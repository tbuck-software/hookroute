<?php

declare(strict_types=1);

namespace Hookroute\Deploy;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;

const SIGNATURE_TTL_SECONDS = 300;
const ARCHIVE_NAME = '.hookroute-release.zip';
const ENV_NAME = '.hookroute-production.env';
const MANIFEST_NAME = '.hookroute-release-manifest.json';
const DEPLOYED_MANIFEST_NAME = '.hookroute-deployed-manifest.json';

function envValue(string $path, string $key): ?string
{
    if (! is_file($path) || ! is_readable($path)) {
        return null;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
            continue;
        }

        [$name, $value] = explode('=', $line, 2);

        if (trim($name) !== $key) {
            continue;
        }

        $value = trim($value);

        if (strlen($value) >= 2 && (($value[0] === '"' && $value[-1] === '"') || ($value[0] === "'" && $value[-1] === "'"))) {
            $value = substr($value, 1, -1);
        }

        return $value;
    }

    return null;
}

function validArchiveEntry(string $name): bool
{
    if ($name === '' || str_contains($name, "\0") || str_contains($name, '\\') || str_starts_with($name, '/')) {
        return false;
    }

    foreach (explode('/', rtrim($name, '/')) as $segment) {
        if ($segment === '' || $segment === '.' || $segment === '..') {
            return false;
        }
    }

    return true;
}

/** @return list<string> */
function manifestFiles(string $path): array
{
    $contents = file_get_contents($path);
    $files = is_string($contents) ? json_decode($contents, true) : null;

    if (! is_array($files) || ! array_is_list($files)) {
        throw new \RuntimeException('Invalid release manifest.');
    }

    foreach ($files as $file) {
        if (! is_string($file) || ! validArchiveEntry($file) || str_ends_with($file, '/')) {
            throw new \RuntimeException('Invalid release manifest entry.');
        }
    }

    return array_values(array_unique($files));
}

/** @param list<string> $previous @param list<string> $current */
function removeStaleFiles(string $root, array $previous, array $current): void
{
    $protected = ['.env', ARCHIVE_NAME, ENV_NAME, DEPLOYED_MANIFEST_NAME, 'public/hookroute-release.php'];
    $currentLookup = array_fill_keys(array_merge($current, $protected), true);

    foreach (array_diff($previous, array_keys($currentLookup)) as $file) {
        if (str_starts_with($file, 'storage/') || ! validArchiveEntry($file)) {
            continue;
        }

        $path = $root.'/'.$file;

        if (is_file($path) || is_link($path)) {
            @unlink($path);
        }
    }
}

function failureCode(\Throwable $exception): string
{
    return match ($exception->getMessage()) {
        'Release archive checksum mismatch.' => 'archive-checksum-mismatch',
        'PHP zip extension is required.' => 'zip-extension-missing',
        'A deployment is already running.' => 'deployment-locked',
        'Unable to open release archive.' => 'archive-open-failed',
        'Unsafe release archive entry.' => 'archive-entry-unsafe',
        'Unable to extract release archive.' => 'archive-extract-failed',
        'Invalid release manifest.', 'Invalid release manifest entry.' => 'manifest-invalid',
        'Release archive is incomplete.' => 'archive-incomplete',
        'Unable to install production configuration.' => 'environment-install-failed',
        'Database migration failed.' => 'migration-failed',
        'Unable to store deployed release manifest.' => 'manifest-store-failed',
        default => 'bootstrap-failed',
    };
}

function release(string $root, string $body, array $server, ?callable $migrate = null): array
{
    $timestamp = $server['HTTP_X_HOOKROUTE_DEPLOY_TIMESTAMP'] ?? null;
    $signature = $server['HTTP_X_HOOKROUTE_DEPLOY_SIGNATURE'] ?? null;
    $currentSecret = envValue($root.'/.env', 'HOOKROUTE_DEPLOY_SECRET');
    $stagedSecret = envValue($root.'/'.ENV_NAME, 'HOOKROUTE_DEPLOY_SECRET');
    $secrets = array_values(array_unique(array_filter([$currentSecret, $stagedSecret], fn ($secret) => is_string($secret) && $secret !== '')));

    if (! is_string($timestamp) || ! ctype_digit($timestamp) || abs(time() - (int) $timestamp) > SIGNATURE_TTL_SECONDS || ! is_string($signature)) {
        throw new \UnexpectedValueException('Unauthorized deployment request.');
    }

    $authorized = false;

    foreach ($secrets as $secret) {
        $authorized = $authorized || hash_equals(hash_hmac('sha256', $timestamp."\n".$body, $secret), $signature);
    }

    if (! $authorized) {
        throw new \UnexpectedValueException('Unauthorized deployment request.');
    }

    $payload = json_decode($body, true, flags: JSON_THROW_ON_ERROR);
    $sha = $payload['sha'] ?? null;
    $checksum = $payload['checksum'] ?? null;

    if (! is_string($sha) || preg_match('/\A[0-9a-f]{40}\z/', $sha) !== 1 || ! is_string($checksum) || preg_match('/\A[0-9a-f]{64}\z/', $checksum) !== 1) {
        throw new \UnexpectedValueException('Invalid deployment payload.');
    }

    $archivePath = $root.'/'.ARCHIVE_NAME;

    $actualChecksum = is_file($archivePath) ? hash_file('sha256', $archivePath) : false;

    if (! is_string($actualChecksum) || ! hash_equals($checksum, $actualChecksum)) {
        throw new \UnexpectedValueException('Release archive checksum mismatch.');
    }

    if (! class_exists(\ZipArchive::class)) {
        throw new \RuntimeException('PHP zip extension is required.');
    }

    $lockDirectory = $root.'/storage/framework';
    @mkdir($lockDirectory, 0775, true);
    $lock = fopen($lockDirectory.'/deployment.lock', 'c');

    if ($lock === false) {
        throw new \RuntimeException('A deployment is already running.');
    }

    if (! flock($lock, LOCK_EX | LOCK_NB)) {
        fclose($lock);
        throw new \RuntimeException('A deployment is already running.');
    }

    try {
        $zip = new \ZipArchive;

        if ($zip->open($archivePath) !== true) {
            throw new \RuntimeException('Unable to open release archive.');
        }

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);

            if (! is_string($name) || ! validArchiveEntry($name)) {
                $zip->close();
                throw new \RuntimeException('Unsafe release archive entry.');
            }
        }

        if (! $zip->extractTo($root)) {
            $zip->close();
            throw new \RuntimeException('Unable to extract release archive.');
        }

        $zip->close();

        $manifestPath = $root.'/'.MANIFEST_NAME;
        $currentManifest = manifestFiles($manifestPath);
        $previousManifest = is_file($root.'/'.DEPLOYED_MANIFEST_NAME)
            ? manifestFiles($root.'/'.DEPLOYED_MANIFEST_NAME)
            : [];

        if (! is_file($root.'/artisan') || ! is_file($root.'/vendor/autoload.php') || ! is_file($root.'/bootstrap/app.php')) {
            throw new \RuntimeException('Release archive is incomplete.');
        }

        if (! @rename($root.'/'.ENV_NAME, $root.'/.env')) {
            throw new \RuntimeException('Unable to install production configuration.');
        }

        @chmod($root.'/.env', 0600);

        if ($migrate === null) {
            require $root.'/vendor/autoload.php';
            $application = require $root.'/bootstrap/app.php';
            $application->make(Kernel::class)->bootstrap();
            $exitCode = Artisan::call('migrate', ['--force' => true]);
        } else {
            $exitCode = $migrate($root);
        }

        if ($exitCode !== 0) {
            throw new \RuntimeException('Database migration failed.');
        }

        removeStaleFiles($root, $previousManifest, $currentManifest);

        if (! @rename($manifestPath, $root.'/'.DEPLOYED_MANIFEST_NAME)) {
            throw new \RuntimeException('Unable to store deployed release manifest.');
        }

        @unlink($archivePath);

        return ['deployed' => true, 'sha' => $sha];
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

function respond(): never
{
    header('Content-Type: application/json');

    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        http_response_code(404);
        echo json_encode(['message' => 'Not Found']);
        exit;
    }

    try {
        echo json_encode(release(dirname(__DIR__), file_get_contents('php://input') ?: '', $_SERVER), JSON_THROW_ON_ERROR);
    } catch (\UnexpectedValueException|\JsonException) {
        http_response_code(401);
        echo json_encode(['message' => 'Unauthorized']);
    } catch (\Throwable $exception) {
        error_log('Hookroute release failed: '.$exception->getMessage());
        http_response_code(500);
        echo json_encode(['message' => 'Deployment failed', 'code' => failureCode($exception)]);
    }

    exit;
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    respond();
}

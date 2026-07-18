<?php

use function Hookroute\Deploy\envValue;
use function Hookroute\Deploy\failureCode;
use function Hookroute\Deploy\manifestFiles;
use function Hookroute\Deploy\release;
use function Hookroute\Deploy\removeStaleFiles;
use function Hookroute\Deploy\validArchiveEntry;

require_once dirname(__DIR__, 2).'/deploy/release.php';

beforeEach(function () {
    $this->releaseRoot = sys_get_temp_dir().'/hookroute-release-'.bin2hex(random_bytes(8));
    mkdir($this->releaseRoot.'/storage/framework', 0775, true);
});

afterEach(function () {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($this->releaseRoot, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    foreach ($iterator as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }

    rmdir($this->releaseRoot);
});

function signedReleaseServer(string $body, string $secret = 'deployment-secret', ?int $timestamp = null): array
{
    $timestamp ??= time();

    return [
        'HTTP_X_HOOKROUTE_DEPLOY_TIMESTAMP' => (string) $timestamp,
        'HTTP_X_HOOKROUTE_DEPLOY_SIGNATURE' => hash_hmac('sha256', $timestamp."\n".$body, $secret),
    ];
}

function createReleaseArchive(string $root, array $entries): string
{
    $path = $root.'/'.Hookroute\Deploy\ARCHIVE_NAME;
    $zip = new ZipArchive;
    expect($zip->open($path, ZipArchive::CREATE))->toBeTrue();

    foreach ($entries as $name => $contents) {
        $zip->addFromString($name, $contents);
    }

    $zip->close();

    return $path;
}

it('reads quoted and unquoted deployment secrets from dotenv files', function () {
    $path = $this->releaseRoot.'/.env';
    file_put_contents($path, "APP_ENV=production\nHOOKROUTE_DEPLOY_SECRET=\"secret value\"\n");

    expect(envValue($path, 'HOOKROUTE_DEPLOY_SECRET'))->toBe('secret value')
        ->and(envValue($path, 'MISSING'))->toBeNull();
});

it('exposes only whitelisted deployment failure codes', function () {
    expect(failureCode(new RuntimeException('PHP zip extension is required.')))->toBe('zip-extension-missing')
        ->and(failureCode(new RuntimeException('DB_PASSWORD=secret')))->toBe('bootstrap-failed');
});

it('rejects unsafe archive and manifest paths', function (string $path, bool $valid) {
    expect(validArchiveEntry($path))->toBe($valid);
})->with([
    ['app/Http/Controller.php', true],
    ['../outside.php', false],
    ['/absolute.php', false],
    ['app\\windows.php', false],
    ['app//empty.php', false],
]);

it('validates release manifests and only removes stale release files', function () {
    mkdir($this->releaseRoot.'/app', 0775, true);
    mkdir($this->releaseRoot.'/storage/logs', 0775, true);
    file_put_contents($this->releaseRoot.'/app/stale.php', 'stale');
    file_put_contents($this->releaseRoot.'/app/current.php', 'current');
    file_put_contents($this->releaseRoot.'/storage/logs/laravel.log', 'persistent');
    file_put_contents($this->releaseRoot.'/.hookroute-release-manifest.json', json_encode(['app/current.php']));

    $current = manifestFiles($this->releaseRoot.'/.hookroute-release-manifest.json');
    removeStaleFiles($this->releaseRoot, ['app/stale.php', 'app/current.php', 'storage/logs/laravel.log'], $current);

    expect($this->releaseRoot.'/app/stale.php')->not->toBeFile()
        ->and($this->releaseRoot.'/app/current.php')->toBeFile()
        ->and($this->releaseRoot.'/storage/logs/laravel.log')->toBeFile();
});

it('rejects expired signatures before opening an archive', function () {
    file_put_contents($this->releaseRoot.'/.env', 'HOOKROUTE_DEPLOY_SECRET=deployment-secret');
    $body = json_encode(['sha' => str_repeat('a', 40), 'checksum' => str_repeat('b', 64)], JSON_THROW_ON_ERROR);

    expect(fn () => release($this->releaseRoot, $body, signedReleaseServer($body, timestamp: time() - 301)))
        ->toThrow(UnexpectedValueException::class, 'Unauthorized deployment request.');
});

it('rejects an archive whose checksum does not match the signed payload', function () {
    file_put_contents($this->releaseRoot.'/.env', 'HOOKROUTE_DEPLOY_SECRET=deployment-secret');
    createReleaseArchive($this->releaseRoot, ['.hookroute-release-manifest.json' => '[]']);
    $body = json_encode(['sha' => str_repeat('a', 40), 'checksum' => str_repeat('b', 64)], JSON_THROW_ON_ERROR);

    expect(fn () => release($this->releaseRoot, $body, signedReleaseServer($body)))
        ->toThrow(UnexpectedValueException::class, 'Release archive checksum mismatch.');
});

it('rejects path traversal entries in a correctly signed archive', function () {
    file_put_contents($this->releaseRoot.'/.env', 'HOOKROUTE_DEPLOY_SECRET=deployment-secret');
    $archive = createReleaseArchive($this->releaseRoot, [
        '../outside.php' => 'unsafe',
        '.hookroute-release-manifest.json' => '[]',
    ]);
    $body = json_encode([
        'sha' => str_repeat('a', 40),
        'checksum' => hash_file('sha256', $archive),
    ], JSON_THROW_ON_ERROR);

    expect(fn () => release($this->releaseRoot, $body, signedReleaseServer($body)))
        ->toThrow(RuntimeException::class, 'Unsafe release archive entry.');
});

it('installs a complete signed release and preserves runtime data', function () {
    file_put_contents($this->releaseRoot.'/.env', 'HOOKROUTE_DEPLOY_SECRET=old-secret');
    file_put_contents($this->releaseRoot.'/'.Hookroute\Deploy\ENV_NAME, "HOOKROUTE_DEPLOY_SECRET=new-secret\nAPP_ENV=production\n");
    mkdir($this->releaseRoot.'/app', 0775, true);
    mkdir($this->releaseRoot.'/storage/logs', 0775, true);
    file_put_contents($this->releaseRoot.'/app/stale.php', 'stale');
    file_put_contents($this->releaseRoot.'/storage/logs/laravel.log', 'persistent');
    file_put_contents($this->releaseRoot.'/'.Hookroute\Deploy\DEPLOYED_MANIFEST_NAME, json_encode([
        'app/stale.php',
        'storage/logs/laravel.log',
    ]));

    $manifest = ['artisan', 'app/current.php', 'bootstrap/app.php', 'vendor/autoload.php'];
    $archive = createReleaseArchive($this->releaseRoot, [
        'artisan' => '#!/usr/bin/env php',
        'app/current.php' => 'current',
        'bootstrap/app.php' => '<?php',
        'vendor/autoload.php' => '<?php',
        Hookroute\Deploy\MANIFEST_NAME => json_encode($manifest),
    ]);
    $body = json_encode([
        'sha' => str_repeat('a', 40),
        'checksum' => hash_file('sha256', $archive),
    ], JSON_THROW_ON_ERROR);
    $migrated = false;

    $result = release(
        $this->releaseRoot,
        $body,
        signedReleaseServer($body, 'new-secret'),
        function (string $root) use (&$migrated): int {
            $migrated = is_file($root.'/app/current.php');

            return 0;
        },
    );

    expect($result)->toBe(['deployed' => true, 'sha' => str_repeat('a', 40)])
        ->and($migrated)->toBeTrue()
        ->and(envValue($this->releaseRoot.'/.env', 'HOOKROUTE_DEPLOY_SECRET'))->toBe('new-secret')
        ->and($this->releaseRoot.'/app/current.php')->toBeFile()
        ->and($this->releaseRoot.'/app/stale.php')->not->toBeFile()
        ->and($this->releaseRoot.'/storage/logs/laravel.log')->toBeFile()
        ->and($this->releaseRoot.'/'.Hookroute\Deploy\ARCHIVE_NAME)->not->toBeFile()
        ->and($this->releaseRoot.'/'.Hookroute\Deploy\DEPLOYED_MANIFEST_NAME)->toBeFile();
});

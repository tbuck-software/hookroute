<?php

declare(strict_types=1);

return static function (array $deployment): int {
    $root = $deployment['root'];

    require_once $root.'/vendor/autoload.php';
    $application = require $root.'/bootstrap/app.php';
    $application->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    $exitCode = Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);

    if ($exitCode !== 0) {
        return $exitCode;
    }

    foreach ([
        'public/hookroute-release.php',
        '.hookroute-deployed-manifest.json',
        '.hookroute-release-manifest.json',
        '.hookroute-release.zip',
        '.hookroute-production.env',
        '.hookroute-deploy-state.json',
    ] as $legacyPath) {
        $path = $root.'/'.$legacyPath;

        if (is_file($path) || is_link($path)) {
            @unlink($path);
        }
    }

    return 0;
};

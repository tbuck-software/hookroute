<?php

use App\Services\UrlGuard;
use Illuminate\Validation\ValidationException;

it('rejects private and non https destination urls', function (string $url) {
    expect(fn () => (new UrlGuard)->assertSafe($url))->toThrow(ValidationException::class);
})->with([
    'loopback' => 'https://127.0.0.1/hook',
    'private network' => 'https://10.0.0.2/hook',
    'metadata endpoint' => 'http://169.254.169.254/latest/meta-data',
    'non-http scheme' => 'file:///etc/passwd',
]);

it('pins a validated hostname to the public address used during validation', function () {
    $guard = new class extends UrlGuard
    {
        protected function resolve(string $host): array
        {
            return ['93.184.216.34'];
        }
    };

    $options = $guard->connectionOptions('https://receiver.example:8443/hook');

    expect($options['curl'][CURLOPT_RESOLVE])->toBe([
        'receiver.example:8443:93.184.216.34',
    ]);
});

it('still requires https when private network destinations are enabled', function () {
    config(['hookroute.allow_private_destinations' => true]);

    expect(fn () => (new UrlGuard)->assertSafe('file:///etc/passwd'))
        ->toThrow(ValidationException::class);
});

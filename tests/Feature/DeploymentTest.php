<?php

use Illuminate\Support\Facades\Artisan;

function deploymentHeaders(string $body, ?int $timestamp = null, string $secret = 'deployment-secret'): array
{
    $timestamp ??= time();

    return [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_HOOKROUTE_DEPLOY_TIMESTAMP' => (string) $timestamp,
        'HTTP_X_HOOKROUTE_DEPLOY_SIGNATURE' => hash_hmac(
            'sha256',
            $timestamp."\n".$body,
            $secret,
        ),
    ];
}

it('runs production migrations for a signed deployment request', function () {
    config()->set('hookroute.deploy_secret', 'deployment-secret');
    Artisan::shouldReceive('call')
        ->once()
        ->with('migrate', ['--force' => true])
        ->andReturn(0);

    $body = json_encode(['sha' => str_repeat('a', 40)], JSON_THROW_ON_ERROR);

    $response = $this->call(
        'POST',
        '/internal/deploy',
        server: deploymentHeaders($body),
        content: $body,
    );

    $response->assertOk()->assertExactJson(['deployed' => true]);
});

it('rejects an invalid deployment signature', function () {
    config()->set('hookroute.deploy_secret', 'deployment-secret');
    Artisan::shouldReceive('call')->never();

    $body = '{}';
    $headers = deploymentHeaders($body);
    $headers['HTTP_X_HOOKROUTE_DEPLOY_SIGNATURE'] = str_repeat('0', 64);

    $this->call('POST', '/internal/deploy', server: $headers, content: $body)
        ->assertUnauthorized();
});

it('rejects an expired deployment signature', function () {
    config()->set('hookroute.deploy_secret', 'deployment-secret');
    Artisan::shouldReceive('call')->never();

    $body = '{}';

    $this->call(
        'POST',
        '/internal/deploy',
        server: deploymentHeaders($body, time() - 301),
        content: $body,
    )->assertUnauthorized();
});

it('hides the deployment endpoint when it is not configured', function () {
    config()->set('hookroute.deploy_secret');
    Artisan::shouldReceive('call')->never();

    $this->post('/internal/deploy')->assertNotFound();
});

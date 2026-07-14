<?php

use App\Jobs\ProcessDelivery;
use App\Models\Connection;
use App\Models\Destination;
use App\Models\Event;
use App\Models\Project;
use App\Models\Source;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('captures an authenticated webhook and fans it out to matching connections', function () {
    Queue::fake();
    $owner = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();
    $source = Source::factory()->for($project)->create();
    $destination = Destination::factory()->for($project)->webhook()->create();
    Connection::factory()->for($project)->for($source)->for($destination)->create([
        'filters' => [['field' => 'status', 'operator' => 'equals', 'value' => 'ready']],
    ]);

    $this->postJson($source->webhookPath(), [
        'status' => 'ready',
        'order' => ['id' => 42],
    ], ['Authorization' => 'Bearer should-not-be-logged'])
        ->assertAccepted()
        ->assertJsonStructure(['event_id']);

    $event = Event::firstOrFail();
    expect($event->payload['order']['id'])->toBe(42)
        ->and($event->headers)->not->toHaveKey('authorization')
        ->and($event->deliveries)->toHaveCount(1);

    Queue::assertPushed(ProcessDelivery::class);
});

it('rejects an invalid source secret and invalid hmac', function () {
    $project = Project::factory()->create();
    $source = Source::factory()->for($project)->create([
        'signing_secret' => 'inbound-signing-secret',
        'signature_header' => 'X-Hookroute-Signature',
    ]);

    $this->postJson('/hooks/'.$source->public_id.'/wrong', ['ok' => true])->assertNotFound();
    $this->postJson($source->webhookPath(), ['ok' => true], [
        'X-Hookroute-Signature' => 'sha256=invalid',
    ])->assertUnauthorized();
    expect(Event::count())->toBe(0);
});

it('accepts a valid hmac signature over the exact request body', function () {
    Queue::fake();
    $source = Source::factory()->create([
        'signing_secret' => 'inbound-signing-secret',
        'signature_header' => 'X-Hookroute-Signature',
    ]);
    $body = json_encode(['event' => 'deployment.ready'], JSON_THROW_ON_ERROR);
    $signature = hash_hmac('sha256', $body, 'inbound-signing-secret');

    $this->call('POST', $source->webhookPath(), server: [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_HOOKROUTE_SIGNATURE' => 'sha256='.$signature,
    ], content: $body)->assertAccepted();

    expect(Event::count())->toBe(1);
});

it('redacts the configured signature header and proxy ip headers', function () {
    Queue::fake();
    $source = Source::factory()->create([
        'signing_secret' => 'inbound-signing-secret',
        'signature_header' => 'X-HMAC',
    ]);
    $body = json_encode(['event' => 'deployment.ready'], JSON_THROW_ON_ERROR);
    $signature = hash_hmac('sha256', $body, 'inbound-signing-secret');

    $this->call('POST', $source->webhookPath(), server: [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_HMAC' => $signature,
        'HTTP_X_FORWARDED_FOR' => '192.0.2.10',
    ], content: $body)->assertAccepted();

    expect(Event::firstOrFail()->headers)
        ->not->toHaveKeys(['x-hmac', 'x-forwarded-for']);
});

it('rejects idempotency keys that cannot fit in storage', function () {
    $source = Source::factory()->create();

    $this->postJson($source->webhookPath(), ['value' => 1], [
        'Idempotency-Key' => str_repeat('x', 256),
    ])->assertBadRequest();

    expect(Event::count())->toBe(0);
});

it('returns the existing event for a repeated idempotency key', function () {
    Queue::fake();
    $source = Source::factory()->create();

    $first = $this->postJson($source->webhookPath(), ['value' => 1], ['Idempotency-Key' => 'same-key']);
    $second = $this->postJson($source->webhookPath(), ['value' => 2], ['Idempotency-Key' => 'same-key']);

    $first->assertAccepted();
    $second->assertOk()->assertJson(['duplicate' => true]);
    expect(Event::count())->toBe(1);
});

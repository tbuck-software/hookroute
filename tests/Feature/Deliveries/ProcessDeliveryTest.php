<?php

use App\Jobs\ProcessDelivery;
use App\Mail\EventNotificationMail;
use App\Models\Connection;
use App\Models\Delivery;
use App\Models\Destination;
use App\Models\Event;
use App\Services\DeliveryDispatcher;
use App\Services\DeliveryErrorSanitizer;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

it('renders and signs a generic webhook delivery', function () {
    config(['hookroute.allow_private_destinations' => true]);
    Http::fake(['https://receiver.example/*' => Http::response(['ok' => true], 200)]);
    $destination = Destination::factory()->webhook()->create([
        'config' => [
            'url' => 'https://receiver.example/events',
            'method' => 'POST',
            'headers' => ['X-Environment' => 'test'],
            'signing_secret' => 'outbound-secret',
        ],
    ]);
    $connection = Connection::factory()->for($destination)->create([
        'payload_mode' => 'template',
        'body_template' => '{"order_id": {{ payload.order.id }}, "event_id": "{{ event.id }}"}',
    ]);
    $event = Event::factory()->for($connection->source)->create([
        'project_id' => $connection->project_id,
        'payload' => ['order' => ['id' => 84]],
        'raw_body' => '{"order":{"id":84}}',
    ]);
    $delivery = Delivery::factory()->for($event)->for($connection)->for($destination)->create();

    (new ProcessDelivery($delivery->id))->handle(app(DeliveryDispatcher::class), app(DeliveryErrorSanitizer::class));

    expect($delivery->fresh()->status)->toBe('delivered');
    Http::assertSent(fn ($request) => $request['order_id'] === 84
        && $request->hasHeader('X-Hookroute-Signature')
        && $request->hasHeader('X-Environment', 'test'));
});

it('sends an immediate email destination through the same delivery pipeline', function () {
    Mail::fake();
    $destination = Destination::factory()->email()->create();
    $connection = Connection::factory()->for($destination)->create([
        'subject_template' => 'Event {{ event.id }} from {{ source.name }}',
        'body_template' => 'The value is {{ payload.value }}.',
    ]);
    $event = Event::factory()->for($connection->source)->create([
        'project_id' => $connection->project_id,
        'payload' => ['value' => 'important'],
    ]);
    $delivery = Delivery::factory()->for($event)->for($connection)->for($destination)->create();

    (new ProcessDelivery($delivery->id))->handle(app(DeliveryDispatcher::class), app(DeliveryErrorSanitizer::class));

    Mail::assertSent(EventNotificationMail::class, fn ($mail) => $mail->subjectLine === 'Event '.$event->public_id.' from '.$event->source->name);
    expect($delivery->fresh()->status)->toBe('delivered');
});

it('keeps a disabled destination delivery skipped', function () {
    $destination = Destination::factory()->webhook()->create(['enabled' => false]);
    $connection = Connection::factory()->for($destination)->create();
    $event = Event::factory()->for($connection->source)->create(['project_id' => $connection->project_id]);
    $delivery = Delivery::factory()->for($event)->for($connection)->for($destination)->create();

    (new ProcessDelivery($delivery->id))->handle(app(DeliveryDispatcher::class), app(DeliveryErrorSanitizer::class));

    expect($delivery->fresh()->status)->toBe('skipped');
});

it('does not process a delivery already claimed by another worker', function () {
    Http::fake();
    $destination = Destination::factory()->webhook()->create();
    $connection = Connection::factory()->for($destination)->create();
    $event = Event::factory()->for($connection->source)->create(['project_id' => $connection->project_id]);
    $delivery = Delivery::factory()->for($event)->for($connection)->for($destination)->create([
        'status' => 'processing',
        'last_attempted_at' => now(),
    ]);

    (new ProcessDelivery($delivery->id))->handle(app(DeliveryDispatcher::class), app(DeliveryErrorSanitizer::class));

    Http::assertNothingSent();
    expect($delivery->fresh()->attempts)->toBe(0);
});

it('never stores credential bearing destination urls in delivery errors', function () {
    $delivery = Delivery::factory()->create();
    $job = new ProcessDelivery($delivery->id);
    $job->failed(new ConnectionException('cURL error for https://discord.com/api/webhooks/123/super-secret?api_key=hidden'));

    $error = $delivery->fresh()->last_error;
    expect($error)->not->toContain('super-secret')
        ->and($error)->not->toContain('api_key')
        ->and($error)->toBe('Could not connect to destination.');
});

it('throws only a sanitized exception back to the queue worker', function () {
    $delivery = Delivery::factory()->create();
    $dispatcher = Mockery::mock(DeliveryDispatcher::class);
    $dispatcher->shouldReceive('deliver')->once()->andThrow(
        new ConnectionException('Failed https://discord.com/api/webhooks/123/super-secret?api_key=hidden'),
    );

    try {
        (new ProcessDelivery($delivery->id))->handle($dispatcher, app(DeliveryErrorSanitizer::class));
        $this->fail('Expected the delivery job to throw.');
    } catch (Throwable $exception) {
        expect($exception->getMessage())->toBe('Could not connect to destination.')
            ->and((string) $exception)->not->toContain('super-secret')
            ->and((string) $exception)->not->toContain('api_key');
    }
});

it('reclaims a processing delivery after its worker lease expires', function () {
    Mail::fake();
    config(['hookroute.delivery_processing_lease_seconds' => 45]);
    $destination = Destination::factory()->email()->create();
    $connection = Connection::factory()->for($destination)->create();
    $event = Event::factory()->for($connection->source)->create(['project_id' => $connection->project_id]);
    $delivery = Delivery::factory()->for($event)->for($connection)->for($destination)->create([
        'status' => 'processing',
        'last_attempted_at' => now()->subSeconds(46),
    ]);

    (new ProcessDelivery($delivery->id))->handle(app(DeliveryDispatcher::class), app(DeliveryErrorSanitizer::class));

    Mail::assertSent(EventNotificationMail::class);
    expect($delivery->fresh()->status)->toBe('delivered');
});

it('keeps a safe http status error through terminal failure', function () {
    config(['hookroute.allow_private_destinations' => true]);
    Http::fake(['https://receiver.example/*' => Http::response('nope', 503)]);
    $destination = Destination::factory()->webhook()->create();
    $connection = Connection::factory()->for($destination)->create();
    $event = Event::factory()->for($connection->source)->create(['project_id' => $connection->project_id]);
    $delivery = Delivery::factory()->for($event)->for($connection)->for($destination)->create();
    $job = new ProcessDelivery($delivery->id);

    expect(fn () => $job->handle(app(DeliveryDispatcher::class), app(DeliveryErrorSanitizer::class)))
        ->toThrow(RuntimeException::class);
    $job->failed(new RuntimeException('Destination returned HTTP 503.'));

    expect($delivery->fresh()->status)->toBe('failed')
        ->and($delivery->fresh()->last_error)->toBe('Destination returned HTTP 503.');
});

<?php

use App\Jobs\SendDigest;
use App\Mail\DigestMail;
use App\Models\Connection;
use App\Models\Destination;
use App\Models\DigestRun;
use App\Models\Event;
use App\Models\Source;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

it('creates only one digest run for a due daily window and includes connected sources', function () {
    Carbon::setTestNow('2026-07-13 18:05:00 Europe/Berlin');
    Queue::fake();
    $destination = Destination::factory()->digest()->create([
        'config' => [
            'recipients' => ['team@example.test'],
            'send_time' => '18:00',
            'window_start_time' => '08:00',
            'timezone' => 'Europe/Berlin',
            'subject' => 'Daily event digest',
            'send_empty' => false,
        ],
    ]);
    $connected = Source::factory()->for($destination->project)->create();
    $unconnected = Source::factory()->for($destination->project)->create();
    Connection::factory()->for($destination->project)->for($connected)->for($destination)->create();
    Event::factory()->for($connected)->for($destination->project)->create(['received_at' => now()->subHour()]);
    Event::factory()->for($unconnected)->for($destination->project)->create(['received_at' => now()->subHour()]);

    $this->artisan('digests:dispatch')->assertSuccessful();
    $this->artisan('digests:dispatch')->assertSuccessful();

    expect(DigestRun::count())->toBe(1)
        ->and(DigestRun::first()->event_count)->toBe(1);
    Queue::assertPushed(SendDigest::class, 1);
});

it('sends an aggregate email for a prepared digest run', function () {
    Mail::fake();
    $run = DigestRun::factory()->withEvents(2)->create();

    (new SendDigest($run->id))->handle();

    Mail::assertSent(DigestMail::class, fn ($mail) => $mail->run->is($run));
    expect($run->fresh()->status)->toBe('sent');
});

it('caps a digest while retaining the total event count', function () {
    Carbon::setTestNow('2026-07-13 18:05:00 Europe/Berlin');
    config(['hookroute.digest_max_events' => 2]);
    Queue::fake();
    $destination = Destination::factory()->digest()->create();
    $source = Source::factory()->for($destination->project)->create();
    Connection::factory()->for($destination->project)->for($source)->for($destination)->create();
    Event::factory()->count(3)->for($destination->project)->for($source)->create(['received_at' => now()->subHour()]);

    $this->artisan('digests:dispatch')->assertSuccessful();

    $run = DigestRun::firstOrFail();
    expect($run->event_ids)->toHaveCount(2)
        ->and($run->event_count)->toBe(2)
        ->and($run->total_event_count)->toBe(3)
        ->and($run->truncated)->toBeTrue();
});

it('includes only events matching at least one enabled digest route', function () {
    Carbon::setTestNow('2026-07-13 18:05:00 Europe/Berlin');
    Queue::fake();
    $destination = Destination::factory()->digest()->create();
    $source = Source::factory()->for($destination->project)->create();
    Connection::factory()->for($destination->project)->for($source)->for($destination)->create([
        'filters' => [['field' => 'status', 'operator' => 'equals', 'value' => 'failed']],
    ]);
    $matching = Event::factory()->for($destination->project)->for($source)->create([
        'payload' => ['status' => 'failed'], 'received_at' => now()->subHour(),
    ]);
    Event::factory()->for($destination->project)->for($source)->create([
        'payload' => ['status' => 'ready'], 'received_at' => now()->subHour(),
    ]);

    $this->artisan('digests:dispatch')->assertSuccessful();

    expect(DigestRun::firstOrFail()->event_ids)->toBe([$matching->id]);
});

it('skips a queued digest when its destination is disabled before sending', function () {
    Mail::fake();
    $run = DigestRun::factory()->withEvents(1)->create();
    $run->destination->update(['enabled' => false]);

    (new SendDigest($run->id))->handle();

    Mail::assertNothingSent();
    expect($run->fresh()->status)->toBe('skipped');
});

it('reconciles events removed by retention before sending', function () {
    Mail::fake();
    $run = DigestRun::factory()->withEvents(1)->create();
    Event::whereKey($run->event_ids)->delete();

    (new SendDigest($run->id))->handle();

    Mail::assertNothingSent();
    expect($run->fresh()->status)->toBe('skipped')
        ->and($run->fresh()->event_count)->toBe(0);
});

it('does not send a digest already claimed by another worker', function () {
    Mail::fake();
    $run = DigestRun::factory()->withEvents(1)->create([
        'status' => 'processing',
        'processing_started_at' => now(),
    ]);

    (new SendDigest($run->id))->handle();

    Mail::assertNothingSent();
});

it('reclaims a digest after its processing lease expires', function () {
    Mail::fake();
    config(['hookroute.digest_processing_lease_seconds' => 75]);
    $run = DigestRun::factory()->withEvents(1)->create([
        'status' => 'processing',
        'processing_started_at' => now()->subSeconds(76),
    ]);

    (new SendDigest($run->id))->handle();

    Mail::assertSent(DigestMail::class);
    expect($run->fresh()->status)->toBe('sent');
});

<?php

namespace App\Jobs;

use App\Enums\DeliveryStatus;
use App\Models\Delivery;
use App\Services\DeliveryDispatcher;
use App\Services\DeliveryErrorSanitizer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class ProcessDelivery implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $timeout = 30;

    public function __construct(public readonly int $deliveryId) {}

    public function backoff(): array
    {
        return [60, 300, 900, 3600];
    }

    public function handle(DeliveryDispatcher $dispatcher, DeliveryErrorSanitizer $errors): void
    {
        $claimed = Delivery::query()
            ->whereKey($this->deliveryId)
            ->where(function ($query) {
                $query->whereIn('status', DeliveryStatus::queued())
                    ->orWhere(function ($stale) {
                        $stale->where('status', DeliveryStatus::Processing)
                            ->where(function ($lease) {
                                $lease->whereNull('last_attempted_at')
                                    ->orWhere('last_attempted_at', '<=', now()->subSeconds(config('hookroute.delivery_processing_lease_seconds')));
                            });
                    });
            })
            ->update([
                'status' => DeliveryStatus::Processing,
                'attempts' => DB::raw('attempts + 1'),
                'last_attempted_at' => now(),
                'last_error' => null,
            ]);
        if (! $claimed) {
            return;
        }

        $delivery = Delivery::findOrFail($this->deliveryId);

        try {
            $response = $dispatcher->deliver($delivery);
            if ($delivery->refresh()->status === DeliveryStatus::Skipped) {
                return;
            }
            if ($response && ! $response->successful()) {
                $delivery->update([
                    'status' => DeliveryStatus::Retrying,
                    'response_status' => $response->status(),
                    'response_excerpt' => Str::limit($response->body(), config('hookroute.response_excerpt_bytes'), '…'),
                    'last_error' => 'Destination returned HTTP '.$response->status().'.',
                ]);

                throw new \RuntimeException('Destination returned HTTP '.$response->status().'.');
            }

            $delivery->update([
                'status' => DeliveryStatus::Delivered,
                'response_status' => $response?->status(),
                'response_excerpt' => $response ? Str::limit($response->body(), config('hookroute.response_excerpt_bytes'), '…') : null,
                'delivered_at' => now(),
            ]);
            $delivery->destination()->update(['last_delivered_at' => now()]);
        } catch (Throwable $exception) {
            if ($delivery->fresh()->status !== DeliveryStatus::Retrying) {
                Delivery::whereKey($delivery->id)->where('status', DeliveryStatus::Processing)->update([
                    'status' => DeliveryStatus::Retrying,
                    'last_error' => $errors->summarize($exception),
                ]);
            }

            throw new \RuntimeException($errors->summarize($exception));
        }
    }

    public function failed(?Throwable $exception): void
    {
        $delivery = Delivery::find($this->deliveryId);
        if (! $delivery) {
            return;
        }

        $delivery->update([
            'status' => 'failed',
            'last_error' => $delivery->last_error ?: app(DeliveryErrorSanitizer::class)->summarize($exception),
        ]);
    }
}

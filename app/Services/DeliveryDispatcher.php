<?php

namespace App\Services;

use App\Enums\DestinationType;
use App\Mail\EventNotificationMail;
use App\Models\Delivery;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;

class DeliveryDispatcher
{
    public function __construct(
        private readonly TemplateRenderer $renderer,
        private readonly UrlGuard $urlGuard,
        private readonly ResponseLimiter $responseLimiter,
    ) {}

    public function deliver(Delivery $delivery): ?Response
    {
        $delivery->loadMissing('event.source', 'event.project', 'connection', 'destination');
        $destination = $delivery->destination;

        if (! $destination->enabled) {
            $delivery->update(['status' => 'skipped', 'last_error' => 'Destination is disabled.']);

            return null;
        }

        return match ($destination->type) {
            DestinationType::Webhook => $this->webhook($delivery),
            DestinationType::Discord => $this->discord($delivery),
            DestinationType::Email => $this->email($delivery),
            DestinationType::Digest => throw new RuntimeException('Digest destinations are handled by the scheduler.'),
        };
    }

    private function webhook(Delivery $delivery): Response
    {
        $config = $delivery->destination->config;
        $url = $config['url'] ?? '';
        $options = $this->requestOptions($url);
        $body = $delivery->connection->payload_mode === 'passthrough'
            ? ($delivery->event->raw_body ?? '')
            : $this->renderer->render($delivery->connection->body_template, $delivery->event->templateContext());
        $headers = array_merge($config['headers'] ?? [], [
            'Content-Type' => $delivery->connection->payload_mode === 'passthrough'
                ? ($delivery->event->content_type ?: 'application/json')
                : 'application/json',
            'X-Hookroute-Event' => $delivery->event->public_id,
        ]);

        if ($secret = ($config['signing_secret'] ?? null)) {
            $headers['X-Hookroute-Signature'] = 'sha256='.hash_hmac('sha256', $body, $secret);
        }

        return Http::withHeaders($headers)
            ->withBody($body, $headers['Content-Type'])
            ->timeout(config('hookroute.delivery_timeout_seconds'))
            ->withOptions($options)
            ->send(strtoupper($config['method'] ?? 'POST'), $url);
    }

    private function discord(Delivery $delivery): Response
    {
        $config = $delivery->destination->config;
        $url = $config['url'] ?? '';
        $options = $this->requestOptions($url);
        $rendered = $this->renderer->render(
            $delivery->connection->body_template ?: '**{{ source.name }}** · `{{ event.id }}`\n```json\n{{ payload }}\n```',
            $delivery->event->templateContext(),
        );
        $decoded = json_decode($rendered, true);
        $payload = is_array($decoded) ? $decoded : ['content' => Str::limit($rendered, 1_950, '…')];
        $payload['allowed_mentions'] = ['parse' => []];
        if ($username = ($config['username'] ?? null)) {
            $payload['username'] = $username;
        }

        return Http::timeout(config('hookroute.delivery_timeout_seconds'))
            ->withOptions($options)
            ->post($url, $payload);
    }

    private function email(Delivery $delivery): null
    {
        $config = $delivery->destination->config;
        $subject = $this->renderer->render(
            $delivery->connection->subject_template ?: 'Event {{ event.id }} from {{ source.name }}',
            $delivery->event->templateContext(),
        );
        $body = $this->renderer->render(
            $delivery->connection->body_template ?: "Source: {{ source.name }}\nReceived: {{ event.received_at }}\n\n{{ payload }}",
            $delivery->event->templateContext(),
        );

        Mail::to($config['recipients'] ?? [])->send(new EventNotificationMail($delivery->event, $subject, $body));

        return null;
    }

    private function requestOptions(string $url): array
    {
        return array_replace_recursive(
            ['allow_redirects' => false],
            $this->urlGuard->connectionOptions($url),
            $this->responseLimiter->options(),
        );
    }
}

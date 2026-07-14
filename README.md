# Hookroute

Hookroute is a focused, self-hosted webhook gateway. It captures inbound requests, evaluates project-owned routes, and delivers each matching event independently to HTTP, Discord, immediate email, or a scheduled email digest.

It is deliberately smaller than a general workflow engine and can run on ordinary PHP/MySQL hosting with one cron entry—no Redis, Docker, or resident process is required.

## Capabilities

- Multiple users, shared projects, invitations, and owner/admin/member roles
- Ownership transfer and invitation-only registration after the first account
- Secret source URLs plus optional HMAC-SHA256 verification
- Redacted raw request capture and idempotency-key handling
- Fan-out with payload filters and per-route templates
- HTTPS webhooks with static headers and optional outbound HMAC signing
- Discord delivery with mentions disabled by default
- Immediate email and daily email digest windows
- Database-backed retries with exponential backoff
- Delivery response excerpts, errors, and one-click replay
- Per-project event retention
- SQLite for a small installation; MySQL for shared hosting

## Requirements

- Docker Desktop or another Docker Compose compatible runtime
- Composer 2 for the initial dependency install
- The included Laravel Sail environment provides PHP 8.4, MySQL 8.0, Node.js, and Mailpit
- A cron facility that can run every minute

## Local development with Sail

```bash
composer install
cp .env.example .env
sail up -d
sail artisan key:generate
sail artisan migrate --seed
sail npm install
sail npm run build
```

The application is available at `http://localhost`; Mailpit is available at `http://localhost:8025`.

The development seed creates `demo@hookroute.test` with password `password` and a populated example project.

Run the scheduler during development in a second terminal so database-queued deliveries and digests are processed:

```bash
sail artisan schedule:work
```

This repository includes Sail's `laravel.test`, MySQL, and Mailpit services. If your shell alias points to `vendor/bin/sail`, all commands above work as written. Use `sail down` to stop the environment and `sail down -v` only when the local MySQL volume should also be deleted.

## Shared-hosting deployment

1. Point the domain document root at the project's `public/` directory.
2. Configure `.env` with `APP_ENV=production`, `APP_DEBUG=false`, the canonical HTTPS `APP_URL`, `SESSION_SECURE_COOKIE=true`, MySQL credentials, and SMTP credentials.
3. Keep `QUEUE_CONNECTION=database`, `SESSION_DRIVER=database`, and `CACHE_STORE=database`.
4. Run `composer install --no-dev --optimize-autoloader`, `php artisan migrate --force`, and `php artisan optimize`.
5. Build assets locally with `npm ci && npm run build` if Node is unavailable on the host, then upload `public/build/`.
6. Make `storage/` and `bootstrap/cache/` writable by PHP.
7. Add one cron entry:

```cron
* * * * * cd /absolute/path/to/hookroute && php artisan schedule:run >> /dev/null 2>&1
```

That scheduler creates due digests, drains the database delivery queue for up to 50 seconds, and prunes expired event history.

If `DB_QUEUE_RETRY_AFTER` is customized, keep it greater than both processing leases and job timeouts. The shipped 90-second queue retry window is paired with 30/60-second job timeouts and 45/75-second delivery/digest leases.

The first account can register without an invitation. Afterwards, new accounts require a valid project invitation unless `HOOKROUTE_ALLOW_PUBLIC_REGISTRATION=true` is set deliberately.

## Webhook ingress

Create a source in the UI and send a request to its generated URL:

```bash
curl -X POST 'https://webhook.example.com/hooks/{source}/{secret}' \
  -H 'Content-Type: application/json' \
  -H 'Idempotency-Key: order-1842-created' \
  -d '{"event":"order.created","order":{"id":1842}}'
```

When source HMAC verification is enabled, sign the exact raw request body with the configured secret and send the hex digest as either `sha256={digest}` or `{digest}` in the configured header.

## Route templates

Templates can reference the following roots:

- `payload.*` — parsed inbound JSON or form fields
- `event.id`, `event.received_at`
- `source.id`, `source.name`
- `project.name`, `project.slug`

Example HTTP JSON template:

```json
{
  "event_id": "{{ event.id }}",
  "order_id": {{ payload.order.id }},
  "source": "{{ source.name }}"
}
```

The `passthrough` mode instead forwards the captured raw body and its original content type.

## Security model

- Source and destination secrets are encrypted at rest with `APP_KEY`.
- Read-only members cannot retrieve source URLs or destination credentials.
- Sensitive inbound headers are discarded rather than persisted.
- Outbound URLs must use HTTPS, resolve only to public IP space, and are pinned to the validated address for the connection unless `HOOKROUTE_ALLOW_PRIVATE_DESTINATIONS=true` is explicitly configured.
- Redirects are disabled for outbound delivery and Discord mentions are suppressed.
- Destination response downloads and daily digest sizes are capped to protect workers from unbounded memory use.
- Rotate `APP_KEY` only with a migration plan: encrypted configuration depends on it.

For intentional private-network delivery, enable the private-destination setting only on a trusted installation whose project administrators are allowed to reach that network.

## Verification

```bash
sail artisan test
sail npm run build
sail npm run lint
```

The test suite covers authorization, invitation acceptance, ingress authentication, idempotency, filtering, templating, HTTP signing, email, digest windows, retention, and network-address rejection.

## License

MIT

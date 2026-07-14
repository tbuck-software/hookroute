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

For production:

- PHP 8.3 or newer with the common Laravel extensions, including cURL, mbstring, OpenSSL, PDO, tokenizer, and XML
- MySQL 8+ or SQLite
- Composer 2
- An SMTP account for invitations, immediate email destinations, and digests
- A cron facility that can run every minute
- A web server whose document root can point to the project's `public/` directory

Node.js is only needed while building frontend assets. The generated `public/build/` directory can be uploaded to a host without Node.js.

Local development additionally requires Docker Desktop or another Docker Compose compatible runtime. The included Laravel Sail environment provides PHP 8.4, MySQL 8.0, Node.js, and Mailpit.

## Local development with Sail

```bash
git clone https://github.com/tbuck-software/hookroute.git
cd hookroute
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

The public landing page includes the product model, feature overview, fixed-price setup offer, contact details, imprint, and privacy information. The canonical repository URL enables its GitHub calls to action and can be overridden for a fork:

```dotenv
HOOKROUTE_REPOSITORY_URL=https://github.com/tbuck-software/hookroute
```

Run the scheduler during development in a second terminal so database-queued deliveries and digests are processed:

```bash
sail artisan schedule:work
```

This repository includes Sail's `laravel.test`, MySQL, and Mailpit services. If your shell alias points to `vendor/bin/sail`, all commands above work as written. Use `sail down` to stop the environment and `sail down -v` only when the local MySQL volume should also be deleted.

## Production installation

Start from a tagged release rather than an arbitrary commit from `main`. Replace `v0.1.0` with the release being installed:

```bash
git clone https://github.com/tbuck-software/hookroute.git
cd hookroute
git checkout v0.1.0
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
```

Configure `.env` before running the database migration:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://webhook.example.com
SESSION_SECURE_COOKIE=true

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=hookroute
DB_USERNAME=hookroute
DB_PASSWORD=change-me

QUEUE_CONNECTION=database
SESSION_DRIVER=database
CACHE_STORE=database

MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=hookroute@example.com
MAIL_PASSWORD=change-me
MAIL_FROM_ADDRESS=hookroute@example.com
```

Then finish the installation:

```bash
php artisan migrate --force
npm ci
npm run build
php artisan optimize
```

If Node.js is unavailable on the server, run `npm ci && npm run build` for the exact same release locally and upload the generated `public/build/` directory.

### Web server and scheduler

1. Point the domain document root at the project's `public/` directory.
2. Make `storage/` and `bootstrap/cache/` writable by PHP.
3. Keep `QUEUE_CONNECTION=database`, `SESSION_DRIVER=database`, and `CACHE_STORE=database`.
4. Add one cron entry:

```cron
* * * * * cd /absolute/path/to/hookroute && php artisan schedule:run >> /dev/null 2>&1
```

That scheduler creates due digests, drains the database delivery queue for up to 50 seconds, and prunes expired event history.

If `DB_QUEUE_RETRY_AFTER` is customized, keep it greater than both processing leases and job timeouts. The shipped 90-second queue retry window is paired with 30/60-second job timeouts and 45/75-second delivery/digest leases.

The first account can register without an invitation. Afterwards, new accounts require a valid project invitation unless `HOOKROUTE_ALLOW_PUBLIC_REGISTRATION=true` is set deliberately.

## Updates

Hookroute uses semantic version tags and GitHub Releases. There is deliberately no automatic updater or background version check: an update can contain database migrations and should be installed consciously after reading its release notes.

Before updating:

1. Read the release notes and note any version-specific instructions.
2. Back up the database and `.env` file.
3. Pause the scheduler cron entry so no delivery worker starts during the update.
4. Ensure the working tree contains no local source modifications.

Replace `v0.1.1` with the target release and run:

```bash
php artisan down --retry=60
git fetch --tags origin
git checkout v0.1.1
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan optimize
php artisan up
```

If frontend assets are built elsewhere, upload the new `public/build/` directory before running `php artisan up`. Resume the scheduler afterwards and send a test event through one route.

Never replace `.env` or run `php artisan key:generate` during an update. Source and destination secrets are encrypted with the existing `APP_KEY`; changing it without a migration plan makes those values unreadable. Database downgrades are not supported, so restore the backup when rolling back across migrations.

The optional fixed-price setup service covers initial installation and 30 days of setup-related support. It does not include ongoing maintenance or automatic updates.

Release changes are recorded in [CHANGELOG.md](CHANGELOG.md). Subscribe to the repository's GitHub Releases to be notified about new versions.

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

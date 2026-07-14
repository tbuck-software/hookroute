# Implementation notes

- Laravel 13, Vue 3, Inertia, TypeScript, Pest, Laravel Sail (PHP 8.4), MySQL 8.0, Mailpit, database queue, and Laravel scheduler.
- The application is intentionally a focused webhook gateway rather than a general workflow engine.
- Core records are project scoped. Owner/admin may configure; members have operational read access with credentials and downstream response bodies removed.
- Source URLs use random bearer secrets, optionally combined with HMAC-SHA256 over the exact raw request body.
- Connections fan out independently to generic HTTPS, Discord, immediate email, or scheduled daily digest destinations.
- Delivery and digest workers claim jobs atomically with recoverable processing leases, retry with backoff, sanitize stored/queue-reported failures, and permit controlled replay.
- HTTPS targets are resolved, checked against reserved/private ranges, and pinned to the validated address for the cURL connection. Redirects are disabled.
- Digest generation applies route filters, streams candidate events, caps included events/previews, reconciles retention before send, and atomically claims runs.
- Registration is open for the first account, invitation-only afterwards by default, and can be opened explicitly through configuration.
- Shared-hosting operation uses one minutely scheduler cron to create digests and drain database jobs for a bounded interval.

Verification at final implementation pass:

- `php artisan test`: 70 tests, 227 assertions
- `vendor/bin/pint`: passed
- `npm run lint`: passed
- `npm run build`: passed
- `composer validate --no-check-publish`: passed
- `git diff --check`: passed
- Browser QA: desktop and mobile layout checks, key CRUD/dialog/event/team flows, no console warnings or errors
- Sail QA: MySQL migration/seed, 70 tests/227 assertions, ESLint, and production frontend build all passed inside the containers

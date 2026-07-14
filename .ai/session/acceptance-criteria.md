# Acceptance criteria

- The first user can register; later users join by invitation unless public registration is deliberately enabled.
- Users can verify email, create projects, invite members, assign owner/admin/member access, and transfer ownership.
- Every project resource is inaccessible to non-members; destructive configuration requires owner/admin access.
- Sources expose a random secret URL, optionally verify an HMAC header, reject invalid requests, and retain a redacted raw event record.
- Active connections filter events and independently fan out to generic HTTP, Discord, or immediate email destinations.
- Connection templates can reference event, project, source, and payload values.
- HTTP failures are retried with backoff and visible per delivery; successful and failed deliveries can be inspected and replayed.
- Digest destinations send one bounded email per configured daily window containing only events matching enabled connections, without duplicate runs.
- Outbound HTTP blocks local/private targets by default, suppresses Discord mentions, limits response logging, and keeps destination secrets encrypted.
- The application runs with SQLite or MySQL, the database queue, and a single minutely Laravel scheduler cron entry.
- The responsive Vue UI provides project switching, overview metrics, CRUD screens, event detail, replay, and team management.

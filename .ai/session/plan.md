# Implementation plan

1. Model projects, memberships, invitations, sources, destinations, connections, events, deliveries, and digest runs.
2. Add policies and project-scoped controllers.
3. Capture raw webhooks, authenticate source URLs/HMAC, redact headers, match filters, and enqueue deliveries.
4. Dispatch webhook, Discord, and immediate email destinations with templating, SSRF protection, signing, retry, and replay.
5. Schedule daily email digest windows with duplicate-run protection.
6. Build an operational Vue/Inertia UI for dashboard, sources, destinations, routes, events, and team management.
7. Verify with Pest, frontend type checking/build, formatting, review, and security review.

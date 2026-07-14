# Review notes

The first independent read-only review returned `block` with four release blockers:

1. DNS rebinding between URL validation and connection.
2. Credential-bearing destination URLs retained in delivery exception messages.
3. Disabled destinations overwritten from `skipped` to `delivered`.
4. No ownership-transfer path, allowing owner account deletion to cascade shared project data.

All four were repaired and covered by regression tests. The same remediation pass also addressed atomic worker/replay claims, idempotency-key races and bounds, custom signature/proxy header redaction, invitation-token exposure, digest volume/filtering/reconciliation, public-registration defaults, response download limits, and read-only response-excerpt exposure.

The first re-review pass identified two remaining queue issues: raw exceptions could still reach worker logs/`failed_jobs`, and processing claims had no stale-worker recovery. Jobs now rethrow only new sanitized exceptions and use leases longer than their job timeouts, allowing a retry to reclaim work after a terminated worker without admitting a concurrent active worker.

The timing was then aligned with Laravel's 90-second database `retry_after`: delivery uses a 30-second timeout and 45-second lease; digest uses a 60-second timeout and 75-second lease. A configuration invariant test enforces `timeout < lease < retry_after` for both jobs.

Ownership mutations are serialized on the project row. Transfer rechecks authority and membership inside the transaction, clears stale owner pivots, promotes exactly one target, and project deletion/removal/role changes recheck the locked current owner. Target user deletion and ownership transfer are also serialized on the target user row.

Final independent read-only re-review verdict: `approve`. No release blocker remains. Residual risks are MySQL/concurrency integration coverage, scheduler hard-kill recovery, and external HTTP/SMTP at-least-once behavior.

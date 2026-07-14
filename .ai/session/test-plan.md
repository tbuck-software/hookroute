# Test plan

- Feature: project membership authorization, invitation-gated registration, invitation acceptance, ownership transfer, and owner deletion guard.
- Feature: secret and HMAC webhook ingestion, disabled source rejection, idempotency, fan-out creation.
- Feature: generic HTTP transformation/signing and successful delivery state.
- Feature: immediate email delivery.
- Feature: due digest dispatch, connection filter selection, size caps, pruning reconciliation, aggregate mail, and duplicate-worker prevention.
- Feature: replay authorization and queueing.
- Unit: filter operators, template rendering, digest window calculation, response-size limiting, private-network URL rejection, and connection-time DNS pinning.
- Regression: Breeze authentication/profile suite.
- Frontend: TypeScript compilation, Vite production build, ESLint.

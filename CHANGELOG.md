# Changelog

All notable changes to Hookroute are documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and releases use [Semantic Versioning](https://semver.org/spec/v2.0.0.html). Hookroute is still in initial development, so release notes should be reviewed carefully before every update.

## [Unreleased]

## [0.1.0] - 2026-07-14

### Added

- Multi-user projects with invitations and owner, admin, and member roles
- Secret webhook source URLs with optional HMAC-SHA256 verification
- Event capture, redacted headers, idempotency handling, retention, and replay
- Payload filters, passthrough delivery, and per-route JSON templates
- HTTP and Discord destinations with retries and delivery evidence
- Immediate email destinations and scheduled email digests
- Shared-hosting operation with a database queue and one scheduler cron entry
- German landing page with a complete English version at `/en`
- Public pricing, contact, imprint, and privacy pages
- Production installation and update documentation

[Unreleased]: https://github.com/tbuck-software/hookroute/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/tbuck-software/hookroute/releases/tag/v0.1.0

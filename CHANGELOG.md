# Changelog

## [1.1.2] - 2026-11-24

### Added

- Support tickets: **Dashboard > Support** (`/dashboard/support`) and a **Report a problem** toolbar button let users send a ticket, with diagnostics attached, to the helpdesk portal API. Tickets that can't be delivered are queued (`LgtSupportTicketQueue`) and retried only by the `lgt_support_retry` task, scheduled under Dashboard > System & Settings > Automation, with backoff, expiry and locking. Settings come from the site's `.env` (see `.env.example`); **Dashboard > LGT Toolkit > Support Settings** shows them read-only. Disabled on non-production sites unless `LGT_SUPPORT_ENABLED_NON_PRODUCTION=true`.
- **Dashboard > Support > All Tickets** (`/dashboard/support/tickets`), super user only: every ticket submitted through the site (`LgtSupportTickets`, including the message), kept for 12 months. Each delivered ticket links to its page in the portal (the `url` from the API response, kept only when it is on the portal's own host).
- `composer check` script (format check and tests), which the CodeStandards workflow already ran.

## [0.0.12] - 2026-10-20

### Added

- Redirections dashboard page (`/dashboard/lgt_toolkit/redirections`) to add, edit, delete and CSV-import 301/302 redirections to pages (default 302). Each rule is also stored as an additional PagePath on the destination page; a request middleware serves the rule so 302s are honoured. The list uses the standard paginated, sortable, filterable search classes (`src/Search`).

## [0.0.10] - 2026-10-19

### Changed

Updated ClassKit dependency to ^2.0

## [0.0.9] - 2026-10-08

### Added

- Expanded PHPUnit regression coverage for slideshow configuration and rendering, AJAX endpoints, mail templates, social sharing, attribute values, blocks, dashboard output, and the responsive picture override.
- Added a lightweight PHPUnit bootstrap for testing package code without booting a Concrete CMS site.

### Changed

- Updated the README testing guidance to describe the current test suite and commands.

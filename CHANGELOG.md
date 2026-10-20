# Changelog

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

# LGT Toolkit

[![Tests](https://github.com/limegreentangerine/lgt_toolkit/actions/workflows/Tests.yml/badge.svg)](https://github.com/limegreentangerine/lgt_toolkit/actions/workflows/Tests.yml)

LGT Toolkit is a Concrete CMS package designed to provide a standard set of site defaults, blocks, attributes, and admin utilities for Lime Green Tangerine builds. It is built as a `concrete5-package` and installs a consistent toolkit for content editors, developers, and designers working inside Concrete CMS.

## Requirements

- Concrete CMS 9.5+
- PHP 8.4+
- Composer-based project installation

## Package overview

The package defines a package handle of `lgt_toolkit` and is installed as a standard Concrete package. On install and upgrade it:

- registers package service providers
- creates dashboard single pages
- installs custom attribute types and attribute sets
- installs a block type set and the bundled block types
- copies selected application overrides into `/application`
- applies default CMS configuration values for SEO, security, session handling, white-labelling, and social media links

## Included functionality

### Core CMS defaults

The package sets a wide range of default Concrete configuration values to match the LGT project baseline, including:

- marketplace disabled
- URL rewriting enabled
- SEO title formatting and trailing slash preferences
- accessibility toolbar titles enabled
- white-label settings applied for the Lime Green Tangerine brand
- database-backed session configuration
- security session invalidation on IP mismatch
- social media service definitions for common profiles

### Custom attributes

The package creates a set of reusable attributes for site and page content:

- Site attributes
    - Company name
    - Company phone
    - Company email
    - Company address
    - Default sharing image
- Page / collection attributes
    - Page banner
    - SEO header
    - Page redirector
- File attributes
    - File categories

It also installs custom attribute types:

- `country`
- `lgt_colour_picker`
- `lgt_file_set`
- `lgt_page_redirector`

### Custom blocks

The package auto-installs these block types under a package-specific block set:

- `lgt_ajax_file_list`
- `lgt_ajax_page_list`
- `lgt_anchor_menu`
- `lgt_anchor_target`
- `lgt_blockquote`
- `lgt_button`
- `lgt_content_side_title`
- `lgt_content_site_attribute`
- `lgt_fileset_gallery`
- `lgt_manual_nav`
- `lgt_section`
- `lgt_social_share`
- `lgt_spacer`
- `lgt_video`
- `lgt_vimeo_video`

These blocks provide content patterns and UI elements commonly used across LGT sites, including page and file lists, navigation, buttons, content sections, galleries, video embeds, and social sharing.

### Dashboard utilities

The package creates dashboard pages for common tasks:

- `/dashboard/lgt_toolkit`
- `/dashboard/lgt_toolkit/cookie_popup`
- `/dashboard/lgt_toolkit/duplicate_express`
- `/dashboard/lgt_toolkit/redirections`
- `/dashboard/lgt_toolkit/support`
- `/dashboard/support`
- `/dashboard/support/tickets` (super user only)

These pages support configuration for:

- cookie policy behaviour
- duplicate Express object tooling
- 301/302 redirections to pages
- support tickets to the helpdesk

### Redirections

The **Redirections** dashboard page (`/dashboard/lgt_toolkit/redirections`) manages redirections from old URL paths to pages on the site, for example after a migration or a page rename.

Each rule has a source path, a destination page and a status code:

- **Temporary (302)** is the default and is recommended.
- **Permanent (301)** can be chosen per rule.

From the dashboard page you can add, edit, delete and bulk-delete rules, and filter the list by source path or status code. The list is paginated and sortable.

#### How redirections are served

Rules are stored in the `LgtRedirections` table. Each rule's source path is also added as a non-canonical `PagePath` on the destination page, and removed again when the rule is changed or deleted. Canonical page paths are never touched.

A request middleware answers matching requests before Concrete issues its own redirect for additional page paths. Core always uses a 301, so this is what allows 302 rules to work. The middleware:

- only handles `GET` and `HEAD` requests
- keeps the original query string on the redirect
- does nothing if the destination page has been deleted, trashed or is no longer routable
- fails open, so a database problem never takes the site down

#### Path rules

Source paths are normalised before they are stored or matched: lowercase, a leading slash, no trailing slash, and no query string or fragment. Full URLs are accepted if they are on the current site's host; URLs on other hosts are rejected.

A rule is rejected if the source:

- is empty, the home page (`/`), or contains control characters, whitespace or `.` / `..` segments
- is a reserved system path, such as `/dashboard`, `/login`, `/logout`, `/concrete`, `/ccm`, `/application`, `/packages`, `/updates`, `/index.php`, `/robots.txt` or `/sitemap.xml`
- is already the address of the destination page, or of any other page
- already has a redirection

The destination must be an existing, published page. Trashed, system, admin and external-link pages cannot be used.

#### CSV import

Rules can be imported in bulk from the dashboard page, which also offers a sample file to download. Files must be `.csv` or `.txt` and up to 2MB. Up to 5,000 rows are read.

```csv
source,destination,code
/old-about-us,/about-us,302
/old-contact,/contact,301
/old-news,/news,
```

- Columns are source, destination and an optional status code (301 or 302; blank uses 302).
- The delimiter (comma, semicolon or tab) is detected from the first line.
- A header row is skipped if the first cell starts with `source`, `from` or `old`.
- The destination is a path or full URL of a page on this site.
- Invalid rows are skipped rather than failing the whole import. The import report lists each skipped row with its line number and reason, including duplicates within the file.

#### Page Redirector attribute

Separately, the `lgt_page_redirector` attribute type and its `page_redirector` page attribute (in the Navigation and Indexing set) redirect an individual page, when it is viewed, to another page or an external URL using a chosen HTTP status. This is independent of the Redirections dashboard page.

### Support tickets

Logged-in users can raise a helpdesk ticket from **Dashboard > Support** (`/dashboard/support`), or from the **Report a problem** button in the page toolbar, which prefills the current page. Anyone who can view that page can submit a ticket. The ticket is sent server-to-server to the helpdesk portal API with diagnostics attached. The portal emails the submitter an acknowledgement with the ticket number.

Tickets are categorised as **Something's broken**, **Content help** or **Change request**. There is no "site down" option: if the site is down this form can't be reached, so use email or phone instead. The portal sets the priority.

If the portal can't be reached, the ticket is saved in a retry queue and sent later by the scheduled task. Queue rows (including attachments) are deleted as soon as the portal accepts the ticket.

Every submission is also recorded in `LgtSupportTickets` (references, status, category, subject, submitter, page and the full message, but no attachments). Its status is updated as the ticket is delivered, queued or fails, and records older than 12 months are deleted by the scheduled task.

#### All Tickets (super user only)

**Dashboard > Support > All Tickets** (`/dashboard/support/tickets`) lists every recorded ticket. It is linked from the Support page for the super user only and hidden from the dashboard menu. Its controller refuses everyone else for every action, whatever the page permissions say.

- The list uses the standard search classes (`src/Search/.../SupportTickets` and `controllers/search/support_tickets.php`), like Redirections: sortable columns (submitted, reference, status, category, subject, from), keyword search (references, subject, name, email), status and category filters, and pagination. The message is not shown in the list.
- Click a ticket to open its detail view (`/dashboard/support/tickets/detail/{id}`), which shows everything stored: portal and local references, dates, category, sender, page, last error and the full message with diagnostics.
- Delivered tickets link to the ticket in the portal. The link comes from the `url` field of the portal's 201 response and is only kept when it uses the same scheme and host as `LGT_SUPPORT_URL`.

#### Requirements

- The site must load its root `.env` file into the environment. The LGT site skeleton does this in `application/bootstrap/autoload.php` with Symfony Dotenv. The `vendor/bin/concrete` CLI loads the same file, so the scheduled task sees the same settings.
- The site's canonical URL must be set (**System & Settings > SEO > URLs and Redirection**). Page links in tickets are only included when they are on that host.

#### Configuration

The settings live in the site's `.env` file, not in the database or Concrete's file config. See `.env.example`:

| Variable                             | Required | Description                                                                                                                       |
| ------------------------------------ | -------- | --------------------------------------------------------------------------------------------------------------------------------- |
| `LGT_SUPPORT_API_KEY`                | Yes      | The site's key from the helpdesk portal. Sent privately; never commit it.                                                         |
| `LGT_SUPPORT_URL`                    | No       | The portal endpoint. Defaults to `https://portal.limegreentangerine.co.uk/api/tickets.php`. Must be `https://`, or it is ignored. |
| `LGT_SUPPORT_ENABLED_NON_PRODUCTION` | No       | `true` to allow tickets from staging/development sites. Their subjects are prefixed `[STAGING]` or `[DEV]`.                       |
| `LGT_SUPPORT_FALLBACK_EMAIL`         | No       | Shown to users when a ticket can't be delivered.                                                                                  |

The values are read from `$_ENV`, `$_SERVER`, or `getenv()`, in that order, so they can also be set as real server environment variables.

**Non-production sites send nothing by default.** Staging sites are often copies of live, `.env` included, so they would otherwise create real tickets against the client. Without a key the form and the toolbar button are hidden.

**Dashboard > LGT Toolkit > Support Settings** (`/dashboard/lgt_toolkit/support`) shows which settings are populated (the key is shown only as `••••••••`), whether the feature is enabled, and the queue's health. It has no "test key" button: the portal has no validation endpoint, and every request creates a real ticket.

#### Retry queue and scheduling

Queued tickets are sent **only** by the Concrete task **Send queued support tickets** (`lgt_support_retry`), run on a schedule set in the dashboard. Nothing else retries them: there is no "Retry now" button and no separate cron command. The task is registered when the package is installed or updated, so run **Update** after upgrading.

1. Under **Dashboard > System & Settings > Automation > Tasks**, open **Send queued support tickets**, tick **Schedule**, and enter `*/5 * * * *` (every 5 minutes).
2. Make sure the server runs Concrete's scheduler every minute (Plesk: **Scheduled Tasks**). Scheduled tasks run inside this process, so no messenger worker is needed:

    ```
    * * * * * cd /path/to/project && vendor/bin/concrete concrete:scheduler:run >> ~/logs/<site>/scheduler.log 2>&1
    ```

    Use the same PHP as `resources/deploy.sh` (phpenv shims or the Plesk PHP in `/opt/plesk/php/*/bin`) if `php` isn't on the cron `PATH`. Scheduling must be on (`concrete.jobs.enable_scheduling`, the default).

**Dashboard > LGT Toolkit > Support Settings** shows the task's schedule and warns if there is none. **Dashboard > Support** warns when tickets are waiting and the task isn't scheduled, or when the oldest one is more than 30 minutes overdue.

- Retries back off: 5 min, 15 min, 30 min, 1 h, then hourly. Items still pending after 72 hours are marked failed. Failed items are purged after 30 days.
- A run sends at most 10 items and stops early if the portal rejects the site key or rate-limits the site. Runs take a Concrete mutex, so they never overlap.
- The task fails visibly (in the scheduler log) if `LGT_SUPPORT_API_KEY` isn't available to the CLI.
- **Dashboard > Support** lists queued and failed requests, and failed ones can be discarded.

A ticket that times out or gets a 5xx may already have reached the portal. Its retry carries a delivery note with the local reference (`Q-YYYYMMDD-XXXX`) so the helpdesk can merge duplicates. A ticket is never sent again after the portal has accepted it.

#### What is sent

The user's description, category, summary, email, name, optional phone and page, plus up to 5 PNG/JPEG/WebP/PDF attachments (7 MB each, 12 MB in total). Diagnostics are appended to the message:

- site name, canonical URL and environment
- the page (ID, version, path, name), only if the user can view it. A specific (e.g. unapproved) version is only used if the user can view page versions; otherwise the live version is reported
- the user's ID, username and groups
- Concrete, PHP and installed package versions
- the browser's user agent
- the latest 15 errors from the core `Logs` table, with emails, passwords, tokens and card-like numbers removed

Config, environment variables, cookies, session data and credentials are never included. Attachments are never written to the File Manager, and are not kept once the ticket is delivered.

#### Local testing

In development mode `LGT_SUPPORT_URL` may be `http://localhost/...` or `http://127.0.0.1/...`, so you can point it at a tiny mock script that returns the status you want to test, for example:

```php
<?php // public/mock-portal.php — returns 201 unless ?status= is given
http_response_code((int) ($_GET['status'] ?? 201));
header('Content-Type: application/json');
echo json_encode(['ticket' => 1234, 'reference' => '#1234', 'url' => 'http://localhost/mock-portal.php?id=1234']);
```

The portal answers `201 {"ticket": 1234, "reference": "#1234", "url": "https://portal.limegreentangerine.co.uk/ticket.php?id=1234"}`. Any other status is handled as described above: a 5xx is retried and flagged as a possible duplicate, because the ticket may already exist.

Delete the mock when you're done.

### Package services and integrations

The package registers supporting services for common application needs:

- `autocache` for automatic cache management
- `focal_point` for image focal point handling
- `lgt_mail` for custom mail handling
- `express_debugger` for Express debugging support

It also registers routes for:

- duplicate Express object conversion
- LGT AJAX file/page listing endpoints
- focal point dialog handling
- cookie policy AJAX endpoints

## Installation

1. Add the package to your Concrete project with Composer:

    `composer require limegreentangerine/lgt_toolkit`

2. Install the package in the Concrete CMS dashboard or via the package manager workflow.

3. Once enabled, the package will automatically create the dashboard pages, blocks, attributes, and configuration.

## Development notes

This package is intentionally opinionated and is meant to provide a shared baseline for multiple Concrete CMS sites. It centralises commonly reused configuration, block patterns, and attribute definitions so projects can start from a predictable, production-friendly standard.

## Slideshow helper

The package includes a reusable slideshow utility in `src/Slideshow` for building slide configurations and exposing the generated option payload for front-end rendering. It supports either a list of string slides or an array of slide objects, and provides the default viewport sizing, snap behaviour, autoplay, and button controls used across site builds.

### Example

```php
use LgtToolkit\Slideshow\Slideshow;

$slides = [
    ['title' => 'One', 'image' => '/images/one.jpg'],
    ['title' => 'Two', 'image' => '/images/two.jpg'],
];

$slideshow = new Slideshow($slides, [
    'mobile' => 1,
    'desktop' => 2,
    'hd' => 3,
    'draggable' => true,
    'snap' => 'center',
    'autoplay' => [
        'enabled' => true,
        'speed' => 6,
        'useTimer' => true,
    ],
]);

$options = $slideshow->getOptions();
$styleVars = $slideshow->generateStyleVariables();
```

### Supported settings

The slideshow settings array supports the following options:

- `mobile`, `desktop`, `hd`: visible slides per breakpoint
- `draggable`: whether drag/swipe interaction is enabled
- `snap`: snap alignment (`none`, `start`, `center`, `end`)
- `gap`, `peek`: per-breakpoint spacing values
- `showButtons`, `showPagination`: per-breakpoint visibility toggles
- `useTheme`: whether the theme stylesheet should be used
- `prevIcon`, `nextIcon`: icon classes for navigation buttons
- `autoplay`: nested settings for enabled state, speed, and timer usage
- `template`: optional package template metadata

The utility exposes helper methods for the generated stylesheet, theme stylesheet, JavaScript asset, options payload, and CSS variable string, which makes it easy to render custom slide components in a Concrete CMS view layer.

## Testing

The package uses PHPUnit for regression coverage. Tests live under `tests/` and use a lightweight bootstrap for package code that does not require a running Concrete CMS site.

Coverage includes slideshow settings and rendering, AJAX cookie and placeholder responses, mail template rendering, social-share links, focal-point and redirect values, attribute and block behavior, dashboard output, and the responsive picture override.

Run the suite with:

- `composer test`
- `composer test-coverage`

Run `composer test` after making changes to verify the suite. Use `composer test-coverage` to print a coverage report when the required coverage driver is available.

## License

MIT

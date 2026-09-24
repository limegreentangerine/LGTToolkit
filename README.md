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

These blocks provide content patterns and UI elements commonly used across LGT sites, including page lists, navigation, buttons, sections, maps, video embeds, and social share utilities.

### Dashboard utilities

The package creates dashboard pages for common tasks:

- `/dashboard/lgt_toolkit`
- `/dashboard/lgt_toolkit/cookie_popup`
- `/dashboard/lgt_toolkit/uaccess`
- `/dashboard/lgt_toolkit/duplicate_express`

These pages support configuration for:

- cookie policy behaviour
- UAccess code
- duplicate Express object tooling

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

The package uses PHPUnit for regression coverage. Tests live under the `tests/` directory and cover its package helpers, file-focal metadata, and content utility classes.

Run the suite with:

- `composer test`
- `composer test-coverage`

These commands are intended for local validation when working on package changes or preparing a release.

## License

MIT

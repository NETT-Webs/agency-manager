# Architecture

## Bootstrap

`agency-manager.php` is the plugin's entry point. It defines the `AM_VERSION`/`AM_PLUGIN_DIR`/`AM_PLUGIN_URL`/`AM_PLUGIN_BASENAME` constants, requires `includes/autoload.php` (a PSR-4-style autoloader for the `AgencyManager\` namespace), registers `register_activation_hook`/`register_deactivation_hook` against `Activator`/`Deactivator`, and finally boots the plugin via `AgencyManager\Plugin::instance()`.

`uninstall.php` runs only when the plugin is deleted from the Plugins screen (never on deactivate), and removes only the plugin's own `am_settings` option — Talent, Location, Form, and Application/Submission content is left in place, since it consists of ordinary WordPress posts/taxonomies/meta, not plugin-specific tables.

## `includes/` layout

- **`cpt/`** — registers the `talent`/`location` custom post types and their taxonomies (`talent_category`, `talent_group`, `location_type`), meta boxes, and a `Registration_Guard` that checks whether a post type/taxonomy of the same name already exists (for example, one a theme registers) and defers to it instead of creating a conflicting duplicate.
- **`admin/`** — the admin-menu wiring (`class-admin.php`) and the single React admin-application mount point (`class-admin-app-page.php`) shared across every admin screen; also the classic Form Builder AJAX save handler and shortcode-reference data.
- **`rest/`** — the plugin's own `agency-manager/v1` REST API namespace: Dashboard stats, Talent/Location profile read/write, Applications, Forms, Import/Export, CSV Import, and Settings controllers, all gated through a shared `manage_options` capability check.
- **`csv-import/`** — the CSV Import pipeline: streaming CSV parsing, column-mapping suggestions, per-row validation/typing, duplicate matching (by ID, email, or title), taxonomy term resolution, image sideloading (`Image_Sideloader`, including optional WebP optimization of generated sub-sizes while the original upload is left untouched), and batch-based import execution — writing through the same REST controllers a manual Save in the admin editor uses, not a separate importer-specific writer.
- **`export-import/`** — the separate, versioned JSON import/export schema for migrating Talent, Locations, taxonomies, Forms, and settings between WordPress installations, matched by slug.
- **`forms/`** — the Form Builder's field-type registry, form schema storage (as post meta on the `am_form` post type), the public-facing form renderer, and the submission-to-Application review workflow.
- **`frontend/`** — the public-facing rendering layer: card/profile templates' supporting logic, the shortcode/Elementor-shared query builder, and carousel rendering.
- **`elementor/`** — registers the Talent/Location Grid/Carousel/Slider/Featured widgets and the two form widgets with Elementor, plus the shared style-control definitions (Widget Style Presets) those widgets use.
- **`shortcodes/`** — registers the public shortcodes (`[talent_grid]`, `[talent_featured]`, etc.) as thin wrappers around the same rendering logic the Elementor widgets use.
- **`compat/`** — theme/meta compatibility helpers (for sites whose theme already owns Talent/Location meta under a different key prefix).
- **`wizard/`** — the first-run Setup Wizard shown immediately after activation.

## Admin application (`src/` → `build/`)

Every admin screen (Dashboard, Talent, Locations, Applications, Forms, Form Builder, Website Display, Import/Export, CSV Import, Settings) is one React/Tailwind single-page application, built from `src/admin-app/` via `@wordpress/scripts`/webpack into `build/`. There is no client-side router — each WordPress admin-menu entry is still a real page load; a `screen` value localized from PHP tells the same compiled bundle which page component to mount inside a shared `Shell` (sidebar + top bar). React, ReactDOM, and `@wordpress/api-fetch` are externalized to WordPress's own bundled copies at build time — no CDN dependency.

## Data model

Talent and Location are ordinary WordPress custom post types. Profile fields are post meta; category/group/type relationships are ordinary WordPress taxonomies. Forms are stored as `am_form` posts with their field schema as post meta; a public submission becomes an `am_submission` post that the Applications workflow reviews and, on approval, uses to create or update a real Talent/Location post — the published record is a normal post from that point on, not a special "imported" or "converted" type. There are no custom database tables; everything is `wp_posts`/`wp_postmeta`/`wp_terms`/`wp_term_relationships`.

## REST API

Registered under the `agency-manager/v1` namespace on `rest_api_init`, with a shared base controller providing a `manage_options` permission callback used by every route. The React admin application authenticates via the standard WordPress REST nonce (`wp_rest`), the same mechanism any other WordPress admin screen uses — no separate authentication scheme.

## Elementor integration

Elementor is an optional dependency, detected at runtime (`elementor/loaded`) — none of the plugin's core functionality requires it, and every Elementor widget has an equivalent shortcode. When Elementor is active, the plugin registers its widgets into Elementor's own widget manager under an `agency-manager` category, using Elementor's native control system (including Elementor's own Typography/Border/Box-Shadow style-control groups) so the widgets present and behave like any other Elementor widget.

## Frontend rendering

Public-facing output (Talent/Location archive and single templates, grid/carousel/slider cards) is produced by a shared rendering layer used identically by the shortcodes, the Elementor widgets, and the theme template files in `templates/` — so a shortcode, an Elementor widget, and a theme override never diverge in markup for the same content.

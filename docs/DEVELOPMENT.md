# Development

## Prerequisites

- **WordPress**: 6.0 or later (declared in the plugin header's `Requires at least`).
- **PHP**: 7.4 or later (declared in the plugin header's `Requires PHP`). Verified during this project's own testing on PHP 8.2.
- **Node.js and npm**: needed only to rebuild the React admin application (`src/admin-app/`). Not needed to simply install and run the plugin, since the compiled `build/` output is already committed.

## Installing dependencies

```bash
npm install
```

This installs the packages declared in `package.json`: `@wordpress/scripts` (the build tool), Tailwind CSS and PostCSS (styling), React/ReactDOM, and a small set of Radix UI primitives and utility libraries used by the admin application's components.

## Build commands

These are the exact scripts defined in `package.json` — nothing here is invented:

```bash
npm run build
```

Runs `wp-scripts build` — a production build. Compiles `src/admin-app/` into:

- `build/index.js` — the bundled, minified admin application.
- `build/index.asset.php` — the WordPress script-dependency manifest (`wp-element`, `wp-api-fetch`, etc.) plus a content-hash version string, used by WordPress's own `wp_enqueue_script()` for cache-busting.
- `build/style-index.css` (and `build/style-index-rtl.css`) — the compiled Tailwind/component styles.

```bash
npm start
```

Runs `wp-scripts start` — a development build with file-watching, for local iteration on `src/admin-app/`.

## Where the build output is consumed

`includes/admin/class-admin-app-page.php` enqueues `build/index.js` and `build/style-index.css` directly (reading the dependency array and version out of `build/index.asset.php`), and outputs a single `<div id="agency-manager-root">` mount point. It never reads from `src/` at runtime — `src/` only matters when you're changing the admin application and need to rebuild.

## Working on PHP only

If you're only changing PHP (REST endpoints, CSV import logic, CPT/taxonomy registration, templates, shortcodes, Elementor widgets), no Node/npm build step is needed at all — just edit the files under `includes/` or `templates/` and reload.

## Running the plugin locally

1. Place this repository's contents at `wp-content/plugins/agency-manager/` in a local WordPress install.
2. Activate **Agency Manager** from the Plugins screen.
3. You'll be redirected to the Setup Wizard on first activation.

There is no bundled test suite or CI configuration in this repository at this time.

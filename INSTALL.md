# Installing NettWebs Talent & Location Management

## Requirements

- WordPress 6.0 or later
- PHP 7.4 or later (tested on 8.2 and 8.3)
- Elementor is optional — only required if you want to use the 10 Elementor widgets. Everything else (admin, shortcodes, forms) works without it.

## Install from the ZIP

1. Log into `wp-admin`.
2. Go to **Plugins → Add New → Upload Plugin**.
3. Click **Choose File**, select `nettwebs-talent-location-management-1.6.5.zip`, then click **Install Now**.
4. Click **Activate Plugin**.
5. You'll be redirected to the **Setup Wizard** automatically — no manual configuration is required.

No file editing, database import, or server configuration is needed. The plugin creates its own database option (`am_settings`) and two default forms on activation.

## First run

The Setup Wizard is three short steps:

1. **Agency Type** — Talent / Location / Casting / Model / Combined Agency. This only sets a sensible default Display Mode; it doesn't lock you into anything.
2. **Starter Content** — optionally creates a Talent page, a Locations page, and the two application form pages, each with the matching shortcode already inserted.
3. **Done** — links straight to the Dashboard.

You can skip content creation and add pages manually later; every shortcode and Elementor widget works identically regardless of how the page was created.

## Upgrading from 1.0.0

Upload and activate the new version the same way (WordPress will offer to replace the existing plugin). No database changes are required — your Talent, Location, Application, and Form data is untouched. Admin menu items have moved (Applications is now its own menu; Website Display replaces the old Display page) but nothing needs to be reconfigured.

## Uninstalling

Deactivating the plugin (**Plugins** screen) does not delete anything. Deleting it via **Plugins → Delete** removes only the plugin's own `am_settings` option — your Talent, Location, Application, and Form content is never deleted, so you can safely reinstall later without losing data.

## Troubleshooting installation

**"The uploaded file exceeds the upload_max_filesize directive."** — The ZIP is well under typical hosting limits (a few hundred KB); if you see this, your host's PHP upload limit is unusually low. Ask your host to raise `upload_max_filesize`, or unzip locally and upload the `agency-manager` folder via FTP to `wp-content/plugins/` instead.

**"Plugin file does not exist."** — Make sure you selected the ZIP itself, not the extracted folder, when using the Upload Plugin screen.

**Blank admin screen after activation** — check your host's PHP error log; this indicates a PHP version below 7.4 or a conflicting plugin defining the same class name (extremely unlikely — NettWebs Talent & Location Management uses its own `AgencyManager\` namespace throughout).

For usage documentation once installed, see README.md, ADMIN-GUIDE.pdf, and DEVELOPER-GUIDE.pdf.

# Rebrand: Agency Manager → NettWebs Talent & Location Management

This plugin was renamed from "Agency Manager" (slug `agency-manager`) to "NettWebs Talent & Location Management" (slug `nettwebs-talent-location-management`) in response to a WordPress.org Plugin Review Team PEND, which flagged the previous name as too generic / potentially confusable with an existing product.

## Changed

- Plugin display name (header `Plugin Name`, admin menu label, page titles)
- Plugin slug / folder name / main file name
- Text domain (`agency-manager` → `nettwebs-talent-location-management`)
- `readme.txt` header, `README.md`, `CHANGELOG.md`, `INSTALL.md`
- `Contributors:` in `readme.txt` (added `nettwebs` alongside the existing `salem`)

## Deliberately NOT changed (backwards compatibility)

The following are **public identifiers already stored in real site data** (Elementor page layouts, post content, database options). Renaming any of them would silently break every existing site the moment it updates — there is no WordPress.org requirement that forces this, so they are preserved as-is:

| Identifier | Example | Why it's preserved |
|---|---|---|
| Shortcodes | `[talent_grid]`, `[talent_featured]`, `[talent_carousel]`, `[talent_slider]`, `[talent_scouting]`, `[location_grid]`, `[location_featured]`, `[location_carousel]`, `[location_slider]`, `[location_scouting]`, `[talent_application_form]`, `[location_submission_form]`, `[agency_form]` | Typed directly into page/post content on real sites. Renaming breaks every page using them, silently, on update. |
| Elementor widget slugs | `am-talent-grid`, `am-location-carousel`, `am-agency-form`, etc. (11 widgets) | Stored inside `_elementor_data` post meta on every page that uses one of these widgets. Elementor resolves a widget purely by this string; an unknown string renders as a broken/missing widget. |
| REST namespace | `agency-manager/v1` | Any external code or saved request calling this namespace would break. Not part of the plugin's public trademark surface — WordPress.org's concern was the plugin *name*, not internal API routes. |
| Admin menu page slugs | `agency-manager`, `agency-manager-talent`, `agency-manager-csv-import`, etc. (used in `admin.php?page=...` URLs) | Internal query-string identifiers, not shown to end users as branding. Changing them breaks bookmarked admin URLs and every `admin_url('admin.php?page=...')` reference throughout the React admin app and PHP. |
| AJAX actions | `am_save_form_schema`, `am_save_widget_style_preset`, `am_rename_widget_style_preset`, `am_delete_widget_style_preset` | Already `am_`-prefixed (low collision risk); internal only, never user-facing. |
| Post meta keys | `_am_field_values`, `_am_form_fields`, `_am_source_url`, `_am_webp_optimized`, etc. | **Real stored data on real sites.** Renaming meta keys orphans every existing record. |
| Options | `am_settings` | Same reasoning — real stored configuration. |
| Post types / taxonomies | `talent`, `location`, `am_form`, `am_submission`, `talent_category`, `talent_group`, `location_type` | Real stored content types on real sites; renaming a post type effectively deletes/orphans all posts of that type from WordPress's perspective. |
| PHP namespace | `AgencyManager\` | Internal-only, never exposed to end users, not part of the plugin's public identity. Renaming touches every file for zero WordPress.org compliance benefit. |
| `AM_*` constants | `AM_VERSION`, `AM_PLUGIN_DIR`, etc. | Same reasoning as the PHP namespace — internal-only. |
| CSS/JS class prefix | `am-*` (327 occurrences) | Internal styling hooks only, no trademark exposure, no collision risk beyond the plugin's own DOM. |
| Elementor widget category slug | `agency-manager` (groups the widgets in Elementor's panel) | Organizational only — not stored per-widget-instance in page data, so this one *could* safely change, but was left as-is since it carries no compliance requirement either way. |

## If WordPress.org specifically requires any of the above to change

None of this project's remediation required it — the review's five concrete code-level findings (file upload sanitization, JSON validation ×2, menu position, `load_plugin_textdomain()`) and the naming/slug concern were all addressed without touching any of the identifiers in the table above. If a future review explicitly requires one of them to change, the correct approach is an aliasing/migration layer (e.g., registering both the old and new identifier, or a one-time upgrade routine), not a silent rename — this preserves every existing installation's saved content.

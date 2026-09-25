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
| PHP namespace | `AgencyManager\` | *Superseded in v1.7.0* — a later review specifically flagged this as too generic; see "v1.7.0" section below. |
| `AM_*` constants | `AM_VERSION`, `AM_PLUGIN_DIR`, etc. | *Superseded in v1.7.0* — same review; see "v1.7.0" section below. |
| CSS/JS class prefix | `am-*` (327 occurrences) | Internal styling hooks only, no trademark exposure, no collision risk beyond the plugin's own DOM. |
| Elementor widget category slug | `agency-manager` (groups the widgets in Elementor's panel) | Organizational only — not stored per-widget-instance in page data, so this one *could* safely change, but was left as-is since it carries no compliance requirement either way. |

## If WordPress.org specifically requires any of the above to change

A second review (v1.7.0) *did* specifically flag the PHP namespace and the `AM_*`/`am_*` internal-identifier prefix as too generic/short (WPCS `PrefixAllGlobals`). Two rows in the table above were superseded as a result — see below. Everything else in the table remains unchanged and still applies for the same reasons.

## v1.7.0: PHP namespace, constants, and internal hook names

Per the second review's explicit requirement, three internal-only identifier categories were migrated to a genuinely unique `nettalo`/`Nettalo\` prefix, each with a backward-compatible alias so nothing already deployed (a theme's own compat code, a site-specific mu-plugin) breaks:

| Identifier | Before | After (primary) | Backward compatibility |
|---|---|---|---|
| PHP namespace | `AgencyManager\` | `Nettalo\TalentLocationManagement\` | None provided — a bare namespace has no PHP-native aliasing mechanism, and no code path in this plugin, a theme, or a known integration is documented as referencing these implementation classes directly by their fully-qualified name (unlike hooks/options/meta keys, which are genuinely public APIs). |
| Global constants | `AM_VERSION`, `AM_PLUGIN_FILE`, `AM_PLUGIN_DIR`, `AM_PLUGIN_URL`, `AM_PLUGIN_BASENAME` | `NETTALO_VERSION`, `NETTALO_PLUGIN_FILE`, `NETTALO_PLUGIN_DIR`, `NETTALO_PLUGIN_URL`, `NETTALO_PLUGIN_BASENAME` | The old `AM_*` constants remain `define()`d with identical values in the main plugin file, explicitly marked as deprecated compatibility aliases. |
| 14 internal hooks (filters/actions) | `am_form_submission_allowed`, `am_submission_created`, `am_form_fields`, `am_talent_query_args`, `am_location_query_args`, `am_settings_defaults`, `am_field_library`, `am_form_templates`, `am_notification_recipient`, `am_submission_status_changed`, `am_submission_published`, `am_meta_fallback_map`, `am_before_import_section`, `am_after_import_section` | Same names, `nettalo_` prefix (e.g. `nettalo_form_submission_allowed`) | Every site fires the new `nettalo_*` hook first, then fires the original `am_*` name via WordPress core's own `apply_filters_deprecated()`/`do_action_deprecated()` — this only actually invokes registered callbacks on the old name (via `has_filter()`/`has_action()` internally), so a site with nothing hooking the old name pays no cost, and a site that IS hooking it (e.g. the Eden Cast theme's `agency-manager-compat.php`, which hooks `am_meta_fallback_map`) keeps working unmodified. |

Everything else that was internal-only in the original table (`am-*` CSS/JS classes, the Elementor widget category slug) was left unchanged — WPCS's prefix sniff does not flag CSS class names or Elementor's own internal category-grouping string, so there was no compliance reason to touch them.

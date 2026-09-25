# Changelog

All notable changes to NettWebs Talent & Location Management (formerly "Agency Manager") are documented in this file.

## [1.7.0] — 2026-09-23

Second corrective release addressing WordPress.org Plugin Review Team feedback (internationalization, input sanitization/validation, output escaping, and generic global-identifier prefixing).

### Changed

- **Internal PHP namespace** moved from `AgencyManager\` to `Nettalo\TalentLocationManagement\` across the entire codebase — the primary implementation namespace per review feedback that `AgencyManager\` wasn't a sufficiently unique/plugin-specific prefix. No public identifier (shortcodes, REST namespace, admin page slugs, Elementor widget slugs, meta keys, the `am_settings` option, post types, taxonomies, AJAX actions) changed as a result — see `docs/REBRAND.md`.
- **Internal PHP constants** `AM_VERSION`, `AM_PLUGIN_FILE`, `AM_PLUGIN_DIR`, `AM_PLUGIN_URL`, `AM_PLUGIN_BASENAME` replaced as the primary implementation constants by `NETTALO_VERSION`, `NETTALO_PLUGIN_FILE`, `NETTALO_PLUGIN_DIR`, `NETTALO_PLUGIN_URL`, `NETTALO_PLUGIN_BASENAME`. The old `AM_*` names remain defined as deprecated compatibility aliases (identical values) for any external code that referenced them.
- **14 internal hook names** (`am_form_submission_allowed`, `am_submission_created`, `am_form_fields`, `am_talent_query_args`, `am_location_query_args`, `am_settings_defaults`, `am_field_library`, `am_form_templates`, `am_notification_recipient`, `am_submission_status_changed`, `am_submission_published`, `am_meta_fallback_map`, `am_before_import_section`, `am_after_import_section`) now fire under a `nettalo_`-prefixed primary name, with the original `am_*` name still fired via `apply_filters_deprecated()`/`do_action_deprecated()` for any existing integration (including a theme's own `agency-manager-compat.php`) that hooks the old name — see `docs/REBRAND.md`.
- Remaining 25 uses of the old `agency-manager` text domain in template files replaced with the plugin's actual slug/text domain, `nettwebs-talent-location-management`.

### Security

- `am_form_submission_allowed` no longer receives raw, unsanitized `$_POST` — it now receives every visible field's value already sanitized per its field type (file/image fields are sanitized, and only uploaded, after this filter allows the submission).
- Added `json_last_error()` validation to the two JSON-payload AJAX endpoints (Form Builder save, Elementor widget style presets) that were decoding client-supplied JSON without checking for decode failure.
- Fixed 1 genuine unescaped-output case (an attachment thumbnail rendered via a variable, which broke static-analysis recognition of `wp_get_attachment_image()`'s already-safe output) and hardened file-upload superglobal access (`$_FILES`) with explicit `isset()` guards and per-field sanitization throughout the form submission and JSON/plugin-data import paths.

## [1.6.5] — 2026-09-14

Corrective release addressing WordPress.org Plugin Review Team feedback on the 1.6.4 submission.

### Changed

- **Renamed** from "Agency Manager" to "NettWebs Talent & Location Management" (plugin name, slug, folder, main file, text domain) to establish a distinctive plugin identity, per review feedback that the previous name was too generic. Only the plugin's own identity strings changed — every shortcode, Elementor widget identifier, REST route, AJAX action, and stored meta/option key is unchanged (see `docs/REBRAND.md` for the full list of what was and wasn't touched, and why). Existing Elementor pages, shortcode usage, and stored data all continue to work unmodified.
- Moved the top-level admin menu from position 25 (which collided with WordPress core's own menu position) to 58.5, below core's menu block.
- Removed the manual `load_plugin_textdomain()` call — no longer necessary now that the plugin's text domain exactly matches its WordPress.org slug (WordPress has auto-loaded translations for that case since 4.6).

### Security

- The public Talent Application / Location Submission form file-upload handler now explicitly sanitizes the client-supplied filename (`sanitize_file_name()`) and no longer relies implicitly on a downstream WordPress core function to make that safe — the client-supplied file *type* is likewise never trusted for any decision; the real type continues to be determined the same way it always was, via WordPress's own upload validation (`wp_handle_upload()`'s `wp_check_filetype_and_ext()`).
- The Form Builder's save handler and the Elementor Widget Style Preset save handler both now validate their JSON payload's structure field-by-field (each expected key checked against its real type/enum) rather than only checking that the decoded JSON is an array. Malformed or unexpected values are safely normalized to a known-good default instead of being stored verbatim.

## [1.6.4] — 2026-08-26

Production hardening and release-cleanup pass over the CSV Import wizard's image pipeline, re-verified end-to-end through the real admin UI at scale (100 Talent, ~400 images) — no changes to Talent/Location data structures, REST contracts, Elementor widgets, Form Builder, shortcodes, or Import/Export.

### Added

- **WebP optimization for CSV-imported images**: after an image is downloaded from a CSV row's Featured Image/Gallery URL, WordPress's own image editor (GD or Imagick, whichever the server has) re-encodes the *generated sub-sizes* (thumbnail, medium, and the active theme's card/profile sizes) as WebP. The original uploaded file is never touched — it stays in its original JPG/PNG format for compatibility with tools and integrations that expect the original file to be unchanged. If the server's image library can't produce WebP, the import proceeds normally with no optimization step; nothing fails because of it. This only applies to images processed by the CSV importer — existing Media Library content is never converted retroactively.

### Fixed

- Two image-processing edge cases found during this release's verification pass, both isolated to the new WebP step above and both fixed before any release build: an early implementation could re-encode the full-size original instead of only its sub-sizes; and when two registered image sizes share one physical file (common for same-aspect-ratio sizes), only one is now converted and both size entries are safely repointed at it, rather than one entry being left pointing at a file that no longer exists.

### Verified (no code changes)

- CSV Import duplicate handling ("Update existing record" mode) re-confirmed to update in place with zero duplicate Talent created and stable attachment IDs when re-importing an unchanged CSV.
- Changing a single Talent's image URL and re-importing correctly updates only that Talent's image; all others are left untouched.
- A CSV row with a broken image URL no longer affects any other row in the same import batch.
- All 8 Talent/Location Elementor widgets (Grid, Slider, Carousel, Featured) render newly-imported WebP images correctly alongside existing, unconverted Media Library images, with no console errors.

## [1.6.0] — 2026-08-22

The Form Builder moves into the same React application shell as the rest of the admin — the last screen that still looked like a classic WordPress admin page now looks and behaves like the rest of Agency Manager. No form storage, schema, field registry, mapping logic, submission handling, or the Elementor Form widget were rebuilt — this release is a UI layer over the exact same backend 1.5.0 shipped.

### Added

- **Form Builder inside the app shell** (`Agency Manager → Forms → Edit`): the same sidebar/breadcrumbs/design system as every other screen, a three-panel workspace (searchable Field Library, Form Canvas, Field Settings), a Form Settings dialog (General/Application/Submission), and a save workflow with an explicit "Unsaved changes"/"All changes saved" indicator and a disabled Save button when nothing has changed.
- **Keyboard-accessible field adding**: every Field Library item has a "+" button alongside drag-and-drop, so adding a field never requires a mouse drag.
- A new read-only REST endpoint (`GET /forms/{id}/builder`) feeds the screen's initial data; saving still posts to the original `admin-ajax.php?action=am_save_form_schema` handler, nonce and all, unchanged.

### Fixed

- A field's mapping `destination` value of `both` (used by fields mapped to shared Talent/Location data since 1.0.0) is now a real, selectable option in the mapping editor instead of displaying blank/unavailable — no mapping behaviour changed, only its visibility while editing.

## [1.5.0] — 2026-08-22

A complete visual redesign of the admin application, plus a new CSV Import feature — both built as a layer over the exact same WordPress data model (custom post types, taxonomies, `_am_*` meta) every earlier release already used. No CPT/taxonomy registration changed, no meta keys renamed, no data migrated.

### Added

- **React/Tailwind/Radix admin application**: Dashboard, Talent, Locations, Applications, Forms, Website Display, Import/Export, and Settings are now one cohesive React application (`src/admin-app/`) instead of a series of classic WordPress admin-page screens, served from a new `agency-manager/v1` REST API that wraps the plugin's existing PHP business logic rather than reimplementing it.
- **Talent/Location add & edit screens**: a new two-column editor (main fields left; status/visibility/preview right) replaces WordPress's native post editor for Talent and Location on installs where this plugin owns those post types. Includes a Featured Image picker, an ordered Gallery manager (drag reorder, first image = card cover), taxonomy term selection, and a live Preview panel that renders the actual public card template — not a lookalike.
- **CSV Import** (`Agency Manager -> Import/Export -> CSV Import`): a guided Upload -> Map Columns -> Review -> Import -> Results wizard for bulk-importing Talent or Locations from a spreadsheet. Automatic column-mapping suggestions (by column name/alias), a searchable field-mapping list drawn from the same destinations the Form Builder already offers, per-row validation with inline errors/warnings, duplicate detection (match by ID, email, or an explicit name opt-in) with Create/Update/Skip handling that never overwrites a field the CSV didn't map, optional automatic taxonomy-term creation, optional image sideloading via WordPress's own secure media functions, saved/reusable column-mapping templates, and batched processing (25 rows/request) so large files never load into a single PHP request. Imported records are written through the exact same code path as the Talent/Location editor's own Save button — an imported Talent behaves identically to one created by hand or published from an Application.
- **Form Builder** restyled to match the new design system (three-column Field Library / Canvas / Settings layout, modernized spacing/colour/typography) — the underlying drag-and-drop JavaScript and save/load behaviour are unchanged.
- **Screen-specific shortcode reference**: the Talent, Locations, Forms, and Applications screens each show only their own relevant shortcodes (with search and one-click copy); the Dashboard continues to show the full reference.

### Changed

- Talent/Location REST field definitions now also include Contact Email, Contact Phone, and Notes — previously written only by the Application-publish workflow, now readable/writable consistently everywhere a Talent/Location record is edited or imported.
- The Form Builder's field-mapping destination list is now a single shared class (`Forms\Mapping_Targets`) also used by CSV Import, so both features always offer identical mapping destinations.

### Fixed

- Select-style profile fields (Availability, Parking, Power, Body Type) are no longer at risk of being silently blanked when a record whose real value predates this plugin's own option vocabulary (e.g. a theme-supplied value like "Mains Power") is re-saved without that field being touched.

## [1.4.0] — 2026-08-02

**Reverts the 1.3.0 architecture entirely** (and, with it, the parts of 1.2.0 that 1.3.0 hadn't already undone) — after review, the Scouting Campaign feature and the Hidden/Live-only Display Mode were more change than intended. Website Display and Scouting Mode are back to exactly their pre-1.2.0 behaviour: Display Mode is Hidden/Now Scouting/Live again (all three, global, shared by Talent Grid/Carousel/Slider/Featured exactly as before), `[talent_scouting]`/`[location_scouting]` always render placeholder cards again, and the Scouting Campaign admin section/repeater, its Import/Export section, and its shortcode-reference wording are all removed. `Frontend\Meta_Resolver` and the `am_meta_fallback_map` filter (from 1.2.0) are unaffected — they're unrelated to Scouting and remain in place.

### Removed

- The Scouting Campaign section (Website Display → Scouting Campaign), its repeater UI and JS, its `scouting_campaign` settings key, and its Import/Export section.

### Added

- **Multiple Scouting Images**: the Placeholder Manager's single Image field is now a multi-image picker ("Scouting Images") — select as many images as you like via the existing media library button. Placeholder cards cycle through them in order (card 1 → image 1, card 2 → image 2, ... wrapping back to image 1 once every image has been used); a single image is used on every card; zero images shows the original plain placeholder block, exactly as before.

## [1.3.0] — 2026-08-02

**Reverted in 1.4.0** — the Scouting Campaign feature and Display Mode change described below were reverted after review; Scouting Mode and Website Display are back to their original (pre-1.2.0) behaviour, plus a much smaller "Multiple Scouting Images" enhancement. See the 1.4.0 entry above.

Scouting Mode redesigned as an independent recruitment campaign feature, superseding 1.2.0's approach. Talent/Location browsing (Grid, Carousel, Slider, Featured, Directory, Single, Search/Filtering) is completely unaffected by this release — this only changes Scouting.

### Changed

- **Scouting is no longer Talent/Location content.** 1.2.0 made Scouting Mode show real Talent/Location posts with a badge; testing surfaced this as confusing (a recruitment section that just duplicated the existing roster). Scouting Mode is now a fully independent marketing/recruitment feature with its own admin-configured cards, entirely decoupled from the Talent/Location post type — it queries nothing (`Carousel_Renderer::render_scouting_cards()` never touches `WP_Query`).
- **The global per-type Display Mode setting no longer includes "Now Scouting."** It now only has **Hidden** and **Live** (`Settings::get_display_mode()`). Talent Grid/Carousel/Slider/Featured/Directory/Search/Filtering, when left on "Auto," always show real content (or nothing, if Hidden) — the global setting can no longer silently switch them over to recruitment content. Scouting only ever appears via `[talent_scouting]`/`[location_scouting]`, or any shortcode/widget instance with its own `mode` explicitly set to Scouting (e.g. `[talent_grid mode="scouting"]`, or an Elementor widget's "Force Now Scouting" option) — an explicit, per-instance opt-in, never inherited. A pre-existing site with a stored legacy "scouting" value for the global setting now reads as Live.

### Added

- **Scouting Campaign** (`Agency Manager → Website Display → Scouting Campaign`, one section per type): a repeater where an admin adds as many recruitment cards as wanted, each with an Image, Badge Text (default "Now Scouting"), Title, Description, Button Text (default "Apply Now"), and a Button Link that's either a WordPress page (picked from a dropdown) or a custom URL. Cards can be added and removed freely; order follows the order they're arranged in on the page. `[talent_scouting]`/`[location_scouting]` and any widget/shortcode instance explicitly set to Scouting Mode render these cards — same existing scouting card design and "Now Scouting" badge placement, just backed by admin-configured data instead of Talent posts. The button always goes to the configured application page/URL, never a Talent profile.
- **Backward-compatible fallback**: if no Scouting Campaign cards have been added yet for a type, Scouting Mode automatically falls back to the existing single-config Placeholder Manager cards, repeated — exactly the pre-1.2.0 "coming soon" behaviour, so a standalone install works immediately after activation with zero setup.
- **Placeholder Manager's Title/Description fields are now actually used.** These fields have existed in the admin UI since the Placeholder Manager was introduced, but the placeholder-card template silently ignored them, always showing hardcoded "Professional Talent"/"Applications Open" text instead. Fixed as part of this release (the same template is shared with Scouting Campaign cards) — an admin's existing Title/Description values now render, with the original hardcoded strings kept only as the ultimate fallback if a field is left blank.
- **Scouting Campaign in Import/Export**: a new "Scouting Campaign" item under the Settings group, included in Export Content/Export Everything. Because cards are a plain ordered list with no name/slug identity (unlike Widget Style Presets), import replaces a type's whole card list wholesale rather than merging index-by-index — avoiding a subtle bug where merging two differently-sized card lists by position would splice unrelated cards together.

## [1.2.0] — 2026-08-02

**Superseded by 1.3.0, then reverted entirely in 1.4.0** — the Scouting Mode approach described below (real Talent/Location posts with a badge) was replaced by an independent Scouting Campaign feature after testing showed it was confusing, and that in turn was reverted in 1.4.0 back to the original placeholder-card behaviour. The meta compatibility layer (`Meta_Resolver`) below is unaffected by either change and remains in place.

Scouting Mode architecture change, plus a new theme-agnostic meta compatibility layer.

### Changed

- **Scouting Mode now shows real content first.** `[talent_scouting]` / `[location_scouting]`, the site-wide "Now Scouting" Display Mode setting, and every Elementor widget's "Force Now Scouting" option now query the site's own active Talent/Location posts and render them through the normal card template — real featured image, real name, real category/group, real city — with the "Now Scouting" badge layered on top of the card (`Frontend\Card_Renderer::render_talent_card()`/`render_location_card()` now accept an optional `$badge` argument that adds it). Placeholder ("coming soon") cards are shown only as a fallback, when zero real posts match — so a brand-new install with no roster yet keeps behaving exactly as before, while an install with existing content (e.g. an agency's existing Talent roster) now shows that real content in its scouting sections instead of generic filler. This is a behaviour change from 1.1.0, not a bug fix — no data changed, no post types/taxonomies changed, and the Placeholder Manager settings (image/badge/button text) are unaffected, since the badge text still comes from that same configuration.

### Added

- **Meta compatibility layer** (`Frontend\Meta_Resolver`): when Agency Manager's own `_am_*` meta is empty for a field, a new `am_meta_fallback_map` filter lets another system (typically the active theme, if it already owns Talent/Location content with its own meta prefix) declare a fallback meta key for that field — read-only, no migration, no data duplicated, and completely inert until something hooks the filter. Applied at every `_am_*` read in card rendering (`talent-card.php`, `location-card.php`) and the default single templates (`single-talent.php`, `single-location.php`), plus gallery-image lookup in `Card_Renderer::get_card_image_id()`. Agency Manager itself declares zero mappings and contains no references to any specific theme; Eden Cast's own mapping (`_ec_age`, `_ec_city`, `_ec_measurements`, etc.) is hooked from the theme's own `wp-content/themes/eden-cast/inc/agency-manager-compat.php`, not from the plugin.

## [1.1.0] — 2026-08-02

UX/information-architecture redesign: Agency Manager now feels like one coherent product (in the spirit of WooCommerce's "Products," not raw custom post types) rather than a set of separate screens bolted onto Talent/Location CPTs. No architectural foundations changed — the Registration Guard, OOP structure, template overrides, Import/Export, and Elementor integration are unchanged and fully preserved; this release only reorganizes and extends the admin UI and the frontend query/shortcode/widget surface.

### Added

- **Applications**: split out of Forms into its own top-level admin screen, listing submissions across *all* forms of a type (Talent/Location tabs) rather than one form at a time — the same reviewed, tested workflow logic (Submitted → Review → Approved → Published/Rejected/Archived), just organized around what's being reviewed instead of which form it came through. Approved submissions now also get a direct "Edit Profile" link once published.
- **Website Display**: a single new screen consolidating what used to be split across the Display page and half of Settings — Display Mode, the Placeholder Manager ("Now Scouting" cards), and Homepage Control, all per type, in one place. Same settings keys underneath; no data changes on upgrade.
- **Tabbed Add Talent editor**: General / Profile / Measurements / Media / Gallery / Social Links / Visibility / Preview, replacing the previous single long meta box + separate Visibility box. No raw WordPress fields exposed beyond the tabs.
- **Tabbed Add Location editor**: General / Property Details / Gallery / Facilities / Availability / Map / Visibility / Preview.
- **Social Links** (Talent): Instagram, Facebook, TikTok, Website/Portfolio URL fields.
- **Preview tab**: a static, server-rendered preview of the Talent/Location card using the exact frontend `Card_Renderer` template — single source of truth, no duplicated markup, no live/AJAX preview. Refreshes only on Save/Update, with an explicit "Save or Update this entry to refresh the preview" notice.
- **Dashboard**: new Approved Applications tile and a Quick Actions block (+ Add Talent, + Add Location, + View Applications, + Website Display).
- **Shortcode parameters**: `category` and `group` (Talent), `type` (Location), `columns`, `only_featured`, `only_active`, `order` (`newest`/`oldest`/`random`) — added to every grid/carousel/slider/featured shortcode.
- **Elementor widget controls**: taxonomy-aware Category/Group dropdowns (Talent) or Type dropdown (Location) populated live from `get_terms()`, replacing the old free-text slug field; new Only Featured, Only Active, and Sort Order controls.
- **Query**: `Frontend\Query` now supports simultaneous Category + Group filtering for Talent (AND), Type filtering for Locations, `only_active`, and three sort orders.
- **Elementor Style tab controls**: every Grid/Carousel/Slider/Featured widget's Style tab now has six full groups of native Elementor visual controls — **Image** (aspect ratio, optional custom height, border radius, object fit), **Card** (background, border colour/width/radius, padding, a named shadow preset — None/Small/Medium/Large), **Button** (width Auto/Full, height, border radius, text size, background/text/border colour, separate hover background/text colour), **Badge** (background, text, border colour for the "Now Scouting" badge), **Typography** (Title/Subtitle/Description, using Elementor's native Typography group control), and **Spacing** (Card Gap, Internal Padding, Button Margin). Every control uses Elementor's own `selectors` mechanism — CSS is generated by Elementor itself, scoped to that one widget instance only; nothing is hardcoded in the plugin and no CSS/PHP editing is needed for common layout/styling changes. The previous single "Card Border"/"Card Shadow" group controls are replaced by these more granular, non-technical-friendly equivalents.
- **Shortcode Reference panel**: every shortcode — grouped as Talent, Locations, and Forms — is now listed with its description, parameters, and a one-click Copy button (with a "Shortcode copied." confirmation) on **Agency Manager → Dashboard**, and the same panel (scoped to the relevant group) also appears on the native Talent and Locations list screens and on the Forms/Applications admin pages, so a non-technical user can discover and copy any shortcode without reading documentation.
- **Interactive Shortcode Builder**: every shortcode with parameters now has a live "Build it" form (Limit, Columns, Category, Group, Featured Only, Active Only, Order By, Display Mode, matched to the right input type) directly below its description — the exact shortcode text updates as each field changes, with its own Copy button, so a parameterised shortcode never has to be typed by hand.
- **Shortcode search**: a search box at the top of the panel filters by tag/description as you type (e.g. "featured" or "carousel"), hiding non-matching group headings automatically.
- **Richer per-shortcode help**: each entry now explains **What it does**, **When to use it**, and its **Available parameters** (previously description + parameters only).
- **Common Examples**: the former 4-row "Common Placements" table is now a 6-row **Common Examples** table (Homepage Featured Talent, Homepage Featured Locations, Full Talent Page, Full Location Page, Now Scouting Talent, Now Scouting Locations), each with its own Copy button.
- **"Reset to Theme Defaults" button**: every Elementor grid/carousel/slider/featured widget's Style tab now opens with a Reset button that restores every Style control back to its registered default — scoped to the one widget instance being edited only, via Elementor's own Backbone settings model (`model.setSetting()`); no other widget, page, or the theme's CSS is ever affected. Implemented with a `Controls_Manager::BUTTON` control firing a custom event on Elementor's editor channel, handled by a small editor-only script (never loaded on the public-facing site).
- **Widget Style Presets**: a new "Widget Style" section at the very top of the Style tab — Preset dropdown, Preset Name field, and Load/Save Current/Rename/Delete buttons — lets a full snapshot of every Style-tab control (Image/Card/Button/Badge/Typography/Spacing) be saved under a name (e.g. "Luxury Cards," "Minimal") and loaded onto any other widget instance, of any type, so a widget only ever needs to be styled once. "Load" is instant (the full preset list, values included, is localized to the editor script once per page load — no AJAX round-trip); "Save Current"/"Rename"/"Delete" persist via three new `wp_ajax_am_*_widget_style_preset` handlers (`Elementor\Widget_Style_Presets`, `manage_options`-gated, nonce-verified), storing presets keyed by name as one slice of the existing `am_settings` option. New presets/renames/deletions update the live dropdown immediately, without an editor reload.
- **Widget Style Presets in Import/Export**: a new "Widget Style Presets" section under the Settings group, included in Export Content/Export Everything, so another Agency Manager installation can immediately reuse the same widget styles. Imported presets are merged by name (upsert) — a same-named preset is updated, a differently-named existing preset is left untouched, exactly like every other named entity this plugin imports.
- **Theme-aware frontend styling**: Agency Manager now cleanly separates data, rendering, and styling. Every card/template reads a small set of CSS custom properties (`--am-primary`, `--am-secondary`, `--am-accent`, `--am-text`, `--am-heading`, `--am-button`, `--am-border`, `--am-radius`) with neutral, plugin-provided fallbacks in `frontend.css` — the active theme defines its own look by declaring these variables (and, for typography/spacing/hover motion, styling Agency Manager's plain class names directly) in its own stylesheet. Agency Manager ships **no colours, fonts, or branding for any specific agency** — not even Eden Cast, whose maroon/gold/serif visual identity now lives entirely in `wp-content/themes/eden-cast/assets/css/talent-locations.css`, styling the plugin's unbranded output the same way any other plugin's markup would be styled. There is no style dropdown, shortcode attribute, or admin setting to select an appearance — the previously-dead, non-functional "Card Style" Elementor control has simply been removed.
- **Archive/single template overrides**: `Frontend\Template_Loader` provides default `archive-talent`, `archive-location`, `single-talent`, `single-location` templates via the standard `template_include` filter pattern (matching how WooCommerce/EDD provide their own CPT templates) — used only as a last resort when neither the active theme's own conventionally-named template nor an `agency-manager/{name}.php` override folder provides one. A theme that already owns these views (like Eden Cast) sees zero change in behaviour.
- **`limit` shortcode attribute**: every grid/carousel/slider/featured shortcode now accepts `limit` as the preferred way to control card count (e.g. `[talent_featured limit="5"]`, `[location_featured limit="5"]`) — ideal for showing a specific number of cards on the homepage. `count` remains a working alias for backward compatibility; if both are supplied, `limit` wins.
- **`[talent_scouting]` / `[location_scouting]` shortcodes**: always render the "Now Scouting" placeholder cards, bypassing the global Display Mode and the database query entirely — for placing a scouting/teaser section anywhere on the site regardless of what Live/Now Scouting/Hidden is currently set globally. Accept `limit` and `columns`.
- **Elementor Display Mode control relabeled and clarified**: "Inherit" is now "Auto (use global Display Mode)" and "Now Scouting" is now "Force Now Scouting" — same underlying values, clearer intent, with an inline description explaining each option. "Force Now Scouting" is the widget-level equivalent of the new scouting shortcodes.
- **Elementor Featured widgets gained full manual control**: switching off "Use Homepage Settings" on a Featured Talent/Location widget now exposes the exact same Category/Group/Type, Only Featured, and Sort Order controls that non-featured widgets already had — previously these were only available on Grid/Carousel/Slider widgets, so a hand-configured Featured widget couldn't filter by category or change sort order. The "Number of Items" control is relabeled "Number of Cards" for clarity.
- **Import/Export reorganized** around its actual purpose — moving reusable agency content (Talent, Locations, their taxonomies, and Display Settings) between installations, not migrating a whole website. Sections are now grouped as **Content** (Talent, Locations, Talent Categories, Talent Groups, Location Categories), **Settings** (Website Display Settings, Homepage Featured Settings, Plugin Settings), and **Optional** (Forms). Four Quick Action buttons replace the old single "select everything" checkbox: **Export Talent** (Talent only), **Export Locations** (Locations only), **Export Content** (Talent + Locations + all four taxonomies + Website Display + Homepage Featured Settings), and **Export Everything** (Export Content, plus Forms and Plugin Settings). Forms is never included by default in Export Content or Export Everything's Content portion — it's always an explicit, separate opt-in, since form wording/branding/workflow is usually customised per agency rather than reused across sites. On Import, the Forms checkbox defaults unticked; every other section still defaults ticked (only sections actually present in the uploaded file are ever applied). No changes to the underlying export/import/schema code were needed for this — Talent/Location field coverage (galleries, videos, social links, measurements, visibility flags), slug-based matching, and settings merge-on-import were already correct; this only changes which sections the UI groups together and what each Quick Action selects.

### Changed

- `location_type` taxonomy admin label changed from "Location Types"/"Location Type" to "Categories"/"Category" (matches the new Locations submenu wording). The taxonomy key (`location_type`) is unchanged — cosmetic only, no data migration.
- Setup Wizard's "Both Talent & Locations" option relabeled "Combined Agency" (value unchanged).
- Settings screen trimmed to Agency Type, Notification Email, and Backup & Restore — homepage control moved to Website Display.
- Forms screen trimmed to form/field definition management — submission review moved to Applications.
- The location grid/carousel/slider/featured shortcodes' old generic `term` attribute is replaced by `type`.

### Fixed

- `Dashboard_Page::get_recent_activity()` and `Notifications::notify()` both linked to an admin route (`agency-manager-forms&view=submission&submission_id=...`) that was never actually implemented. Both now link to the new Applications screen, correctly routed to the Talent or Location tab.

## [1.0.0] — 2026-08-01

Initial release.

### Added

- **Talent & Location management**: custom post types, taxonomies (`talent_category`, `talent_group`, `location_type`), and structured profile meta boxes (age/city/availability/measurements/languages/skills/video/PDF/gallery for Talent; city/parking/power/availability/amenities/map embed/gallery for Locations), each with Featured/Show on Homepage/Active visibility flags.
- **Registration Guard**: the plugin registers its own CPTs, taxonomies, and meta boxes only when nothing else already provides them (`post_type_exists()`/`taxonomy_exists()` checks, plus a narrow theme-detection check specifically for meta boxes) — installable standalone on a fresh site, or alongside a theme/plugin that already has its own Talent/Location system without creating duplicates.
- **Dashboard**: live stat tiles (Total/Featured/Active per type, current Display Mode, Pending Applications) and a Recent Activity feed.
- **Display Modes**: Hidden / Now Scouting / Live, set independently per type, with a fully admin-editable placeholder card (badge, heading, description, button, image, count) for the "Now Scouting" state.
- **Homepage Control**: heading/subheading/button/count/mode/card style/animation for the Talent and Location homepage sections, editable from wp-admin with no Elementor required.
- **Forms**: a built-in form builder (`am_form`/`am_submission` post types) with add/enable-disable/required/order field management, two default forms (Talent Application, Location Submission), nine field types (text/email/tel/url/textarea/select/checkbox/image/file), nonce + honeypot protected public submission handling, and a full approval workflow — Submitted → Review → Approved → Published (creates a real draft profile with mapped fields), or Rejected/Archived at any point before Published. Includes email notifications and CSV export of submissions.
- **Shortcodes**: `[talent_grid]`, `[talent_featured]`, `[talent_carousel]`, `[talent_slider]`, `[location_grid]`, `[location_featured]`, `[location_carousel]`, `[location_slider]`, `[talent_application_form]`, `[location_submission_form]`.
- **Elementor integration**: a dedicated "Agency Manager" widget category with ten widgets mirroring the shortcodes above, sharing one rendering implementation per family (grid/carousel/slider/featured; the two form widgets). Content/Layout/Style control groups using Elementor's native Typography/Border/Box Shadow controls, plus a custom Card Hover control. Guarded entirely behind `elementor/loaded` — no Elementor class is ever referenced if Elementor isn't active.
- **Import/Export**: a versioned JSON schema covering Talent, Locations, Categories, Groups, Location Types, Forms, Display Settings, Homepage Settings, and Plugin Settings (individually or all at once). Slug-based matching creates-or-updates without ever duplicating; media is referenced by URL and sideloaded on import (deduped by filename); a detailed per-section report (created/updated/errors) is shown after every import.
- **Backup & Restore**: a one-click Settings+Forms-only shortcut built on the same Export/Import code.
- **Setup Wizard**: a first-run, three-step flow (agency type → optional starter pages, each embedding the matching shortcode → done), redirecting once after activation.
- **Template overrides**: any theme can override the four card/placeholder templates by placing a same-named file in `{theme}/agency-manager/`.
- **Extension points**: `am_submission_created`, `am_submission_status_changed`, `am_submission_published`, `am_before_import_section`, `am_after_import_section` actions; `am_form_fields`, `am_notification_recipient`, `am_settings_defaults`, `am_talent_query_args`, `am_location_query_args` filters.

### Security

- CSV exports defuse formula injection (leading `=`/`+`/`-`/`@` prefixed with `'`) on all cells, including translated headers.
- Public form image/file uploads capped at 10 MB; "image" fields additionally restricted to actual image mime types (jpg/gif/png/webp) rather than WordPress's full default upload list.
- JSON import validates file type, size (10 MB cap), and top-level schema shape before processing; malformed or wrongly-typed individual items are skipped and counted as errors rather than trusted or allowed to fatal.
- Every admin state-changing action is nonce-protected and capability-checked; the one intentionally public handler (form submission) is nonce + honeypot protected.

### Fixed during hardening (pre-release)

- Meta-box/term-meta Registration Guard checks were evaluated at plugin-construction time (before an active theme's `functions.php` runs), so a theme-defined constant was never yet available and the guard silently failed to defer. Moved the check into the `init` hook callback itself, matching the CPT/taxonomy guard pattern.
- `Importer::import_posts()`'s post-status validation ternary referenced the raw (possibly-undefined) array key in its true-branch instead of the already-defaulted local value, which both emitted a PHP warning and could silently store a null `post_status`.
- `Importer::import_posts()`'s gallery-image handling didn't deduplicate the resulting attachment-ID list — multiple gallery entries resolving to the same (correctly deduped) attachment produced repeated IDs in `_am_gallery_ids`, showing the same image multiple times.
- `Form_Renderer::render_form()` unconditionally enqueued the full wp.media admin library on the public-facing frontend for a plain `<input type="file">` element that needs no JavaScript at all.
- Consolidated three duplicate `wp_enqueue_media()`/`admin.js`/`admin.css` call sites (`Meta_Boxes`, `Term_Meta`, `Display_Page`) into one shared `Admin\Media_Picker_Assets::enqueue()` helper.

=== Agency Manager ===
Contributors: edencast
Tags: talent, casting, locations, elementor, csv import
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.6.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Talent and location management for agencies — profiles, application forms, CSV import, and Elementor widgets, from one admin screen.

== Description ==

Agency Manager gives a talent, casting, or location agency a self-contained management system inside WordPress: a React/Tailwind admin application (Dashboard, Talent, Locations, Applications, Forms, Website Display, Import/Export, Settings), Talent and Location profiles, public application forms with a review workflow and a drag-and-drop Form Builder, CSV import for bulk Talent/Location data, three Display Modes (Hidden / Now Scouting / Live), Elementor widgets, shortcodes, and JSON import/export.

= Features =

* **Admin application**: Dashboard, Talent, Locations, Applications, Forms, Website Display, Import/Export, and Settings as one React/Tailwind interface over standard WordPress custom post types, taxonomies, and post meta — no custom database tables.
* **Talent & Location management**: add/edit screens (Basic Info, Professional Info, Media/Gallery, Social Links, Availability, Visibility) with a live preview using the actual public card template.
* **Form Builder**: a drag-and-drop editor (Field Library / Canvas / Field Settings) for the public Talent Application and Location Submission forms, with field-level mapping into Talent/Location profile fields.
* **Applications**: review public submissions through a Submitted → Review → Approved → Published workflow.
* **CSV Import**: upload → column-mapping → preview → import wizard for bulk-importing Talent or Locations from a spreadsheet, with duplicate detection (create/update/skip), optional taxonomy auto-creation, and image download from URLs in the spreadsheet. Where the server's PHP image library supports it, downloaded images also get WebP-optimized derivative sizes; the original uploaded file is always kept unchanged.
* **Website Display**: Display Mode (Hidden / Now Scouting / Live), an editable "Now Scouting" placeholder manager, and homepage featured-content control, per content type.
* **Shortcodes**: `[talent_grid]`, `[talent_featured]`, `[talent_carousel]`, `[talent_slider]`, the matching Location shortcodes, and form shortcodes, with `category`, `group`, `type`, `columns`, `only_featured`, `only_active`, and `order` parameters.
* **Elementor widgets**: Talent/Location Grid, Carousel, Slider, and Featured widgets, plus the Talent Application and Location Submission form widgets — all optional; Elementor is not required.
* **Import / Export**: a JSON schema for Talent, Locations, taxonomies, Forms, and settings, matched by slug so re-importing the same file updates existing records instead of duplicating them.
* Checks for an existing `talent`/`location` post type or taxonomy before registering its own, and defers automatically if a theme or another plugin already provides one — so it never creates duplicate content types.

= External services =

Agency Manager's CSV Import feature can download images from URLs that **you** type or paste into your own CSV file — for example, a link to a photo already hosted on Dropbox, your own server, or any other URL you provide. When you use this feature, Agency Manager sends an HTTP request to that specific URL, using WordPress's own `download_url()` function, to copy the image into your site's Media Library. No request is made unless you supply a URL in a CSV file and use the CSV Import feature; there is no other third-party service, API, tracking script, font, or CDN that this plugin contacts, automatically or otherwise.

== Installation ==

1. In your WordPress admin, go to **Plugins → Add New → Upload Plugin**.
2. Choose the Agency Manager ZIP file and click **Install Now**.
3. Click **Activate**.
4. You'll be redirected to the Setup Wizard automatically.

== Frequently Asked Questions ==

= Does this conflict with a theme that already has its own Talent/Location post types? =

No. Agency Manager checks for an existing `talent`/`location` post type or `talent_category`/`talent_group`/`location_type` taxonomy before registering its own, and defers automatically if one already exists. Its Dashboard, Applications, Forms, Website Display, Import/Export, Settings, shortcodes, and Elementor widgets remain fully available either way.

= Do I need Elementor? =

No. Every widget also exists as a shortcode, and the Setup Wizard's starter pages use shortcodes only.

= Does the CSV importer contact any third-party service? =

Only if you tell it to. It downloads images from URLs you provide inside your own CSV file, using WordPress's standard `download_url()` function — the same mechanism WordPress itself uses for "insert image from URL." No image is downloaded unless you supply a URL and run an import.

= Where do I find full documentation? =

An Admin Guide (day-to-day admin use) and Developer Guide (hooks, architecture, template overrides) are included in the plugin's own files, alongside this readme.

= What happens to my data if I delete the plugin? =

Deleting the plugin (not just deactivating it) removes only its own settings option. Your Talent, Location, Form, and Application/Submission content is left in place, since it consists of ordinary WordPress posts, taxonomies, and post meta.

== Privacy ==

Agency Manager's public Talent Application and Location Submission forms can collect personal information directly from the person filling out the form — for example, name, email address, phone number, free-text answers, and uploaded files, depending on how each form is configured in the Form Builder. When a form is submitted, Agency Manager also records the submitter's IP address (from the standard `REMOTE_ADDR` server value) against that submission, for spam/abuse review purposes.

All of this information is stored locally in your WordPress database as ordinary post meta on the submission record — Agency Manager does not send it to any external service, analytics platform, or third party. It stays on your own server, under your own control, exactly like any other WordPress post data.

If you use the CSV Import feature, the Name/Email/Phone/etc. fields you import become part of the resulting Talent or Location record, stored the same way.

Because these forms can collect personal data, if your site is subject to GDPR or similar regulations you are responsible for including Agency Manager's Talent Application and Location Submission forms in your site's own privacy policy, and for handling data-access/erasure requests through WordPress's built-in Privacy Tools (Tools → Export/Erase Personal Data), the same as you would for any other form plugin.

== Screenshots ==

1. The Dashboard — stats, Quick Actions, Recent Activity, and Shortcode Reference.
2. The Talent list — search, filter, and status.
3. Adding/editing a Talent profile.
4. The Location list.
5. Editing a Location.
6. Reviewing public Applications.
7. The Forms screen.
8. The Form Builder's Field Library and Canvas.
9. Website Display settings.
10. The CSV Import wizard — upload step.
11. The CSV Import wizard — column mapping.
12. The CSV Import wizard — preview.
13. Import / Export.
14. The Shortcode Reference panel.
15. A public Talent profile as rendered on the front end.
16. A public Location profile as rendered on the front end.
17. Talent cards on the front end.
18. Location cards on the front end.

== Changelog ==

= 1.6.4 =
Production hardening pass: the CSV Import wizard and its image pipeline were re-verified end-to-end at scale (100 Talent, ~400 images), including a duplicate-import test (Update mode: 0 duplicates), a changed-image re-import test, and a partial-failure test (one bad image URL no longer affects the rest of the batch). Adds WebP optimization for images imported via CSV — generated sub-sizes are re-encoded as WebP after download where the server supports it, while the original uploaded file is always kept untouched in its original format. No CDN or external service required; no behaviour changes to Elementor, shortcodes, Forms, or existing Media Library content.

= 1.6.0 =
The Form Builder (Forms → Edit) now lives inside the same React application shell as the rest of the admin, with a searchable Field Library, Form Canvas, Field Settings panel, and an explicit save/unsaved-changes workflow. No form storage, schema, or save logic changed.

= 1.5.0 =
Complete React/Tailwind/Radix redesign of the entire admin application (Dashboard, Talent, Locations, Applications, Forms, Website Display, Import/Export, Settings) over the same existing WordPress data — no CPT/taxonomy/meta changes. Adds full Talent/Location add/edit screens (replacing the classic post editor for these types), a visually restyled Form Builder, and a new CSV Import wizard (upload -> column mapping -> preview -> import) for bulk Talent/Location data with duplicate detection and optional taxonomy/image handling. No data changes — all existing content, settings, forms, and submissions carry over automatically.

= 1.4.0 =
Reverts the 1.3.0 Scouting Campaign architecture; Website Display and Scouting Mode return to Hidden / Now Scouting / Live with the original placeholder-card behaviour, plus a Multiple Scouting Images enhancement (the Placeholder Manager's image field is now a multi-image picker that cycles across placeholder cards).

= 1.3.0 =
Scouting Mode redesigned as an independent, admin-configured recruitment campaign (repeater of cards with image/badge/title/description/button), decoupled from real Talent/Location content; reverted in 1.4.0. Superseded by 1.4.0 — see above.

= 1.2.0 =
Scouting Mode changed to show real Talent/Location content with a "Now Scouting" badge, plus a new theme-compatibility meta layer (`Frontend\Meta_Resolver` / `am_meta_fallback_map`) letting a theme's own existing Talent/Location meta prefix act as a fallback. The Scouting Mode change was superseded by 1.3.0 and reverted in 1.4.0; the meta compatibility layer is unaffected and remains in place.

= 1.1.0 =
UX/information-architecture redesign: new Applications and Website Display screens, tabbed Talent/Location editors with Social Links and a Preview tab, expanded shortcode and Elementor widget filtering (category/group/type/columns/only_featured/only_active/order), Dashboard Quick Actions. Import/Export reorganized into Content / Settings / Optional groups, with dedicated Export Talent / Export Locations / Export Content / Export Everything quick actions — Forms and Plugin Settings are always opt-in, never bundled automatically.

= 1.0.0 =
Initial release.

== Upgrade Notice ==

= 1.6.4 =
Production hardening for CSV Import and its image pipeline, plus optional WebP optimization for imported images. No data changes — existing Talent, Locations, Forms, Applications, settings, and Elementor content carry over automatically with no action required.

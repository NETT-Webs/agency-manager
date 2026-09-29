# NettWebs Talent & Location Management

Talent, casting, model, and location management for agency websites — one coherent admin experience for Talent, Locations, Applications, and Website Display, not a collection of raw custom-post-type screens. Works standalone on any WordPress site; no theme dependency.

**Version:** 1.7.1
**Requires:** WordPress 6.0+, PHP 7.4+ (tested on 8.2 and 8.3)
**Elementor:** optional — shortcodes and the admin work without it; the 10 Elementor widgets require Elementor to be active.
**Source:** https://github.com/NETT-Webs/agency-manager
**Developed by:** [NettWebs](https://nettwebshosting.co.za/)

**What's new in 1.6.4:** production hardening pass over the CSV Import wizard and its image pipeline — re-verified end-to-end at scale (100 Talent, ~400 images), plus a WebP optimization step for imported images: WordPress's own image editor re-encodes generated sub-sizes (thumbnail, medium, card sizes, etc.) as WebP after download, while the original uploaded file is always kept untouched in its original format for compatibility. No CDN or external service required — if the server's image library can't produce WebP, images import normally in their original format. See CHANGELOG.md for details.

**What's new in 1.6.0:** the Form Builder (Forms → Edit) now lives inside the same React application shell as the rest of the admin — a searchable Field Library, Form Canvas, and Field Settings panel, plus a Form Settings dialog and an explicit save/unsaved-changes workflow. No form storage, schema, or save logic changed. See CHANGELOG.md for details.

**What's new in 1.5.0:** the entire admin area (Dashboard, Talent, Locations, Applications, Forms, Website Display, Import/Export, Settings) is now one cohesive React application over the same existing WordPress data. Talent/Location now have a dedicated add/edit screen with a live card preview, and a **CSV Import** wizard (Import/Export → CSV Import) supports bulk Talent/Location import from a spreadsheet with column mapping, validation, and duplicate handling.

---

## Table of Contents

1. [Installation](#installation)
2. [First-Run Setup Wizard](#first-run-setup-wizard)
3. [Dashboard](#dashboard)
4. [Adding Talent & Locations](#adding-talent--locations)
5. [Display Modes & the Placeholder Manager](#display-modes--the-placeholder-manager)
6. [Applications & Forms](#applications--forms)
7. [Shortcodes](#shortcodes)
8. [Elementor Widgets](#elementor-widgets)
9. [Homepage](#homepage)
10. [Shortcode Reference Panel](#shortcode-reference-panel)
11. [CSV Import](#csv-import)
12. [Import / Export](#import--export)
13. [Backup & Restore](#backup--restore)
14. [Theming: Theme Style & Template Overrides](#theming-theme-style--template-overrides)
15. [Meta Compatibility (Existing Theme-Owned Content)](#meta-compatibility-existing-theme-owned-content)
16. [Hooks (Actions & Filters)](#hooks-actions--filters)
17. [Folder Structure](#folder-structure)
18. [Developer Notes](#developer-notes)
19. [Troubleshooting](#troubleshooting)
20. [Security Notes](#security-notes)

---

## Installation

1. Upload the `agency-manager` folder to `wp-content/plugins/`, or upload the zip via **Plugins → Add New → Upload Plugin**.
2. Activate **NettWebs Talent & Location Management** from the Plugins screen.
3. You'll be redirected to the [Setup Wizard](#first-run-setup-wizard) automatically.

**On a site that already has its own Talent/Location system** (e.g. a theme that registers `talent`/`location` post types itself): NettWebs Talent & Location Management detects this automatically and does not double-register anything — see [Developer Notes → Registration Guard](#registration-guard--theme-compatibility). Its Dashboard, Applications, Forms, Website Display, Import/Export, Settings, shortcodes, and Elementor widgets are still fully available regardless.

## First-Run Setup Wizard

Runs once, immediately after activation.

1. **Agency Type** — Talent / Location / Casting / Model / Combined Agency. Sets the default [Display Mode](#display-modes--the-placeholder-manager) for the type you don't need to Hidden; doesn't create any content for it.
2. **Starter Content** — optionally creates up to four pages, each containing just the matching shortcode:
   - Talent page → `[talent_grid]`
   - Locations page → `[location_grid]`
   - Apply as Talent page → `[talent_application_form]`
   - Register Your Location page → `[location_submission_form]`

   Existing pages at the same slug are left alone. If your active theme has a menu assigned to a theme location, the new pages are added to it automatically. The two default forms (Talent Application, Location Submission) and their fields are seeded automatically on activation, before the wizard even runs.
3. **Done.**

The wizard deliberately never generates Elementor page-builder layouts — only plain pages with a shortcode in them, which already respects your Display Mode settings and is far more robust across themes. Use the [Elementor widgets](#elementor-widgets) instead if you want a builder-made layout.

## Dashboard

**NettWebs Talent & Location Management → Dashboard** is the front door — a professional at-a-glance view, not a raw post list:

- **Stat tiles**: Total Talent, Featured Talent, Active Talent, Talent Display Mode, Total Locations, Featured Locations, Active Locations, Locations Display Mode, Pending Applications, Approved Applications.
- **Recent Activity**: the latest Talent, Location, and Application records, linking straight to the right edit screen or Applications tab.
- **Quick Actions**: + Add Talent, + Add Location, + View Applications, + Website Display.

## Adding Talent & Locations

If NettWebs Talent & Location Management owns the `talent`/`location` post types on your site (see the Registration Guard note below), **Talent** and **Locations** appear as their own menu items under **NettWebs Talent & Location Management**, each with the standard WordPress All / Add New / Categories (/ Groups for Talent) submenu — added automatically by WordPress itself once the post types are registered, no extra plugin code needed for that part.

**Add Talent / Edit Talent** is a tabbed editor, not one long form:

| Tab | Fields |
|---|---|
| **General** | City, Age, Availability |
| **Profile** | Languages, Skills, Experience, Video URL |
| **Measurements** | Height, Body Type, Hair Colour, Eye Colour, Measurements |
| **Media** | PDF (comp card / CV) — the main profile photo uses WordPress's own Profile Photo panel in the sidebar |
| **Gallery** | Photo gallery |
| **Social Links** | Instagram, Facebook, TikTok, Website/Portfolio |
| **Visibility** | Featured, Show on Homepage, Active |
| **Preview** | A live-rendered card using the *exact* frontend template — see below |

**Add Location / Edit Location** mirrors this: **General** (City) · **Property Details** (Parking, Power) · **Gallery** · **Facilities** (Amenities) · **Availability** · **Map** (Google Maps embed URL) · **Visibility** · **Preview**.

Visibility flags, shared by both:
- **Featured** — included in `[talent_featured]` / `[location_featured]` and the matching widgets.
- **Show on Homepage** — a separate flag from Featured, so an entry can be featured on an archive without being pinned to the homepage.
- **Active** — unchecking hides an entry from every listing without deleting it. Entries default to Active.

**Preview tab**: reuses the exact same `Frontend\Card_Renderer` template the live site uses — a single source of truth, not a re-implementation. It reflects the *last-saved* state only ("Save or Update this entry to refresh the preview.") — there's no live/AJAX preview in v1, by design, to keep this simple and maintainable rather than re-building part of Elementor's own live-preview system inside the plugin.

There is intentionally no SEO tab — NettWebs Talent & Location Management manages Talent, Locations, Applications, Forms, and Website Display; SEO is a dedicated SEO plugin's job (Yoast, RankMath, etc.), not this one's.

Talent Groups and Location Categories (**NettWebs Talent & Location Management → Talent/Locations → Groups/Categories**) each support an optional Card Image, Button Text, and Button URL for custom category-card layouts.

## Display Modes & the Placeholder Manager

**NettWebs Talent & Location Management → Website Display** — one screen for everything that controls how Talent and Locations *appear*, per type, independently (e.g. Talent → Live, Locations → Now Scouting):

| Mode | Behavior |
|---|---|
| **Hidden** | The relevant shortcode/widget renders nothing at all, and the homepage section is skipped. |
| **Now Scouting** | Renders placeholder cards instead of real entries — for launching before your roster/portfolio is ready. |
| **Live** | Renders your real, Active entries. |

The **Placeholder Manager**, on the same screen, is fully admin-editable — nothing is hardcoded: Badge, Title, Description, Button Text, Button Link, one or more **Scouting Images**, and how many placeholder cards to show. Switching a type to Now Scouting immediately swaps every grid/widget/shortcode of that type over to these placeholders; switching back to Live restores your real content — no code, no rebuilding pages.

**Scouting Images**: select one or more images via the same media-library button used elsewhere in the plugin. Each placeholder card uses the next image in order — card 1 uses image 1, card 2 uses image 2, and so on, cycling back to the first image once every image has been used (3 images across 5 cards → image 1, 2, 3, 1, 2). A single image is used on every card. Leaving this empty shows the original plain placeholder block, exactly as before.

The same screen's **Homepage Section** panel (heading/subheading/button/count/mode/card style/animation, per type) is what actually drives the homepage — see [Homepage](#homepage).

## Applications & Forms

**NettWebs Talent & Location Management → Forms** manages form *definitions*: the two defaults (Talent Application, Location Submission) plus any custom ones. Clicking **Edit** on any form opens the visual **Form Builder** — a three-panel workspace (searchable Field Library, Form Canvas, Field Settings) inside the same admin application shell as every other screen. Drag a field onto the canvas, or use its "+" button; select a field to edit its label, type, required flag, validation, conditional logic, and Talent/Location field mapping. See the Form Builder Guide for a full walkthrough.

**NettWebs Talent & Location Management → Applications** is where submissions are actually reviewed — separate from form *definitions*, with a Talent/Location tab switch, listing every submission across all forms of that type:

Submitted → Review → Approved → **Published**, or → Rejected / Archived at any point before Published.

- **Approve** is purely a status change — a human checkpoint. Nothing goes public automatically.
- **Publish** (only once Approved) is the one place a submission's answers get mapped onto a real Talent/Location post — matching by field key (`full_name`/`location_name` → title, `email`/`phone`/`city`/`message`/`description` → profile meta, `photo` → featured image). The new post starts as a **draft**: the application itself was already reviewed, but the profile (more photos, bio copy) usually still needs finishing before it should go live. An administrator never edits raw post data to do this — Publish does it for them.
- **Export CSV** downloads a form's submissions.

Frontend forms embed via `[talent_application_form]` / `[location_submission_form]` or the matching Elementor widgets — both public and unauthenticated by design, protected by a nonce and a honeypot field, capped at 10 MB per upload, with "image" fields restricted to actual image files regardless of what a visitor tries to upload.

## Shortcodes

| Shortcode | Notes |
|---|---|
| `[talent_grid]` | Static CSS grid, default 12 items. |
| `[talent_featured]` | Defaults everything from **Website Display → Talent Homepage Section**; only entries with **Show on Homepage** checked. |
| `[talent_carousel]` | Scrollable, default 9 items, 3 per view on desktop, responsive down to 1. |
| `[talent_slider]` | One item at a time, default 6 items, autoplay. |
| `[talent_scouting]` | Always renders the "Now Scouting" placeholder cards for Talent, regardless of the global Display Mode — for placing a scouting section anywhere on demand. |
| `[location_grid]` / `[location_featured]` / `[location_carousel]` / `[location_slider]` / `[location_scouting]` | Location equivalents. |
| `[talent_application_form]` / `[location_submission_form]` | The two built-in forms. |

Common attributes on every grid/featured/carousel/slider shortcode:

| Attribute | Values | Notes |
|---|---|---|
| `limit` | integer | The number of cards to show. e.g. `[talent_featured limit="5"]` shows exactly 5 Talent cards — ideal for a homepage section. `count` remains a working alias for backward compatibility; if both are given, `limit` wins. Defaults to the shortcode's own sensible default (12 for grid, 9 for carousel, 6 for slider, or the Homepage Section count for `*_featured`) when neither is set. |
| `mode` | `inherit` (default) \| `hidden` \| `scouting` \| `live` | Overrides Website Display's mode for just this instance. |
| `columns` | 1–6 | Grid only; sets `--am-columns` inline. Leave unset for the default responsive auto-fill behaviour. |
| `category` | talent_category slug | **Talent only.** e.g. `[talent_grid category="models"]` |
| `group` | talent_group slug | **Talent only.** e.g. `[talent_grid group="children"]` — combine with `category` for an AND filter. |
| `type` | location_type slug | **Location only.** e.g. `[location_grid type="wine-estates"]` |
| `only_featured` | `1`/`yes` \| `0`/`no` (default `0`) | Restrict to entries with **Featured** checked. |
| `only_active` | `1`/`yes` (default) \| `0`/`no` | Set to `0` to include inactive entries too. |
| `order` | `newest` (default) \| `oldest` \| `random` | |

There is no `style` attribute — appearance is entirely the active theme's responsibility. See [Theming](#theming-theme-style--template-overrides).

`[talent_scouting]` and `[location_scouting]` accept only `limit` and `columns` — a placeholder card has no taxonomy, active flag, or sort order to filter by. They bypass the global Display Mode and Query entirely, so `[talent_scouting limit="5"]` always shows 5 Talent placeholder cards even while the global Talent Display Mode is set to Live.

Examples: `[talent_featured limit="5"]`; `[location_featured limit="5"]`; `[talent_grid columns="4" category="models" order="random"]`; `[location_grid type="wine-estates" only_active="0"]`; `[talent_scouting limit="3"]`.

## Elementor Widgets

Available under the **NettWebs Talent & Location Management** category once Elementor is active: **Talent Grid, Featured Talent, Talent Carousel, Talent Slider, Location Grid, Featured Locations, Location Carousel, Location Slider, Talent Application Form, Location Submission Form**.

Grid/Carousel/Slider/Featured widgets share one set of controls:
- **Content** — Number of Cards; a Category and Group dropdown for Talent widgets or a Type dropdown for Location widgets (populated live from your actual taxonomy terms, not free-text slugs); Only Featured; Sort Order (Newest/Oldest/Random); Only Active; Display Mode — **Auto** (follows Website Display's global setting for this type), **Hidden**, **Force Now Scouting** (always shows placeholder cards in this widget, regardless of the global mode — the widget equivalent of `[talent_scouting]`/`[location_scouting]`), or **Live**. Featured widgets additionally have a "Use Homepage Settings" switch, on by default, so the homepage never needs Elementor edits at all — switch it off to hand-configure a Featured widget with the exact same Category/Group/Type/Only Featured/Sort Order/Display Mode controls as any other widget.
- **Layout** — responsive columns (grid only), card hover effect (lift/zoom/fade).
- **Style tab** — a full set of visual controls, no code required, split into six groups:
  - **Image** — Aspect Ratio (Portrait/Square/Landscape), an optional custom pixel Height override, Border Radius, Object Fit (Cover/Contain).
  - **Card** — Background Colour, Border Colour/Width/Radius, Padding, Shadow (None/Small/Medium/Large).
  - **Button** — Width (Auto/Full Width), Height, Border Radius, Text Size, Background/Text/Border Colour, and separate Hover Background/Text Colour.
  - **Badge** — Background, Text Colour, Border Colour (the "Now Scouting" badge on placeholder cards).
  - **Typography** — Title, Subtitle, and Description (the placeholder-card status line) typography and colour, using Elementor's native Typography group control.
  - **Spacing** — Card Gap (between cards), Internal Padding (inside the title/subtitle area), Button Margin.

  Every one of these is a standard Elementor control using its native `selectors` mechanism — Elementor generates the CSS itself, scoped to that one widget instance only (never affecting any other widget, page, or the plugin's shortcodes). Nothing here is hardcoded in the plugin, and no CSS or PHP editing is ever required to use it.

  A **Reset to Theme Defaults** button sits at the top of the Style tab. Click it to restore every one of the controls above back to its original value — only on the widget instance you're currently editing; no other widget, page, or the theme's own CSS is ever touched.

### Widget Style Presets

A **Widget Style** section sits at the very top of the Style tab — above Reset — so a widget only ever has to be styled once:

| Control | What it does |
|---|---|
| **Preset** (dropdown) | Every saved preset, by name. |
| **Preset Name** | Used by Save and Rename, below. |
| **Load** | Applies the selected preset's full set of Style-tab values (Image/Card/Button/Badge/Typography/Spacing) to **this widget instance only**. Instant — no page reload. |
| **Save Current Widget Style** | Snapshots every current Style-tab value on this widget and saves it under the typed name — creating a new preset, or overwriting an existing one with that exact name. |
| **Rename Selected Preset** | Renames the preset selected in the dropdown to the typed name, keeping its saved values. |
| **Delete Selected Preset** | Removes the selected preset everywhere it's stored (with a confirmation prompt) — it simply won't be in the list for any widget going forward. |

Presets are named however makes sense for your site — "Luxury Cards," "Homepage Cards," "Small Cards," "Dark Cards," "Minimal," etc. Save/Rename/Delete affect that named preset everywhere (any widget could load it); Load only ever changes the one widget you're currently editing. Presets are stored once, site-wide (not per-page or per-widget-type), so a preset built on a Talent Grid widget can be loaded onto a Location Slider widget just as easily.

The two form widgets let you hide specific fields for that one instance (field management itself stays on the Forms screen) plus label typography and button colours.

## Homepage

The homepage uses the *same* `[talent_featured]` / `[location_featured]` shortcodes (or their Elementor widgets) as anywhere else on the site — there is no separate homepage-only rendering path. Set the heading, subheading, button, and card count once on **Website Display → Homepage Section**, and every `*_featured` shortcode/widget with "Use Homepage Settings" on (the default) reflects it immediately.

Want a specific number of cards on a particular page instead — say, 5 on the homepage but 12 on the Talent archive? Switch off "Use Homepage Settings" and use `limit`: `[talent_featured limit="5"]` and `[location_featured limit="5"]`, or an Elementor Featured widget with Number of Cards = 5 — no code changes required either way. Want placeholder cards in a specific spot regardless of the global Display Mode — e.g. a "coming soon" teaser section while the rest of the site is Live? Use `[talent_scouting limit="5"]` / `[location_scouting limit="5"]`, or a widget with Display Mode set to Force Now Scouting.

## Shortcode Reference Panel

Every shortcode is listed, described, and copy-button-ready in one place — **NettWebs Talent & Location Management → Dashboard**, under "Shortcodes" — grouped as Talent, Locations, and Forms, so nothing has to be memorised or looked up in this README. The same panel (scoped to just the relevant group) also appears on:

- The native **Talent** and **Locations** list screens (`Talent → All Talent`, `Locations → All Locations`) — only that section's own shortcodes.
- **NettWebs Talent & Location Management → Forms** and **NettWebs Talent & Location Management → Applications** — the two form shortcodes, since applications are created by submitting them.

Each entry expands to show **What it does**, **When to use it**, and its **Available parameters**, and has a **Copy** button — click it, then paste directly into an Elementor Text/Shortcode widget, a block-editor Shortcode block, or the classic editor. A small "Shortcode copied." message confirms it.

**Interactive Shortcode Builder** — every shortcode that takes parameters also has a "Build it" form right below its description: a field for each real parameter (Limit, Columns, Category, Group, Featured Only, Active Only, Order By, Display Mode), matched to the right input type (number, dropdown, checkbox, or text). As you change any field, the exact shortcode updates live in a code box below, ready to copy — no memorising attribute names or quoting syntax.

**Search** — a search box at the top of the panel filters shortcodes as you type, matching against the tag and its description (e.g. typing "featured" shows only `talent_featured`/`location_featured`; typing "carousel" shows only the carousel shortcodes). A group heading hides itself automatically if none of its shortcodes match.

**Common Examples** — a ready-made table at the bottom of the Dashboard panel: Homepage Featured Talent (`[talent_featured limit="4"]`), Homepage Featured Locations, Full Talent Page (`[talent_grid]`), Full Location Page, Now Scouting Talent (`[talent_scouting limit="4"]`), and Now Scouting Locations — each with its own Copy button.

## CSV Import

**NettWebs Talent & Location Management → Import / Export → CSV Import.** For bulk-adding Talent or Locations from a spreadsheet a client or scout already has — a separate wizard from the JSON Import/Export below, built for human-supplied CSV files rather than site-to-site migration.

The flow is always the same five steps:

1. **Upload** — choose Talent or Locations, then upload a `.csv` file (20MB max).
2. **Map columns** — each CSV column is matched to a Talent/Location field automatically where the header name is recognisable (e.g. "Email", "City", "Category"); anything not auto-matched (notably **Description**, **Featured**, and **Active**, which have no single obvious header name) is mapped by hand from a dropdown. Columns you don't want imported are simply left unmapped.
3. **Preview** — every row is validated before anything is written: how many records are new vs. already exist (matched by email, ID, or name — never guessed), and any warnings or errors per row.
4. **Choose Create / Update / Skip** — what happens when a row matches an existing record: create a new one anyway, update the existing record in place (fields not present in the CSV are left untouched), or skip it.
5. **Import & review results** — a progress screen while records are created/updated in batches, then a summary (created / updated / skipped / error counts) with a downloadable, per-row report.

**Images:** a "Featured Image" column and a "Gallery" column (multiple URLs separated by `|` or `,`) let each row point at real image URLs; NettWebs Talent & Location Management downloads them into the Media Library the same way WordPress's own "Insert from URL" does. Re-importing a CSV whose image URLs haven't changed reuses the already-downloaded image instead of downloading it again. Where the server's PHP image library supports it, imported images are also optimized: WordPress's own generated sub-sizes (thumbnail, medium, and the theme's card/profile sizes) are re-encoded as WebP after download, while the original uploaded file is always kept in its original format untouched — existing Media Library files, `wp_get_attachment_image()`, responsive `srcset`, and Elementor all keep working exactly as before, since nothing about *how* an image is looked up changes, only what format some of its sizes are stored in. If the server can't produce WebP, images are imported normally with no optimization step — importing never fails because of it.

## Import / Export

**NettWebs Talent & Location Management → Import / Export.** The primary use case is moving the *reusable* parts of an agency — Talent, Locations, and how they're displayed — from one WordPress installation to another (e.g. localhost → staging → production), as one JSON file.

Sections are grouped:

| Group | Sections | Included in "Export Content"? |
|---|---|---|
| **Content** | Talent, Locations, Talent Categories, Talent Groups, Location Categories | Yes |
| **Settings** | Website Display Settings, Homepage Featured Settings, Widget Style Presets, Plugin Settings | Website Display, Homepage Featured, and Widget Style Presets, yes. Plugin Settings, no — always opt-in. |
| **Optional** | Forms | No |

Four Quick Actions cover the common cases: **Export Talent** / **Export Locations** (just that one section), **Export Content** (Talent, Locations, both taxonomies, Display/Homepage settings, and Widget Style Presets — everything needed for a destination site's existing Talent/Location pages, built once with the same widgets or shortcodes, to immediately look and show the same content), and **Export Everything** (Export Content plus Forms and Plugin Settings). **Forms and Plugin Settings are never included automatically** — Forms are usually customised per agency (wording, branding, workflow) rather than reused across sites, so migrating them is always an explicit, separate choice.

- **Matching is always by slug** (or, for Widget Style Presets, by preset name) — an existing Talent/Location/Category/Group/Form/preset with the same slug/name is *updated in place*; nothing is ever duplicated. Re-importing the same file repeatedly is safe.
- Categories/Groups/Location Categories import before Talent/Locations, since posts reference them by slug.
- Images are referenced by URL in the file and downloaded into your media library on import (matched by filename, so a repeat import doesn't re-fetch the same file) — **the source site must be reachable over HTTP at import time.**
- Settings sections (including Widget Style Presets) are **merged**, not overwritten — importing Display Settings updates just the keys present in the file, and importing Widget Style Presets adds/updates only the presets present in the file by name, leaving any of the destination site's own differently-named presets untouched.
- A malformed or corrupted file is rejected before anything is written; a per-item structural problem inside an otherwise-valid file is skipped and counted as an error, not a fatal.
- You get a **detailed report** afterward: created / updated / error counts per section.
- Application *submissions* are intentionally not part of Import/Export (they're site-specific, in-progress workflow data, not portable content) — Forms (the field *definitions*) are, as an optional section.
- **No Elementor page layout content is ever exported or imported** — not `_elementor_data`, not page structure, nothing. Widget Style Presets are a different, much smaller thing: just the colour/size/typography values from a widget's Style tab, saved under a name — never a page's layout or content. The destination site's Talent/Location pages are still expected to already use the plugin's widgets or shortcodes (built once, per the Setup Wizard or manually); imported Talent/Locations simply appear there with no further action, and an imported Widget Style Preset can then be loaded onto those same widgets to match. NettWebs Talent & Location Management migrates its own content and appearance, never the website's page structure around it.

## Backup & Restore

**NettWebs Talent & Location Management → Settings → Backup & Restore** — a one-click version of Import/Export scoped to just Plugin Settings, Display Settings, Homepage Settings, and Forms (not your Talent/Location content). Useful before a big configuration change. It's a thin wrapper around the same Import/Export code — no separate logic to keep in sync.

## Theming: Theme Style & Template Overrides

NettWebs Talent & Location Management separates **data** (Talent/Location records), **rendering** (the templates, `Carousel_Renderer`/`Card_Renderer` classes, widgets, and shortcodes that turn that data into markup), and **styling** — which belongs entirely to the active theme. NettWebs Talent & Location Management ships **no colours, fonts, spacing, or branding of its own** — not even for Eden Cast. This keeps the plugin fully reusable: any theme, for any agency, styles it the same way.

### Theme Style

Every card and template reads a small set of CSS custom properties, each with a neutral, sensible fallback baked into the plugin's own `assets/css/frontend.css`:

`--am-primary`, `--am-secondary`, `--am-accent`, `--am-text`, `--am-heading`, `--am-button`, `--am-border`, `--am-radius`

A theme defines its own look by declaring these in its own stylesheet — nothing to configure in wp-admin, no dropdown, no shortcode attribute:

```css
/* your-theme/style.css, or any enqueued theme stylesheet */
:root {
    --am-primary: #5c1a2b;
    --am-secondary: #3e101c;
    --am-accent: #c9a24b;
    --am-heading: #171412;
    --am-button: #c9a24b;
    --am-border: rgba(14, 14, 14, 0.1);
    --am-radius: 2px;
}
```

A theme that defines nothing gets the plugin's neutral default look. A theme that defines these variables gets its own branding everywhere NettWebs Talent & Location Management renders a card — automatically, with no further configuration. `[talent_featured]`, `[talent_featured limit="5"]`, and every Elementor widget always render using whatever the active theme has defined; there is no preset to select, override, or manage.

Colours and radius cover most of a theme's identity, but typography, spacing, and hover motion aren't CSS custom properties in this design — a theme that wants those too simply targets NettWebs Talent & Location Management's plain, stable class names directly (`.am-talent-card`, `.am-talent-card__meta`, `.am-scouting-card__badge`, etc.) in its own stylesheet, the same way it would style any other plugin's output. The Eden Cast theme does exactly this — see `wp-content/themes/eden-cast/assets/css/talent-locations.css` for a complete real-world example (maroon gradient cards, a gold badge, serif headings, and a gradient-reveal hover caption), entirely in theme code, with zero Eden Cast branding inside the NettWebs Talent & Location Management plugin itself.

### Template Overrides

Every card/placeholder template, and the archive/single page templates, can be overridden by a theme without touching the plugin: create `agency-manager/{template-name}.php` inside your active theme's folder. Overridable names:

- `talent-card`, `location-card`, `talent-placeholder-card`, `location-placeholder-card` — checked before the plugin's own `templates/{template-name}.php`.
- `archive-talent`, `archive-location`, `single-talent`, `single-location` — full-page templates, used only if the Talent/Location post types are registered by NettWebs Talent & Location Management itself (a theme that provides its own CPTs, like Eden Cast, already fully owns these views — nothing here changes for that install). Checked in order: your theme's `agency-manager/{name}.php` override folder, then your theme's own conventionally-named root template (`single-talent.php`, `archive-location.php`, etc. — standard WordPress template-hierarchy naming, so a theme that already has one of these needs no override folder at all), then finally the plugin's own default under `templates/`.

```
your-theme/
  agency-manager/
    talent-card.php      <- card override, checked first
    single-talent.php    <- full-page override, checked first
  single-talent.php      <- OR: a plain WP-native template name, checked second
```

Copy the plugin's version from `wp-content/plugins/agency-manager/templates/` as a starting point — card templates receive the same variables the plugin passes in (`$post_id` for real cards, `$config` for placeholder cards); the archive/single templates are standard WordPress templates (`get_header()`/the loop/`get_footer()`). This is also exactly what the Preview tab (see [Adding Talent & Locations](#adding-talent--locations)) renders for cards, so an override applies there too — one template, one visual result everywhere.

## Meta Compatibility (Existing Theme-Owned Content)

If the active theme already has its own Talent/Location post types and its own meta prefix (e.g. Eden Cast's `_ec_*` fields), NettWebs Talent & Location Management's Registration Guard already defers entirely to that theme — no post types, taxonomies, or meta boxes are registered a second time (see `DEPLOYMENT.md` for the full compatibility verification).

NettWebs Talent & Location Management's own rendering (cards, Scouting Mode, the default single templates) reads its own `_am_*` meta first. When a field is empty, `Frontend\Meta_Resolver` checks the `am_meta_fallback_map` filter (see [Hooks](#hooks-actions--filters)) for a fallback meta key and reads that instead — so a theme can make its existing content display fully through NettWebs Talent & Location Management's own surfaces without migrating or duplicating a single value. NettWebs Talent & Location Management ships no fallback mappings of its own and contains no reference to any specific theme; the actual mapping lives in the theme, e.g. `wp-content/themes/eden-cast/inc/agency-manager-compat.php` on this site.

This is what makes Scouting Mode's real-card behaviour (see [Display Modes & the Placeholder Manager](#display-modes--the-placeholder-manager)) show a theme's existing Talent/Location roster correctly — title and photo already worked via native WordPress fields (`post_title`, featured image); this fallback closes the gap for the remaining fields (city, age, measurements, etc.) that live under the theme's own meta keys.

## Hooks (Actions & Filters)

| Hook | Type | Fires | Signature |
|---|---|---|---|
| `am_submission_created` | action | A new form submission is stored. | `( int $submission_id )` |
| `am_submission_status_changed` | action | Any workflow status change, including to `published`. | `( int $submission_id, string $new_status, string $old_status )` |
| `am_submission_published` | action | `publish_submission()` creates the real Talent/Location post. | `( int $post_id, int $submission_id )` |
| `am_before_import_section` | action | Before a section of an import file is processed. | `( string $section, mixed $payload )` |
| `am_after_import_section` | action | After a section finishes; report counts are populated. | `( string $section, array $counts )` |
| `am_form_fields` | filter | A form's field schema is resolved (rendering + validation). | `( array $fields, int $form_id )` |
| `am_notification_recipient` | filter | The recipient of the new-submission email. | `( string $to, int $submission_id )` |
| `am_settings_defaults` | filter | The plugin's default settings structure. | `( array $defaults )` |
| `am_talent_query_args` | filter | Final `WP_Query` args for any talent listing. | `( array $query_args, array $raw_args )` |
| `am_location_query_args` | filter | Final `WP_Query` args for any location listing. | `( array $query_args, array $raw_args )` |
| `am_meta_fallback_map` | filter | A card/single template reads an `_am_*` field and finds it empty. | `( array $map, string $type, int $post_id )` — return `field_name => meta_key` entries and NettWebs Talent & Location Management reads that key instead. |

Example — a theme that already owns Talent/Location content with its own `_ec_*` meta prefix can display fully through NettWebs Talent & Location Management's cards/templates without any migration:

```php
add_filter( 'am_meta_fallback_map', function ( $map, $type ) {
    if ( 'talent' === $type ) {
        $map['city'] = '_ec_city';
        $map['age']  = '_ec_age';
    }
    return $map;
}, 10, 2 );
```

NettWebs Talent & Location Management still reads `_am_city`/`_am_age` first — the fallback only applies when those are empty, so this is purely additive and never overrides a post that genuinely has its own `_am_*` data. See [Meta Compatibility](#meta-compatibility-existing-theme-owned-content) below for the full picture, and `wp-content/themes/eden-cast/inc/agency-manager-compat.php` for the real mapping this site uses.

Example — always send new-submission notifications to a second address too:

```php
add_filter( 'am_notification_recipient', function ( $to, $submission_id ) {
    return $to . ',bookings@example.com';
}, 10, 2 );
```

## Folder Structure

```
agency-manager/
  agency-manager.php            Plugin header, constants, autoloader
  uninstall.php                 Removes only the am_settings option — never content
  includes/
    class-plugin.php            Bootstrap — wires every subsystem onto its hook
    class-activator.php         Seeds default forms/settings, sets wizard redirect, flushes rewrites
    class-deactivator.php
    class-settings.php          The am_settings option: read/write/defaults, request-memoized
    compat/
      class-registration-guard.php   post_type_exists()/taxonomy_exists() + the one EDEN_CAST_DIR check
    cpt/                        Talent/Location CPTs, taxonomies, tabbed meta boxes, term meta (all guarded)
    forms/                      am_form/am_submission CPTs, Form_Renderer, Workflow, Notifications, Csv_Exporter
    frontend/                   Templates (override-aware loader), Template_Loader (archive/single
                                 template_include filter), Card_Renderer, Carousel_Renderer, Query
    admin/                      Dashboard, Applications, Forms, Website Display,
                                 Import/Export, Settings, Backup, Shortcode_Reference,
                                 and the shared Media_Picker_Assets helper
    wizard/                     Setup_Wizard
    shortcodes/                 The 8 grid/featured/carousel/slider shortcodes + 2 scouting shortcodes
    elementor/                  Elementor_Integration + widgets/ (Base_Grid_Widget, Base_Form_Widget, 10 concrete widgets)
    export-import/              Schema, Exporter, Importer
  templates/                    Default card/placeholder/archive/single templates (theme-overridable — see above)
  assets/
    css/                        frontend.css (--am-* CSS custom properties, theme-defined, with neutral
                                 plugin-provided fallbacks), carousel.css
    js/                         carousel.js (dependency-free, native CSS scroll-snap)
    admin/                      admin.css, admin.js (wp.media picker), tabs.js (dependency-free tab
                                 switcher), shortcode-reference.js (dependency-free clipboard copy)
```

## Developer Notes

### Registration Guard / theme compatibility

`Compat\Registration_Guard` decides whether this plugin registers the `talent`/`location` CPTs, their taxonomies, and the profile meta boxes, or defers to something that already provides them (e.g. a theme with its own Talent/Location system). CPT/taxonomy registration runs on `init` priority 20 and checks `post_type_exists()`/`taxonomy_exists()` — generic, and correctly defers to *anything* already registered, from any source. Meta boxes have one narrower, explicitly Eden-Cast-aware check (`defined('EDEN_CAST_DIR')`) since there's no generic "does a meta box already exist" check in WordPress — isolated to that one class so the rest of the plugin stays fully theme-agnostic. Forms (`am_form`/`am_submission`), the Dashboard, Applications, Settings, Import/Export, shortcodes, and Elementor widgets are never guarded — nothing else on a typical site registers anything equivalent.

**Important**: the guard checks run on `init`, not at plugin-construction time — a theme's `functions.php` hasn't executed yet when the plugin's main file first loads, so any theme-defined constant genuinely isn't available until later. Every guarded subsystem defers its actual check to a hook callback for this reason.

### Data model

- Post meta prefix: `_am_*` (e.g. `_am_age`, `_am_city`, `_am_gallery_ids`, `_am_featured`, `_am_homepage`, `_am_active`, `_am_social_instagram`/`_am_social_facebook`/`_am_social_tiktok`/`_am_social_website`). Deliberately its own consistent prefix rather than reusing any specific theme's meta keys, so the plugin stays honestly decoupled.
- Term meta prefix: `am_*` (`am_group_image_id`, `am_group_button_text`, `am_group_button_url`) on both `talent_group` and `location_type`.
- Settings: one option, `am_settings` (autoloaded — it's read on most frontend requests via the Display Mode checks; request-memoized in `Settings::all()` so a page with several shortcodes/widgets only pays the merge-defaults cost once).
- `_am_gallery_ids` is a comma-separated string of attachment IDs; parsed defensively wherever it's read.
- `location_type` is the taxonomy's programmatic key everywhere in code (rewrite slug, meta, query args) — only its *admin label* is "Categories," to match the Locations submenu wording. No data migration involved in that choice.

### Code architecture

- Namespace `AgencyManager\`, PSR-4-ish autoloading via a small `spl_autoload_register` in `includes/autoload.php` (underscores in class/namespace segments map to hyphens in the file path — WordPress's own file-naming convention combined with real namespaces).
- Every subsystem class exposes a `register()` method that wires its own hooks; `class-plugin.php` is the single place that instantiates all of them.
- Grid/Carousel/Slider/Featured rendering shares one implementation (`Frontend\Carousel_Renderer`, `Elementor\Widgets\Base_Grid_Widget`) — "layout" is just a parameter, not four copies of the same logic. Same for the two form widgets (`Base_Form_Widget`) and the two application forms (`Forms\Form_Renderer::render_form()`), and for the tabbed Talent/Location editor (`Cpt\Meta_Boxes::render_tabs()`, one small shell both post types share).
- Applications (`Admin\Applications_Page`) and Forms (`Admin\Forms_Page`) are deliberately separate screens with one responsibility each — reviewing submissions vs. defining form fields — both driving the same underlying `Forms\Workflow` and `am_form`/`am_submission` data.
- No raw SQL anywhere — only `WP_Query`/`get_posts`/`wp_insert_post`/`get_terms`/`wp_insert_term` and friends.

## Troubleshooting

**"I don't see a Talent/Locations menu item under NettWebs Talent & Location Management."** — Something else on your site (usually a theme) already registers those post types; the Registration Guard correctly deferred. Manage them wherever that existing menu item is; NettWebs Talent & Location Management's Dashboard, Applications, Forms, Website Display, Import/Export, and Settings screens are still fully functional.

**"My Elementor widgets don't appear."** — Confirm Elementor is active; the widgets are registered on `elementor/loaded` and simply don't exist otherwise (this is intentional — the plugin never touches an Elementor class unless Elementor itself is present).

**"A submission's required field wasn't actually required."** — If you hid that field on one specific Elementor form widget instance, that instance also exempts the hidden field from required-validation server-side (a visitor never saw it, so it can't be required for them) — this is expected, not a bug.

**"Import says 0 created, N updated, but I expected new content."** — Every item in your file matched an existing slug and was correctly updated in place rather than duplicated. Check the slugs in your source file if you actually intended new content.

**"Images didn't come through on import."** — The source site referenced in the file's image URLs must be reachable over HTTP from your server at the moment you run the import.

**"New submissions aren't emailing me."** — Check **NettWebs Talent & Location Management → Settings → Notification Email**, and confirm outbound mail works at all on your host (a plugin like WP Mail SMTP is usually the fix — a hosting/SMTP configuration matter, not something NettWebs Talent & Location Management itself controls).

**"The Preview tab looks out of date."** — It only reflects the last *saved* state, by design. Save or Update the entry to refresh it.

## Security Notes

- Every state-changing admin action is nonce-protected and capability-checked (`manage_options`, or `edit_post`/`manage_categories` where that's the more specific correct capability).
- CSV exports defuse formula injection (a cell starting with `=`, `+`, `-`, or `@` is prefixed with `'`) — submission data originates from unauthenticated public forms, so this matters.
- JSON imports are validated (file type, 10 MB size cap, schema shape) before anything is written, and malformed per-item data is skipped rather than trusted.
- Public form uploads are capped at 10 MB and "image" fields are restricted to actual image mime types.
- Media downloaded during import (`media_sideload_image()`, the same WordPress core function used by core's own importers) fetches whatever URL is in the file you choose to import — only run an import you trust, the same way you'd only run a WordPress "Import from URL" tool you trust. This action always requires `manage_options`.

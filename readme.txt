=== WM Accessibility Sensory Governor & Contrast Tokenizer ===
Contributors: arnoldwender
Tags: a11y, accessibility, wcag, contrast, reduced-motion
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 8.2
Stable tag: 1.2.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Focus-ring and reduced-motion CSS for WCAG 2.2 (SC 2.4.11, SC 2.3.3) plus a colour-contrast calculator in the admin, over REST and WP-CLI.

== Description ==

The plugin prints one `<style>` block into the head of every page. While the focus governor is on, every element that receives keyboard focus gets a visible outline in the configured ring colour and a scroll margin of 96 px at the top and 48 px at the bottom, which keeps it clear of fixed bars up to that height (aimed at WCAG 2.2 Success Criterion 2.4.11, Focus Not Obscured). While the motion governor is on, animations and transitions collapse to 0.001 ms whenever the visitor has enabled Reduced Motion in the operating system (SC 2.3.3).

It also calculates colour-contrast ratios with the WCAG relative-luminance formula and reports the AA, AAA and UI-component verdicts from the exact ratio (colours must be 3- or 6-digit hex codes): in the Live Contrast Calculator tab of the admin screen, over the rate-limited REST route `wm-sensory/v1/calculate-contrast` and with `wp a11y contrast <foreground> <background>`.

For site builders and developers, the plugin exposes WordPress 6.5+ Block Bindings (`wm-sensory/tokens`), a GenerateBlocks 2.x Dynamic Tag (`{{wm_sensory_token}}`), and four WordPress hooks (`wm_sensory_ready`, `wm_sensory_tokens_css`, `wm_sensory_contrast_result`, `wm_sensory_rest_rate_limit`).

The plugin stores four options (`wm_sensory_focus_enabled`, `wm_sensory_motion_enabled`, `wm_sensory_ring_color`, `wm_sensory_delete_data_on_uninstall`). Stored options are preserved by default when the plugin is uninstalled unless the administrator explicitly enables deletion on the admin screen. It ships no frontend JavaScript, no shortcode, no custom database table and makes no external request. Registers as a spoke of the WM Suite Hub when the hub is active.

== Frequently Asked Questions ==

= Is technical or private support provided? =

No. This plugin comes with zero support (`0` support). Private inquiries sent via email, `arnoldwender.com`, or social/chat channels are not answered. Reproducible bugs belong solely on GitHub Issues (`https://github.com/arnoldwender/wm-wp-a11y-sensory-governor/issues`) with no guarantee of reply or resolution. The software is provided **"AS IS"** at your sole risk with zero warranty or liability (see `SUPPORT.md` in the repository).

== Screenshots ==

1. Admin control screen for keyboard focus ring, scroll margins, motion reduction and exact WCAG contrast calculator
2. Frontend accessible skip target rendered alongside the active WM Sensory Governor stylesheet tokens
3. WordPress dashboard widget showing configured sensory protection status and quick link

== Changelog ==


= 1.2.2 =
* Fixed: Screenshot 1 showed the WM Suite menu from before WM Suite Hub 1.2.3, with entries the hub no longer has («Marketplace (Lösungspakete)», «Merkle-Audit-Protokoll», «CRA- & SBOM-Zentrale»). It is retaken with the current menu; the plugin's code is unchanged.

= 1.2.1 =
* The bundled family stylesheet is identical to WM Suite Hub 1.2.5 again: it starts the admin header below WordPress's help toggle instead of shortening it. This plugin's own screens register no help tab, so they change only where another plugin adds one.

= 1.2.0 =
* The WM Suite Hub contract now lives in the `ArnoldWender\SuiteHub\Contracts` namespace. The spoke adapter registers only with WM Suite Hub 1.2.0 or later, so update the hub together with this plugin; used on its own, the plugin works as before.
* The `.wordpress-org/` banners show the current version.
* Fixed: The subtitle of the admin header ended with a place name that has nothing to do with the plugin; it now names only what the plugin does. Its translation is kept and corrected in all 28 catalogs.
* Removed: The archived pre-2026-11-19 marketing visuals in `versionen/`: they claimed features and conformity the plugin does not have. They were never part of the installable package.

= 1.1.3 =
* Removed the agency name, links to unrelated projects and the personal mailbox from code, catalogs and documentation; the admin family badge and the hub menu read "WM Suite". The READMEs say the plugin was built with an agentic workflow (AI coding agents) and should be reviewed like any third-party code.
* Added root `SUPPORT.md`, GitHub Issue templates, explicit zero-support & "AS IS" liability notices in READMEs and `readme.txt`, and normalized canonical GPLv2+ `arnoldwender.com` / `arnoldwender/wm-wp-a11y-sensory-governor` links.
* Updated `.wordpress-org/` visual banners to personal `ARNOLD WENDER · OPEN SOURCE` branding.

= 1.1.2 =
* Added live `.wordpress-org/` visual kit assets (banner, icons, and live WordPress screenshots) and updated documentation links for v1.1.2.

= 1.1.1 =
* Point Plugin URI to the official plugin page.

= 1.1.0 =
* Added: transient-backed HMAC-SHA256 rate limiting on `POST wm-sensory/v1/calculate-contrast` (`check_public_contrast_access`, default 60 requests/min, bypassed for administrators, never storing raw IP addresses) and strict parameter sanitization.
* Added: opt-in uninstallation lifecycle (`wm_sensory_delete_data_on_uninstall`, default `0`) so stored settings survive plugin updates or accidental removal unless explicitly opted in.
* Added: WordPress 6.5+ Block Bindings source `wm-sensory/tokens` and GenerateBlocks 2.x Dynamic Tag `{{wm_sensory_token}}` (`includes/Integrations/EcosystemBridge.php`).
* Added: developer action `wm_sensory_ready` and filters `wm_sensory_tokens_css`, `wm_sensory_contrast_result`, and `wm_sensory_rest_rate_limit`.
* Added: complete `.po`/`.mo` translations across all 28 bundled locales, CycloneDX 1.6 `bom.json`, and WordPress Playground blueprint.
* Fixed: saving one settings tab switched the other governor off (and the motion tab reset the ring colour); each tab saves its own fields.
* Fixed: invalid colours ("#zzzzzz") returned 21:1 PASS; they are refused with 400 (REST), an error (WP-CLI) or a failed action (hub).
* Fixed: verdicts came from the rounded ratio (4.4989:1 passed AA as 4.5:1); they use the exact ratio.
* Fixed: the calculator opened with a fixed 10.42:1; it shows the computed ratio of its start colours.
* Fixed: the focus-ring colour chosen on the settings page is used in the generated CSS and validated as a six-digit hex colour.
* Admin screen and dashboard widget aligned with the WM Suite design family sheets (`wm-admin-tokens.css`, `wm-admin-ui.css`), dashicons and visible `aria-current` tab state.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.2.2 =
Screenshot 1 retaken with the current WM Suite menu. No code, data or settings change.

= 1.2.1 =
Stylesheet update only (the family sheet of WM Suite Hub 1.2.5). No data or settings change.

= 1.2.0 =
Hub users: update WM Suite Hub to 1.2.0 together with this plugin. The hub contract moved to a new PHP namespace; next to an older hub the plugin keeps working, but it is not listed in the cockpit and its admin page has no menu entry until the hub is updated.

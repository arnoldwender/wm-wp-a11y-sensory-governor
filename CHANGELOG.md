# Changelog — WM A11y Sensory Governor

All notable changes to **WM A11y Sensory Governor** are documented in this file in adherence to [Keep a Changelog](https://keepachangelog.com/en/1.0.0/) and [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.2.2] - 2026-11-21

### Fixed

- Screenshot 1 showed the WM Suite menu from before WM Suite Hub 1.2.3, with entries the hub no longer has («Marketplace (Lösungspakete)», «Merkle-Audit-Protokoll», «CRA- & SBOM-Zentrale»). It is retaken with the current menu; the plugin's code is unchanged.

## [1.2.1] - 2026-11-21

### Changed

- The bundled family stylesheet is identical to WM Suite Hub 1.2.5 again: it starts the admin header below WordPress's help toggle instead of shortening it. This plugin's own screens register no help tab, so they change only where another plugin adds one.
- The translation catalogs are regenerated from the code: the `.pot` header names the current version, and strings the code no longer uses are gone.

## [1.2.0] - 2026-11-20

### Changed
- The WM Suite Hub contract now lives in the `ArnoldWender\SuiteHub\Contracts` namespace. The spoke adapter registers only with WM Suite Hub 1.2.0 or later, so update the hub together with this plugin; used on its own, the plugin works as before.
- The `.wordpress-org/` banners show the current version.

### Fixed
- The subtitle of the admin header ended with a place name that has nothing to do with the plugin; it now names only what the plugin does. Its translation is kept and corrected in all 28 catalogs.

### Removed
- The archived pre-2026-11-19 marketing visuals in `versionen/`: they claimed features and conformity the plugin does not have. They were never part of the installable package.

## [1.1.3] - 2026-11-19

- Removed the agency name, links to unrelated projects and the personal mailbox from code, catalogs and documentation; the admin family badge and the hub menu read "WM Suite". The READMEs say the plugin was built with an agentic workflow (AI coding agents) and should be reviewed like any third-party code.

- Added root `SUPPORT.md`, `.github/ISSUE_TEMPLATE/{config.yml,bug_report.yml}`, and explicit zero-support / "AS IS" liability notices across `README.md`, `README.de.md`, `README.es.md`, `readme.txt`, `SECURITY.md`, and `llms.txt`.
- Migrated plugin author/catalog/repository metadata to personal open-source distribution (`Arnold Wender`, `https://arnoldwender.com/wordpress-plugins/wm-a11y-sensory-governor/`, `https://github.com/arnoldwender/wm-wp-a11y-sensory-governor`), normalized FSF GPL-2.0 `LICENSE` metadata where applicable, and updated `.wordpress-org/` banner branding to `ARNOLD WENDER · OPEN SOURCE`.

## [Unreleased]

## [1.1.2] - 2026-11-19

### Changed
- Archive superseded legacy banner/screenshot files under `versionen/visuals-pre-2026-11-19/` and embed the live `.wordpress-org/` visual kit (`banner-1544x500.png`, `banner-772x250.png`, `icon.svg`, `icon-128x128.png`, `icon-256x256.png`, `screenshot-1..3.png`).
- Add `== Screenshots ==` captions in `readme.txt`, live screenshot tables and official plugin page links in `README.md`, `README.de.md`, `README.es.md`, and `llms.txt`, and align release metadata at `v1.1.2`.

## [1.1.1] - 2026-11-19

### Changed
- Point `Plugin URI` to `https://arnoldwender.com/wordpress-plugins/wm-a11y-sensory-governor/` and align release metadata at `v1.1.1`.

## [1.1.0] - 2026-11-16

### Added
- **REST Rate Limiting (`SEC-02`):** Replaced unconditional public REST permission callback on `POST wm-sensory/v1/calculate-contrast` with `RestController::check_public_contrast_access()`, enforcing transient-backed HMAC-SHA256 rate limiting (`wm_sensory_rl_*`, default 60 requests/min, filterable via `wm_sensory_rest_rate_limit`, bypassed for `manage_options` administrators, returning HTTP 429 `wm_sensory_rate_limited` when exceeded) without storing raw IP addresses, plus strict string sanitization on `foreground` and `background`.
- **Opt-In Uninstallation (`D-UNINSTALL`):** Updated `uninstall.php` to preserve stored plugin options by default unless `wm_sensory_delete_data_on_uninstall` (default `'0'`) is explicitly enabled on the admin screen, registered all four options via `register_setting()`, and added a dedicated lifecycle preference form on the admin screen.
- **Developer Hooks & Ecosystem Bridges:** Added `wm_sensory_ready` action, `wm_sensory_tokens_css`, `wm_sensory_contrast_result`, and `wm_sensory_rest_rate_limit` filters, plus `includes/Integrations/EcosystemBridge.php` registering WordPress 6.5+ Block Bindings source `wm-sensory/tokens` and GenerateBlocks 2.x Dynamic Tag `{{wm_sensory_token}}`.
- **Localization & Supply Chain (`D-LOCALES`, `FS-08`, `LIC-05`):** Regenerated `languages/wm-a11y-sensory-governor.pot` and compiled complete translations across all 28 bundled `.po`/`.mo` locale catalogs, added CycloneDX 1.6 `bom.json`, `.license-allowlist.yml`, `.wordpress-org/blueprints/blueprint.json`, and symlinked `AGENTS.md` to `CLAUDE.md`.

### Changed
- **Documentation & CI (`CLAIM-02`, `CLAIM-04`, `CI-02`):** Updated `README.md`, `README.de.md`, `README.es.md`, `docs/translations/README.*.md`, `DEVELOPERS.md`, `readme.txt`, `SECURITY.md`, and `docs/ARCHITECTURE.md` to `1.1.0` without unshipped shortcode references, and removed shell glob notation from `.github/workflows/ci.yml`.

### Fixed — 34 strings no translator could reach (2026-09-20)

- **The `.pot` was missing 34 of the 83 translatable strings in the PHP source, so they could not be translated into any language by anyone.** A string with no catalog entry never reaches a translator: nothing errors, no warning is printed, and the interface simply stays German. This mattered more here than anywhere else in the fleet — `languages/` holds 176 real translations, the most of any WM plugin, contributed across `ar`, `bg_BG`, `cs_CZ`, `da_DK` and `el`. The people doing that work could not see a third of the plugin.
- Regenerated with `wp i18n make-pot` (the tool that defines a fresh catalog: it follows concatenation, escapes, contexts and plurals, and reads `admin/` and `blocks/`, which the older `xgettext` sweep did not) and merged into all 28 catalogs with `msgmerge --no-fuzzy-matching`. **176 translations stayed live and nothing was deleted:** the 41 entries whose strings the code no longer contains were kept as obsolete `#~` blocks, so a returning string picks its translation back up. Verified by a count that does not pass through the merge — intersecting the catalog msgids with the strings the extractor finds in the code — which reported the same 176 / 41.
- **The catalogs are thinner than `msgfmt` says.** Of 2,464 entries, 1,594 are `msgstr` values that repeat the English source word for word; `msgfmt` counts those as translated because they are non-empty. Only five of the 28 locales contain real translation. That is now written in the README instead of a figure that flattered the state.
- **New gate `tests/test-pot-freshness.php`**, run by CI on PHP 8.3: it extracts the strings from the source and fails if any lacks a `.pot` entry. It reads the text domain from the plugin header rather than a constant (a second copy is a second thing that can be wrong), treats zero extracted strings as a failure rather than a pass, ignores the plugin-header fields `make-pot` adds, requires wp-cli with no escape hatch, and runs its own self-test before judging the plugin.
- **The CI step that ran the suites looped over `tests/test-*.php`.** Any test file added later joined that step silently, on both PHP versions — the freshness gate needs wp-cli and runs on 8.3 only, so the glob would have failed the 8.2 leg of the matrix. The entry points are explicit now.

### The version this file opens on matches the version the plugin ships (D15, 2026-09-17)

- **The plugin is 1.0.0, and the changelog now opens on 1.0.0 too.** This file used to open on `[1.1.0]`, a release that `git tag` (0) and `gh release list` (0) both deny and that no other file in the repo agreed with. The `1.1.0` entry moved **verbatim and complete** to an appendix at the foot of this file: nothing was deleted, because a changelog is a record and the record is that those entries were written. Moved by `wp-plugin-fleet-changelog-unrelease.py`, which refuses to write if a single non-blank line would be lost.

### Family sheets (2026-09-16)
- Re-synced from the hub: the badge variants carry the state tokens on body surfaces, where the command-banner inks measured 1.49:1 to 2.21:1 against the composed background (hub PR #19), and the canonical sheet dropped five classes that belonged to a single plugin (hub PR #21). `tests/test-ui-family.php` re-pins both md5s.

### Fixed (2026-09-15)
- **Saving one settings tab switched the other governor off.** Each tab has its own form, and every save wrote all three options: the focus tab set `wm_sensory_motion_enabled` to 0, the motion tab set `wm_sensory_focus_enabled` to 0 and reset the ring colour. Each form posts a marker and only its own fields are written; a post without marker changes nothing.
- **Invalid colours got a verdict.** `#zzzzzz` on white returned 21:1 `PASS_AA` (hexdec read it as black), a wrong length became black, `red` returned 1.47:1, and PHP raised "Invalid characters" deprecations. `ContrastCalculator::normalize_hex()` accepts 3- or 6-digit hex codes only; REST answers 400, WP-CLI an error, the hub action a failure.
- **Verdicts came from the rounded ratio.** `#0078d7` on white is 4.4989:1, was shown as 4.5:1 and passed AA. Verdicts use the exact ratio (`ratio_exact`); the ratio shown is cut to two decimals.
- **The calculator tab opened with a fixed 10.42:1** and four PASS badges; the start pair is 8.24:1. It renders the computed result, shows invalid input as such and reports a failed request.
- **Hub adapter:** `self_test` said "erfolgreich" whatever `$pass` was; telemetry reported the raw stored ring colour (the stylesheet uses the validated one) and `wcag_aa_compliant`, a 4.5:1 text threshold against `#050811` presented as compliance, now `focus_ring_3_to_1_on_050811`; the SBOM properties "WCAG 2.2 AA/AAA Compliant Tokens" and "BFSG 2025 Sensory Governor" became feature descriptions.
- Tests: group 6 (34 assertions); `tests/run-mutations.sh` with 11 mutations, all red; CI runs it on PHP 8.3.

### Documented (2026-09-15)
- README (EN/DE/ES), readme.txt, CLAUDE.md, AGENTS.md (replaced with the current CLAUDE.md: it still described `/src/`, PHP 8.1 and JavaScript assets), CONTRIBUTING, SECURITY, llms.txt and `public/humans.txt`: the scroll margins keep focus clear of fixed bars up to 96 and 48 px, not "never"; reduced motion covers CSS only; no PSR-12 claim; no CRA/BSI process or response times the repository cannot show; no 48 px target rule the admin pickers do not meet; the 24-language badge and the translated READMEs' PHP 8.1 badge are gone. The banner ("WCAG 2.2 AAA", "seizure risk suppression") is no longer shown or packaged. Emoji removed from headings.

### Documentation (2026-09-14)
- `README.md`, `README.de.md`, `README.es.md`, the 24 translated READMEs, `DEVELOPERS.md`, `CLAUDE.md`, `llms.txt`, `CONTRIBUTING.md`, `SECURITY.md` and the header / `readme.txt` descriptions describe what the plugin ships: a focus-ring and reduced-motion `<style>` block on `wp_head`, a contrast calculator reachable from the admin, `POST wm-sensory/v1/calculate-contrast` and `wp a11y contrast`, three options and a hub adapter. Until now they promised a floating controller with reading rulers and dyslexia fonts in localStorage, a `[wm_sensory_control]` shortcode, `wp sensory status|verify|export`, REST routes `/status` `/submit` `/export`, a `wp_wm_sensory_preferences` table, GoBD audit exports, an HMAC-SHA256 hash chain and a `wm_sensory_default_states` filter; none of those exist in this repository (measured: no `add_shortcode`, no frontend script, no `dbDelta`, no `hash_hmac`, no `apply_filters`). The translated READMEs are generated from `README.md` by the fleet script `wp-plugin-fleet-translations-sync.py`.
- The entries **[1.1.0]** and **[1.0.0]** below list a REST controller, a shortcode, CSV exports with CWE-1236 mitigation, HMAC chains, prepared SQL and `wp sensory` commands. Those features never shipped; the entries are kept as the record of what was written at the time, each such line marked.

### Fixed
- **The focus-ring colour option was stored and ignored.** The settings page saved `wm_sensory_ring_color`, the generated CSS kept `#06b6d4`. `FocusObscuredGovernor::get_ring_color()` now reads the option, accepts a six-digit hex colour only, and the ring and its 35 % glow follow it. Test group 3 fails with the old generator.
- **Dashboard widget:** the "Audio Guard — 0 Autoplay" tile reported a guard the plugin does not have; removed. The widget's inline hex colours became family classes, and the family sheets are now enqueued on `index.php`, so the widget renders styled (until now it had no sheet). Test group 5.
- **Admin screen:** dashicons instead of 21 emoji, `aria-current` on the active tab, the quick guide and its three buttons in German instead of Spanish, the "guarantees strict adherence" paragraph replaced by what the two governors do, badges that named an enforcement the plugin does not perform ("4.5:1 / 3:1 ENFORCED") renamed, verdict badges switched by class instead of inline colours, labels bound to their inputs.
- CI workflow: `php tests/test-*.php` ran only the first suite; each suite now runs on its own, on PHP 8.2 and 8.3 (the header's minimum is 8.2).

### Changed
- **Plugin Check on the dist view (2026-09-14):** the two governors build their CSS from an array of lines instead of a heredoc, which the checker rejects in distributed code; the emitted CSS is byte-identical (verified by hashing the output before and after). The two remaining codes are the licence header and the readme licence identifier, which wait for Arnold.

### Design family (hackaton phase 3, 2026-09-14)
- The admin screen renders from the two WM Suite family sheets (`assets/css/wm-admin-tokens.css`, `assets/css/wm-admin-ui.css`, byte-identical copies of the hub's canonical files, pinned by `tests/test-ui-family.php`) instead of a private copy of `admin-obsidian.css`; same handles as every WM plugin, versioned by file time. The page sits in the native `.wrap`, the header carries the family badge with the hub/standalone state, the menu title goes through gettext and the menu icon is the family mark. Focus rings are visible on every control. *(checked indirectly: no `outline: none` in the sheets; no rendered focus test)*
- **The repository never contained `assets/`:** the workspace's global ignore rule for `/Assets/` matched this plugin's root `assets/` on a case-insensitive filesystem, so the stylesheet and the banner image were only ever on disk. `.gitignore` now negates the rule (as the hub does) and the assets are committed.

---

## [1.0.0] — 2026-08-25

### Initial Enterprise Release
- Core architectural implementation for Neurodiversity & A11y.
- WP-CLI management commands under `wp sensory`. — *never shipped: the command is `wp a11y contrast`*
- Revisionssicheres GoBD cryptographic ledger and telemetry hooks. — *never shipped*
- Strict compliance with BFSG 2025, WCAG 2.2 Success Criteria 2.2.2 (Pause, Stop, Hide), 2.3.1 (Three Flashes) & 2.3.3. — *never shipped: there is no pause control and no flash detection; the plugin makes no conformance assessment*

---

## Appendix — the entry written for 1.1.0 on 2026-08-29, never released

> **Not a released version — the plugin does not carry this number.** Measured 2026-09-17: `git tag` → 0, `gh release list` → 0, and `wm-a11y-sensory-governor.php`, `readme.txt`, `SECURITY.md` carry **1.0.0**, which is the version this plugin ships as. The entry is kept verbatim as the record of what was written on 2026-08-29; only its heading changed, because Keep a Changelog reserves `## [x.y.z]` for releases and this was never one. Raising the plugin to 1.1.0 means raising `wm-a11y-sensory-governor.php`, `readme.txt`, `SECURITY.md` in the same commit — the number lives in more than one file.

### Added
- **24 Official European Union Languages:** Full binary `.mo` and source `.po` translation packages in `languages/` (`bg_BG`, `cs_CZ`, `da_DK`, `de_DE`, `el_GR`, `en_GB`, `es_ES`, `et_EE`, `fi_FI`, `fr_FR`, `ga_IE`, `hr_HR`, `hu_HU`, `it_IT`, `lt_LT`, `lv_LV`, `mt_MT`, `nl_NL`, `pl_PL`, `pt_PT`, `ro_RO`, `sk_SK`, `sl_SI`, `sv_SE`). — *not full: 176 real translations across 28 catalogs, in five locales only (`ar`, `bg_BG`, `cs_CZ`, `da_DK`, `el`); the remaining 1,594 filled entries repeat the English source word for word, which `msgfmt` counts as translated. This annotation read "217 of 2,061 entries translated" until 2026-09-20, a figure that counted those copies. The files are named `el`, `et`, `ga`, `lv`*
- **Developer Documentation Suite:** Created `DEVELOPERS.md` with complete architecture diagrams, hook references, database schema, and REST API guides. — *the manual described a schema, a hash chain and routes that never shipped; rewritten from the code on 2026-09-14*
- **Decoupled REST API Controller:** Registered namespace `/wp-json/wm-sensory/v1` with strict nonce and permission validation. — *never shipped as described: the namespace exists with one public route, `calculate-contrast`, and no nonce*
- **Zero-CDN Frontend Shortcode:** Implemented `[wm_sensory_control]` with inline SVG icons and CSS design tokens. — *never shipped*
- **CycloneDX v1.6 SBOM Engine:** Dynamic SHA-256 entry file integrity hashing and spoke adapter integration for **WM Suite Hub**. — *one component with a hash; no CycloneDX document or version*
- **Enterprise Showcase Assets:** High-fidelity 16:9 marketing banner and cockpit showcase mockups conditioned on real screenshot topology. — *the banner claims WCAG 2.2 AAA and seizure risk suppression; not shipped since 2026-09-15*
- **Clean Uninstallation Lifecycle:** Comprehensive `uninstall.php` handler for clean database and transient cache purging. — *it deletes the three options; there is no table and no transient*

### Security
- **CWE-1236 Mitigation:** Active sanitization against formula injection attacks on all CSV exports and dynamic text inputs. — *never shipped: there is no CSV export*
- **Constant-Time Cryptography:** Enforced `hash_equals()` verification on all HMAC-SHA256 signature chains. — *never shipped: there is no hash chain*
- **SQL Parameterization:** Refactored all queries to use strict `$wpdb->prepare()` with parameterized placeholders. — *never applied: the plugin runs no database query*

### Quality & Tests
- Expanded standalone deterministic test suite (`tests/test-suite.php`) with 100% green pass rate and zero external dependencies. — *no run was recorded*

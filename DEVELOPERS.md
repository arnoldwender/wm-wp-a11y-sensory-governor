# WM A11y Sensory Governor — Developer Manual

> Everything below is measured against the code in this repository (`v1.1.2`).

---

## 1. Layout

| Path | Role |
| --- | --- |
| `wm-a11y-sensory-governor.php` | Header, constants (`WM_SENSORY_GOVERNOR_VERSION` / `_FILE` / `_PATH` / `_URL`), PSR-4 autoloader for `WMCyber\A11y\Governor\` → `includes/`, hub spoke registration, `plugins_loaded` boot. |
| `includes/Core/Plugin.php` | Singleton. Text domain on `init`, the `<style>` block on `wp_head` (priority 999), `EcosystemBridge`, `AdminDashboard` in the admin, the REST controller on `rest_api_init`, the WP-CLI command when `WP_CLI` is defined, and `wm_sensory_ready` action. |
| `includes/Governor/FocusObscuredGovernor.php` | `get_ring_color()` (option, validated) and `get_focus_css()`. |
| `includes/Governor/VestibularMotionGovernor.php` | `get_motion_css()`. |
| `includes/Calculator/ContrastCalculator.php` | `normalize_hex()` (3- or 6-digit hex, otherwise `null`) and `calculate_contrast( $fg, $bg )`: hex → sRGB → relative luminance → ratio and verdicts (filtered via `wm_sensory_contrast_result`); throws `InvalidArgumentException` for anything that is not a hex code. |
| `includes/API/RestController.php` | One route, `wm-sensory/v1/calculate-contrast`, guarded by `check_public_contrast_access()` (transient HMAC-SHA256 rate limiter). |
| `includes/CLI/SensoryCommand.php` | `wp a11y contrast`. |
| `includes/Admin/AdminDashboard.php` | Menu, five-tab screen, `register_settings()`, per-form settings save, dashboard widget, family sheet enqueue. |
| `includes/Integrations/EcosystemBridge.php` | WP 6.5+ Block Bindings (`wm-sensory/tokens`) and GenerateBlocks 2.x Dynamic Tag (`{{wm_sensory_token}}`). |
| `includes/Adapters/Sensory_Spoke_Adapter.php` | WM Suite Hub contract (`WM_Plugin_Module_Interface`); loaded only when the hub's interface exists. |
| `assets/css/` | `wm-admin-tokens.css`, `wm-admin-ui.css`: byte-identical copies of the hub's canonical sheets, pinned by `tests/test-ui-family.php`. No plugin layer, no frontend JavaScript file. |
| `languages/` | 28 `.po` / `.mo` catalogs and `wm-a11y-sensory-governor.pot`. |
| `bom.json` | CycloneDX 1.6 SBOM (`GPL-2.0-or-later`, SHA-256 of main entry file). |
| `uninstall.php` | Opt-in uninstaller: preserves options by default unless `wm_sensory_delete_data_on_uninstall` is `'1'`, in which case it deletes all four options and rate-limit transients. |

---

## 2. Options

| Option | Default | Read by |
| --- | --- | --- |
| `wm_sensory_focus_enabled` | `'1'` | `Plugin::inject_governor_styles()`, `EcosystemBridge`, admin screen, widget, adapter telemetry |
| `wm_sensory_motion_enabled` | `'1'` | `Plugin::inject_governor_styles()`, `EcosystemBridge`, admin screen, widget, adapter telemetry |
| `wm_sensory_ring_color` | `#06b6d4` | `FocusObscuredGovernor::get_ring_color()` (six-digit hex or the default), admin screen, `EcosystemBridge`, adapter telemetry |
| `wm_sensory_delete_data_on_uninstall` | `'0'` | `AdminDashboard::render_dashboard()`, `uninstall.php` |

All four options are registered via `register_setting( 'wm_sensory_governor_settings', ... )` and written by `AdminDashboard::save_settings()` on `admin_init` after `wp_verify_nonce( 'wm_sensory_save_action' )` and `current_user_can( 'manage_options' )`. Each form posts `wm_sensory_form`:
- `focus` writes `wm_sensory_focus_enabled` and `wm_sensory_ring_color` (through `sanitize_hex_color()`, fallback to `#06b6d4`);
- `motion` writes `wm_sensory_motion_enabled`;
- `lifecycle` writes `wm_sensory_delete_data_on_uninstall`;
- anything else writes nothing.

---

## 3. Output on the frontend

`Plugin::inject_governor_styles()` prints on `wp_head` (999), when at least one governor is on:

```css
<style id="wm-sensory-governor-tokens">
/* WM A11y Sensory Governor — WCAG 2.2 2.4.11 Focus Not Obscured & High-Visibility Focus Ring */
:root {
  --wm-focus-offset: 96px;
  --wm-focus-ring-color: #06b6d4;
  --wm-focus-ring-width: 3px;
  --wm-focus-ring-offset: 2px;
}
:focus-visible, a:focus-visible, button:focus-visible, input:focus-visible, textarea:focus-visible,
select:focus-visible, summary:focus-visible, [tabindex]:focus-visible {
  scroll-margin-top: var(--wm-focus-offset, 96px) !important;
  scroll-margin-bottom: 48px !important;
  outline: var(--wm-focus-ring-width) solid var(--wm-focus-ring-color) !important;
  outline-offset: var(--wm-focus-ring-offset) !important;
  box-shadow: 0 0 0 4px rgba(6, 182, 212, 0.35) !important;
  transition: outline-offset 0.1s ease-in-out !important;
}
/* WM A11y Sensory Governor — WCAG 2.2 2.3.3 Animation from Interactions & Vestibular Safety */
@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after { animation-duration: 0.001ms !important; animation-iteration-count: 1 !important; transition-duration: 0.001ms !important; scroll-behavior: auto !important; }
  .wm-parallax, .hero-animation, .marquee-track, .splide__track { animation: none !important; transform: none !important; }
}
</style>
```

Each governor's block is included only while its option is `'1'`. Before printing, the combined CSS passes through `apply_filters( 'wm_sensory_tokens_css', $css, $focus_enabled, $motion_enabled )` and `wp_strip_all_tags()`.

---

## 4. REST

| Method | Route | Permission | Body | Response |
| --- | --- | --- | --- | --- |
| `POST` | `/wp-json/wm-sensory/v1/calculate-contrast` | `[ RestController, 'check_public_contrast_access' ]` | JSON `foreground`, `background` (hex, 3 or 6 digits) | `200 { success: true, data: { ratio, ratio_exact, ratio_string, wcag_aa_normal, wcag_aa_large, wcag_aaa_normal, wcag_aaa_large, ui_components, verdict } }`; `400` on missing/invalid hex; `429` (`wm_sensory_rate_limited`) when rate limit is exceeded |

`check_public_contrast_access()` bypasses rate limiting for `manage_options` users and applies a transient counter (`wm_sensory_rl_<28-hex>`, 60-second window, default 60 requests/min, filterable via `wm_sensory_rest_rate_limit`) derived from `hash_hmac( 'sha256', $ip, wp_salt( 'auth' ) )` so raw IP addresses are never stored.

---

## 5. WP-CLI

```bash
wp a11y contrast <foreground> <background>     # e.g. wp a11y contrast #ffffff #050811
```

Prints the ratio and the PASS / FAIL / WARN verdicts for AA normal, AA large, AAA normal and UI components; a colour that is not a hex code ends with `WP_CLI::error` (exit 1).

---

## 6. Hooks & Ecosystem Bridges

### Hooks this plugin attaches to

| Hook | Where | Effect |
| --- | --- | --- |
| `plugins_loaded` | main file | `Plugin::instance()->boot()` |
| `init` | `Plugin`, `EcosystemBridge` | `load_plugin_textdomain()`, Block Bindings & GenerateBlocks registration |
| `wp_head` (999) | `Plugin` | `<style id="wm-sensory-governor-tokens">` |
| `rest_api_init` | `Plugin` | REST route registration |
| `admin_menu`, `admin_enqueue_scripts`, `admin_init`, `wp_dashboard_setup` | `AdminDashboard` | menu, family sheets, `register_settings()`, settings save, widget |
| `generateblocks_dynamic_tag_output` | `EcosystemBridge` | resolves `wm_sensory_token` output |
| `wm_register_suite_module` (filter) | main file | appends `Sensory_Spoke_Adapter` when the hub's interface exists |

### Developer hooks exposed by this plugin

| Hook | Type | Signature | Description |
| --- | --- | --- | --- |
| `wm_sensory_ready` | Action | `do_action( 'wm_sensory_ready', Plugin $plugin )` | Fired once `Plugin::boot()` finishes wiring hooks. |
| `wm_sensory_tokens_css` | Filter | `apply_filters( 'wm_sensory_tokens_css', string $css, bool $focus_enabled, bool $motion_enabled )` | Filters the CSS printed into `<style id="wm-sensory-governor-tokens">`. |
| `wm_sensory_contrast_result` | Filter | `apply_filters( 'wm_sensory_contrast_result', array $result, string $fg, string $bg )` | Filters the computed contrast result array returned by `ContrastCalculator::calculate_contrast()`. |
| `wm_sensory_rest_rate_limit` | Filter | `apply_filters( 'wm_sensory_rest_rate_limit', int $limit, WP_REST_Request $request )` | Filters the per-minute unauthenticated request limit on `wm-sensory/v1/calculate-contrast` (default `60`; `<= 0` disables throttling). |

### Block Bindings & GenerateBlocks 2.x

- **WordPress 6.5+ Block Bindings:** source `wm-sensory/tokens`, argument `key` (`ring_color`, `ring_contrast`, `focus_status`, `motion_status`).
- **GenerateBlocks 2.x Dynamic Tag:** `{{wm_sensory_token}}` (supports `key` option with the same four keys).

---

## 7. Admin & WM Suite Hub spoke

- **Menu:** `add_menu_page( 'wm-sensory-governor', …, family icon, 83 )`. With the WM Suite Hub active, the hub moves the entry under *WM Suite*.
- **Screen:** `render_dashboard()` — family header with hub/standalone badge, German quick guide, five tabs (`?tab=telemetry|calculator|focus|motion|statutory`, `aria-current` on the active one), nonce-protected forms (`wm_sensory_save_action`).
- **Hub spoke (`Sensory_Spoke_Adapter`):** slug `wm-a11y-sensory-governor`, `GPL-2.0-or-later` CycloneDX component metadata with SHA-256 of the main file, telemetry metrics, and `self_test` / `calculate_contrast` actions.

---

## 8. Tests

```bash
php tests/test-suite.php                              # contrast maths, CSS, adapter, admin, per-tab save, opt-in uninstall, REST rate limit, hooks, bridges
php tests/test-pot-freshness.php                      # every translatable string has a .pot entry (needs wp-cli)
bash tests/run-mutations.sh                           # each entry in tests/mutations.php undoes one fix in a copy; the suite must turn red
wp eval-file tests/wp-runtime-sensory.php --user=1    # inside WordPress: REST, WP-CLI, calculator tab, site head, per-tab save, hub adapter
```

---

## 9. Security disclosure & License

See [SECURITY.md](SECURITY.md). Licensed under **GPLv2 or later** (`GPL-2.0-or-later`).

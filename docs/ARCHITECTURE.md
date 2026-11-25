# Architecture — WM Accessibility Sensory Governor & Contrast Tokenizer
`wm-a11y-sensory-governor` · version `1.1.0` from the plugin header · WordPress 6.4+ · PHP 8.2+ · text domain `wm-a11y-sensory-governor`

Every figure in the measured section below comes from the code, not from any document — the commands are given so they can be re-run.

```bash
# the inventory in this file
python3 the fleet's architecture inventory tool wm-a11y-sensory-governor
```

---

## What runs where

```
wp_head            Governor prints a <style> block: focus ring + reduced motion
                     built from the stored governor options, filtered via wm_sensory_tokens_css
                     the CSS goes out through wp_strip_all_tags(), NOT esc_html()

Calculator         colour contrast ratio (filtered via wm_sensory_contrast_result)
                     admin tab (inline script) · rate-limited POST wm-sensory/v1/calculate-contrast
                     · wp a11y contrast

Ecosystem          WP 6.5+ Block Bindings (wm-sensory/tokens) + GenerateBlocks 2.x Dynamic Tag ({{wm_sensory_token}})

hub adapter        registered through wm_register_suite_module; with the hub active the
                   menu moves under *WM Suite*
```

No shortcode, no custom table, no frontend script. Four options (with opt-in deletion on uninstall) and a `<style>` block are the runtime footprint.

---

## Wiring — what to touch for a given change

| Change | Files, in order | Trap |
|---|---|---|
| Add a setting | `includes/Admin/` `register_settings()` + form → `save_settings()` → read it in consumer → `uninstall.php` (inside opt-in guard) → assertion + mutation | **`save_settings()` writes only the fields of the form that was posted.** Each form posts `wm_sensory_form`; writing an option whose field is not in the posted form silently resets the other tabs |
| Change the emitted CSS | `includes/Governor/` + `Plugin::inject_governor_styles()` | it is escaped with `wp_strip_all_tags()`, not `esc_html()` — `esc_html()` would turn `>` into `&gt;` and break every child selector |
| Touch the contrast maths | `includes/Calculator/` | colours are hex or **refused**; verdicts use the exact ratio, never a rounded one. 4.499 is not 4.5 |
| Add an admin icon | dashicons | **no emoji** and no inline colour in the widget — group 5 fails on either. The calculator's preview box is the one place inline colour is allowed, because it is set from the user's own inputs |

---

## Fragile spots

1. **`save_settings()` writes only the posted form's fields.** See the wiring table; this is the shape that loses the other tabs' settings.
2. **Opt-in uninstallation (`uninstall.php`).** Stored options survive plugin deletion unless `wm_sensory_delete_data_on_uninstall` is explicitly `'1'`.
3. **The generated CSS is stripped, not HTML-escaped.**
4. **Contrast verdicts use the exact ratio.** Rounding first turns a fail into a pass at the boundary, which is the only place the number matters.
5. **The historical marketing banner in `assets/images/`** is not used by the plugin and is excluded from the distributed package via `.distignore`.

---

<!-- wm-measured-inventory -->

## Measured inventory

Regenerated from the code, never hand-edited. Everything above this line is written by hand and stays.

```bash
python3 the fleet's architecture inventory tool wm-a11y-sensory-governor --append-to docs/ARCHITECTURE.md
```

## Inventory by layer

**11 PHP files, 1397 lines** (excluding `tests/`, `vendor/`, `node_modules/`, `languages/`).

| Layer | Files | Lines |
|---|---:|---:|
| `(raíz)` | 2 | 112 |
| `includes/API` | 1 | 109 |
| `includes/Adapters` | 1 | 150 |
| `includes/Admin` | 1 | 531 |
| `includes/CLI` | 1 | 52 |
| `includes/Calculator` | 1 | 102 |
| `includes/Core` | 1 | 94 |
| `includes/Governor` | 2 | 102 |
| `includes/Integrations` | 1 | 145 |

---

## Classes

| Class | File |
|---|---|
| `AdminDashboard` | [`includes/Admin/AdminDashboard.php`](../includes/Admin/AdminDashboard.php) |
| `ContrastCalculator` | [`includes/Calculator/ContrastCalculator.php`](../includes/Calculator/ContrastCalculator.php) |
| `EcosystemBridge` | [`includes/Integrations/EcosystemBridge.php`](../includes/Integrations/EcosystemBridge.php) |
| `FocusObscuredGovernor` | [`includes/Governor/FocusObscuredGovernor.php`](../includes/Governor/FocusObscuredGovernor.php) |
| `Plugin` | [`includes/Core/Plugin.php`](../includes/Core/Plugin.php) |
| `RestController` | [`includes/API/RestController.php`](../includes/API/RestController.php) |
| `SensoryCommand` | [`includes/CLI/SensoryCommand.php`](../includes/CLI/SensoryCommand.php) |
| `Sensory_Spoke_Adapter` | [`includes/Adapters/Sensory_Spoke_Adapter.php`](../includes/Adapters/Sensory_Spoke_Adapter.php) |
| `VestibularMotionGovernor` | [`includes/Governor/VestibularMotionGovernor.php`](../includes/Governor/VestibularMotionGovernor.php) |

---

## Entry points

**REST routes:** `/calculate-contrast`

**WP-CLI:** `a11y`

---

## Persistence — who writes each key and who reads it

| Key | Written by | Read by |
|---|---|---|
| `wm_sensory_delete_data_on_uninstall` | `includes/Admin/AdminDashboard.php` | `includes/Admin/AdminDashboard.php`, `uninstall.php` |
| `wm_sensory_focus_enabled` | `includes/Admin/AdminDashboard.php` | `includes/Adapters/Sensory_Spoke_Adapter.php`, `includes/Admin/AdminDashboard.php`, `includes/Core/Plugin.php`, `includes/Integrations/EcosystemBridge.php` |
| `wm_sensory_motion_enabled` | `includes/Admin/AdminDashboard.php` | `includes/Adapters/Sensory_Spoke_Adapter.php`, `includes/Admin/AdminDashboard.php`, `includes/Core/Plugin.php`, `includes/Integrations/EcosystemBridge.php` |
| `wm_sensory_ring_color` | `includes/Admin/AdminDashboard.php` | `includes/Governor/FocusObscuredGovernor.php` |

Every key has exactly one writing layer and at least one reader.


---

## Gates

- `tests/mutations.php`
- `tests/run-mutations.sh`
- `tests/test-pot-freshness.php`
- `tests/test-suite.php`
- `tests/test-ui-family.php`
- `tests/wp-runtime-sensory.php`
- `.github/workflows/ci.yml`

What CI actually runs is in the workflow files above — a test file that no workflow invokes is not a gate.

---

## Drift detected against the documentation

Nothing measurable: versions agree and every class named in the documents exists.


# Contributing to WM A11y Sensory Governor

Thank you for your interest in contributing to **WM A11y Sensory Governor**.

---

## Zero Support & Issue Scope

**Zero technical, configuration, or private support is provided.** See [SUPPORT.md](SUPPORT.md):

- Report reproducible code defects **exclusively** via public [GitHub Issues](https://github.com/arnoldwender/wm-wp-a11y-sensory-governor/issues).
- **Do not** contact the author privately via email, `arnoldwender.com`, or direct messages — private inquiries are never answered.
- Issues and pull requests carry no response or maintenance SLA, and the software is provided strictly **"AS IS"** without warranty or liability.

---

## Core Development Standards

1. **PHP 8.2+ Strict Typing:** Every PHP file must start with `declare(strict_types=1);` (the header declares `Requires PHP: 8.2`).
2. **Zero-CDN Architecture:** No external resources (CDNs, Google Fonts, external telemetry) may be loaded at runtime.
3. **Docs measured against the code:** `README.md` and `DEVELOPERS.md` describe what the plugin registers. A new command, route, option or hook is documented in the same pull request; nothing is documented that does not exist.
4. **Admin family:** dashicons instead of emoji, family classes instead of inline colours, the two family sheets never edited in this repository (they are copies of the hub's canonical files).
5. **Contrast and targets:** text in the admin screen at least 4.5:1 against its background; controls at least 44 × 44 px where the markup is ours (the colour pickers are 44 × 38 px today). A change to the calculator keeps the verdicts on the exact ratio.

---

## Development Workflow

```bash
# 1. Clone the repository
git clone https://github.com/arnoldwender/wm-wp-a11y-sensory-governor.git
cd wm-a11y-sensory-governor

# 2. Run the standalone suites (no WordPress needed)
php tests/test-suite.php
bash tests/run-mutations.sh

# 3. Plugin Check before a pull request (the plugin-check plugin, in a WordPress install)
wp plugin check wm-a11y-sensory-governor
```

---

## Commit Conventions

- Use conventional format: `[Feature] ...`, `[Fix] ...`, `[Security] ...`, `[Docs] ...`.
- A fix comes with an assertion in `tests/test-suite.php` that fails without it and an entry in `tests/mutations.php`.

For support and liability rules, read [SUPPORT.md](SUPPORT.md).

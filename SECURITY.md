# Security Policy — WM Accessibility Sensory Governor & Contrast Tokenizer

## Supported Versions

| Version | Supported |
| --- | --- |
| `1.2.x` (`1.2.2`) | Current release branch |

---

## Reporting a Vulnerability

Please **do not** open a public GitHub issue for security vulnerabilities, and **do not** send private inquiries or support emails to `arnoldwender.com`, email addresses, or social/direct messages (private channels are unmonitored for open-source plugins).

Submit security reports **exclusively** via GitHub Private Vulnerability Reporting:

- **Security Advisories:** [github.com/arnoldwender/wm-wp-a11y-sensory-governor/security/advisories/new](https://github.com/arnoldwender/wm-wp-a11y-sensory-governor/security/advisories/new)

> **Best-Effort Only / Zero Warranty / Zero Support:** This open-source repository carries **zero technical or user support** ([SUPPORT.md](SUPPORT.md)) and **zero response-time SLA**. Advisory reports may be reviewed at the author's sole discretion without obligation, timeline, or liability ("AS IS" under [LICENSE](LICENSE)).

### What to Include:
1. Proof of Concept (PoC) script or detailed reproduction steps.
2. Affected component: the REST route `wm-sensory/v1/calculate-contrast`, the admin screen and its settings save, the `wp a11y contrast` command, or the CSS printed on `wp_head`.
3. Potential impact assessment (e.g., stored option reaching the stylesheet unvalidated, privilege escalation on the settings save, XSS in the admin screen).

### Response
Reports are answered by the author; there is no staffed security team, no fixed response time and no CVE numbering arrangement. The previous edition promised a 24-hour acknowledgement, a 72-hour patch and CVE attribution "in accordance with CRA Art. 14 and BSI Guidelines", none of which is backed by a process in this repository.

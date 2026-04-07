# AODN Changelog Logger

**Automatically track every WordPress core, plugin, and theme update.** Know exactly what changed, when, and who did it.

[![WordPress](https://img.shields.io/badge/WordPress-6.4%2B-blue)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-purple)](https://php.net)
[![License](https://img.shields.io/badge/License-GPL%20v2-green)](https://www.gnu.org/licenses/gpl-2.0.html)

---

## What It Does

WordPress updates happen constantly — auto-updates at 3 AM, theme patches, core version bumps. When something breaks, the question is always: **what changed, and when?**

AODN Changelog Logger answers that. Install it and every update is recorded automatically:

- **Version history** — Logs every update from → to (e.g., 2.4.1 → 2.5.0)
- **Timestamps** — Exact date and time for every change
- **User attribution** — Which admin or automated process triggered the update
- **Auto vs. manual detection** — Distinguish between your clicks and WP-Cron
- **Filter & search** — Slice by update type, date range, or keyword
- **CSV export** — Clean reports for clients, audits, or your own records
- **Auto-purge** — Set retention and old entries clean themselves up
- **Zero frontend impact** — Admin-only, no page speed effect
- **No external dependencies** — Nothing phones home, no tracking, no third-party services

## Screenshots

### Update Log
![Update log dashboard](https://aiordienow.com/wp-content/uploads/2026/04/screenshot-1-update-log.png)

### Settings
![Settings page](https://aiordienow.com/wp-content/uploads/2026/04/screenshot-2-settings.png)

## Installation

### From GitHub

1. Download the [latest release](https://github.com/warkitten785/aodn-changelog-logger/releases/latest)
2. Upload the zip via **WordPress Admin → Plugins → Add New → Upload Plugin**
3. Activate — logging starts immediately

### Manual

1. Clone or download this repo
2. Copy the folder to `/wp-content/plugins/aodn-changelog-logger/`
3. Activate through **Plugins** in WordPress admin

No configuration required. It just works.

## Requirements

- WordPress 6.4+
- PHP 8.2+

## Who It's For

- **Freelancers** managing client sites who need a paper trail
- **Agencies** that want to document every update across every site
- **Site owners** tired of wondering why something broke after an update
- **Developers** who want an audit log without installing a bloated security suite

## What It Costs

**Nothing.** Free forever. No pro version. No upsell. No license key. No account required. No feature walls.

Download it, install it, use it on as many sites as you want.

## Contributing

This is open source under GPL v2. Fork it, customize it, submit PRs. If you find a bug, [open an issue](https://github.com/warkitten785/aodn-changelog-logger/issues).

## Support

- [AI Or Die Now](https://aiordienow.com) — our home base
- [Product page](https://aiordienow.com/arsenal/aodn-changelog-logger/) — full description and free download
- [Report an issue](https://github.com/warkitten785/aodn-changelog-logger/issues)

## Changelog

### 1.1.0
- Modernized codebase — PHP 8.2+ with strict types
- Redesigned admin UI with premium card-based layout
- Improved settings page with modern toggle controls
- Added proper deactivation cleanup
- Added uninstall.php for clean removal
- Better responsive design

### 1.0.0
- Initial release

## License

GPL v2 or later. See [LICENSE](LICENSE).

---

Built by [AI Or Die Now](https://aiordienow.com) — tools that work without asking for anything back.

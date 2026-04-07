=== AODN Changelog Logger ===
Contributors: aiordienow
Tags: changelog, update log, plugin updates, audit trail, version history
Requires at least: 6.4
Tested up to: 6.7
Requires PHP: 8.2
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically logs every WordPress core, plugin, and theme update with version history, timestamps, and user attribution.

== Description ==

**AODN Changelog Logger** keeps a complete audit trail of every update on your WordPress site — plugins, themes, and WordPress core. Know exactly what changed, when it changed, who changed it, and whether it was manual or an automatic update.

Perfect for:
* **Freelancers & agencies** managing client sites — send monthly update reports in seconds.
* **Site owners** who want to know why something broke after an update.
* **Developers** maintaining strict version control and documentation.
* **Anyone** running WP auto-updates who wants visibility into what's happening.

= Features =

* **Automatic logging** — hooks into WordPress update events, nothing to configure
* **Tracks all update types** — plugins, themes, and WordPress core
* **Version history** — shows what version something was updated *from* and *to*
* **User attribution** — know who triggered each update (or if it was auto-update)
* **Auto vs. Manual** — distinguishes between manual updates and WordPress automatic background updates
* **Filter & search** — filter by type, date range, or search by plugin/theme name
* **CSV export** — export your log for client reports or record-keeping
* **Auto-purge** — optionally auto-delete logs older than X days to keep the database clean
* **Lightweight** — no external dependencies, no bloat, no tracking, no upsells

= Usage =

After activation, go to **Changelog Logger** in your WordPress admin menu. Updates will be logged automatically from that point forward. Use the filter bar to search or narrow down the log. Export as CSV anytime.

== Installation ==

1. Upload the `aodn-changelog-logger` folder to `/wp-content/plugins/`
2. Activate the plugin through the **Plugins** menu in WordPress
3. Go to **Changelog Logger** in the admin menu — that's it

No configuration required. Logging starts immediately after activation.

== Frequently Asked Questions ==

= Does it log updates that happened before I installed the plugin? =

No. It can only log updates that occur after activation. There's no way to retroactively retrieve update history.

= Will it slow down my site? =

No. The plugin only runs during update events, which happen infrequently. It has zero impact on frontend performance.

= What happens when I uninstall? =

All log data is removed from the database cleanly. No orphaned data left behind.

= Can I export the log? =

Yes — click the "Export CSV" button on the main log page. You can apply filters before exporting to get a subset of the data.

= Does it work with WP-CLI or managed hosting auto-updates? =

It hooks into the `upgrader_process_complete` action which fires for all update methods including WP-CLI and managed hosting platforms that use the standard WordPress upgrader.

= Is it multisite compatible? =

Basic multisite compatibility is included. Each site in the network maintains its own log.

== Screenshots ==

1. The main update log with type badges, version change indicators, and filter controls
2. Stats bar showing total updates broken down by type
3. Settings page with logging preferences and auto-purge options

== Changelog ==

= 1.1.0 =
* Modernized codebase — PHP 8.2+ with strict types
* Completely redesigned admin UI with premium card-based layout
* Improved settings page with modern toggle controls
* Added proper deactivation cleanup (clears scheduled events)
* Added uninstall.php for clean removal
* Fixed default settings fallback when activation hook doesn't fire
* Better responsive design for tablet and mobile
* Updated branding and links

= 1.0.0 =
* Initial release
* Automatic logging for plugin, theme, and core updates
* Filter by type, date range, and search
* CSV export
* Auto-purge setting
* Manual/auto update attribution

== Upgrade Notice ==

= 1.1.0 =
Redesigned admin UI, modernized PHP 8.2+ codebase, improved settings page. Recommended update.

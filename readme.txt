=== Health Guard ===
Contributors: yodzira
Tags: site health, monitoring, cron, tls, maintenance
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.1.1
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Weekly site checks with plain-language reports: PHP/WordPress versions, overdue cron, TLS expiry, autoload bloat, disk space, abandoned plugins.

== Description ==

Health Guard runs a weekly background check and tells you — in plain language — what needs attention before it becomes downtime:

* PHP version vs the minimum required by your plugins
* plugins that do not declare compatibility with your WordPress yet
* scheduled tasks (cron) that missed their run time
* TLS certificate expiry (warns 3 weeks ahead, screams when expired)
* autoloaded options bloat that drags every page load
* low disk space
* plugins abandoned by their authors for over 2 years

Each finding comes with "why it matters" and "what to do". A weekly email digest is sent only when something changed — no noise.

The check runs on a cron request, never on your visitors' page loads, and reads only your own site. Clean uninstall removes the table, options and scheduled events.

== Pro Version ==

Pro adds automation, reports and integrations on top of the free version
(one license = one site, 12 months of updates):

https://yodsira.duckdns.org/buy/health-guard

== Installation ==

1. Install and activate the plugin.
2. Open the Health Guard menu → "Run scan now".
3. Weekly scans run automatically; the email digest goes to the admin address.

== Frequently Asked Questions ==

= Does it slow my site down? =
No. Everything runs on a cron request. Visitors' page loads carry zero extra work.

= Does it change anything on my site? =
No. It only reads and reports. No fixes, no deletions, no auto-updates.

= Where does plugin compatibility data come from? =
From wordpress.org's public API, cached for 12 hours.

== Changelog ==

= 0.1.1 =
* Added: extension filters for the Pro companion (routing and custom checks). Nothing changes for existing setups.

= 0.1.0 =
* First release: 7 weekly checks, plain-language report, change-only email digest, clean uninstall.

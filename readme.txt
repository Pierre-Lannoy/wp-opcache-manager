=== OPcache Manager ===
Contributors: PierreLannoy, hosterra
Tags: analytics, cache, monitor, OPcache, Zend
Requires at least: 6.2
Requires PHP: 8.1
Tested up to: 6.5
Stable tag: 3.0.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

OPcache statistics and management right in the WordPress admin dashboard.

== Description ==

**OPcache statistics and management right in the WordPress admin dashboard.**

**OPcache Manager** is a full featured OPcache management and analytics reporting tool. It allows you to monitor and optimize OPcache operations on your WordPress site or network.

**OPcache Manager** works on dedicated or shared servers. In shared environments, its use has no influence on other hosted sites than yours. Its main management features are:

* individual script invalidation, forced invalidation and recompilation;
* manual site invalidation - a sort of 'smart' OPcache reset only for your site;
* manual site warm-up - to pre-compile all of you site files;
* optional scheduled site invalidation and/or warm-up.

**OPcache Manager** is also a full featured analytics reporting tool that analyzes all OPcache operations on your site. It can report:

* KPIs: hit ratio, free memory, cached files, keys saturation, buffer saturation and availability;
* metrics variations;
* metrics distributions;
* OPcache related events.

**OPcache Manager** supports multisite report delegation (see FAQ).

**OPcache Manager** supports a set of WP-CLI commands to:

* invalidate or warmup cache - see `wp help opcache invalidate` and `wp help opcache warmup` for details;
* toggle on/off main settings - see `wp help opcache settings` for details;
* obtain operational statistics - see `wp help opcache analytics` for details.

For a full help on WP-CLI commands in OPcache Manager, please [read this guide](https://perfops.one/opcache-manager-wpcli).

> **OPcache Manager** is part of [PerfOps One](https://perfops.one/), a suite of free and open source WordPress plugins dedicated to observability and operations performance.

**OPcache Manager** is a free and open source plugin for WordPress. It integrates many other free and open source works (as-is or modified). Please, see 'about' tab in the plugin settings to see the details.

= Support =

This plugin is free and provided without warranty of any kind. Use it at your own risk, I'm not responsible for any improper use of this plugin, nor for any damage it might cause to your site. Always backup all your data before installing a new plugin.

Anyway, I'll be glad to help you if you encounter issues when using this plugin. Just use the support section of this plugin page.

= Privacy =

This plugin, as any piece of software, is neither compliant nor non-compliant with privacy laws and regulations. It is your responsibility to use it with respect for the personal data of your users and applicable laws.

This plugin doesn't set any cookie in the user's browser.

This plugin doesn't handle personally identifiable information (PII).

= Donation =

If you like this plugin or find it useful and want to thank me for the work done, please consider making a donation to [La Quadrature Du Net](https://www.laquadrature.net/en) or the [Electronic Frontier Foundation](https://www.eff.org/) which are advocacy groups defending the rights and freedoms of citizens on the Internet. By supporting them, you help the daily actions they perform to defend our fundamental freedoms!

== Installation ==

= From your WordPress dashboard =

1. Visit 'Plugins > Add New'.
2. Search for 'OPcache Manager'.
3. Click on the 'Install Now' button.
4. Activate OPcache Manager.

= From WordPress.org =

1. Download OPcache Manager.
2. Upload the `opcache-manager` directory to your `/wp-content/plugins/` directory, using your favorite method (ftp, sftp, scp, etc...).
3. Activate OPcache Manager from your Plugins page.

= Once Activated =

1. Visit 'PerfOps One > Control Center > OPcache Manager' in the left-hand menu of your WP Admin to adjust settings.
2. Enjoy!

== Frequently Asked Questions ==

= What are the requirements for this plugin to work? =

You need at least **WordPress 5.2** and **PHP 7.2**.

= Can this plugin work on multisite? =

Yes. It is designed to work on multisite too. Network Admins can configure the plugin, use management tools and have access to all analytics reports. Sites Admins have access to the analytics reports only.

= What are the requirements for scheduled invalidation/warm-up and statistics to work? =

You need to have a fully operational WordPress cron. If you've set an external cron (crontab, online cron, etc.), its frequency must be less than 5 minutes - 1 or 2 minutes is a recommended value.

= Where can I get support? =

Support is provided via the official [WordPress page](https://wordpress.org/support/plugin/opcache-manager/).

= Where can I report a bug? =
 
You can report bugs and suggest ideas via the [GitHub issue tracker](https://github.com/Pierre-Lannoy/wp-opcache-manager/issues) of the plugin.

== Changelog ==

Please, see [full changelog](https://perfops.one/opcache-manager-changelog).

== Upgrade Notice ==

== Screenshots ==

1. Daily Statistics
2. Historical Statistics
3. Management Tools
4. Quick Actions in Admin Bar
# OPcache Manager
[![version](https://badgen.net/github/release/Pierre-Lannoy/wp-opcache-manager/)](https://wordpress.org/plugins/opcache-manager/)
[![php](https://badgen.net/badge/php/7.2+/green)](https://wordpress.org/plugins/opcache-manager/)
[![wordpress](https://badgen.net/badge/wordpress/5.2+/green)](https://wordpress.org/plugins/opcache-manager/)
[![license](https://badgen.net/github/license/Pierre-Lannoy/wp-opcache-manager/)](/license.txt)

__OPcache Manager__ is a full featured OPcache management and analytics reporting tool. It allows you to monitor and optimize OPcache operations on your WordPress site or network.

See [WordPress directory page](https://wordpress.org/plugins/opcache-manager/) or [official website](https://perfops.one/opcache-manager).

__OPcache Manager__ works on dedicated or shared servers. In shared environments, its use has no influence on other hosted sites than yours. Its main management features are:

* individual script invalidation, forced invalidation and recompilation;
* manual site invalidation - a sort of 'smart' OPcache reset only for your site;
* manual site warm-up - to pre-compile all of you site files;
* optional scheduled site invalidation and/or warm-up.

__OPcache Manager__ is also a full featured analytics reporting tool that analyzes all OPcache operations on your site. It can report:

* KPIs: hit ratio, free memory, cached files, keys saturation, buffer saturation and availability;
* metrics variations;
* metrics distributions;
* OPcache related events.

__OPcache Manager__ supports multisite report delegation.

> __OPcache Manager__ is part of [PerfOps One](https://perfops.one/), a suite of free and open source WordPress plugins dedicated to observability and operations performance.

__OPcache Manager__ is a free and open source plugin for WordPress. It integrates many other free and open source works (as-is or modified). Please, see 'about' tab in the plugin settings to see the details.

## WP-CLI

__OPcache Manager__ supports a set of WP-CLI commands to:

* toggle on/off main settings - see `wp help opcache settings` for details;
* obtain operational statistics - see `wp help opcache analytics` for details.

For a full help on WP-CLI commands in OPcache Manager, please [read this guide](WP-CLI.md).

## Installation

1. From your WordPress dashboard, visit _Plugins | Add New_.
2. Search for 'OPcache Manager'.
3. Click on the 'Install Now' button.

You can now activate __OPcache Manager__ from your _Plugins_ page.

## Support

For any technical issue, or to suggest new idea or feature, please use [GitHub issues tracker](https://github.com/Pierre-Lannoy/wp-opcache-manager/issues). Before submitting an issue, please read the [contribution guidelines](CONTRIBUTING.md).

Alternatively, if you have usage questions, you can open a discussion on the [WordPress support page](https://wordpress.org/support/plugin/opcache-manager/). 

## Contributing

Before submitting an issue or a pull request, please read the [contribution guidelines](CONTRIBUTING.md).

> ⚠️ The `master` branch is the current development state of the plugin. If you want a stable, production-ready version, please pick the last official [release](https://github.com/Pierre-Lannoy/wp-opcache-manager/releases).

## Smoke tests
[![WP compatibility](https://plugintests.com/plugins/opcache-manager/wp-badge.svg)](https://plugintests.com/plugins/opcache-manager/latest)
[![PHP compatibility](https://plugintests.com/plugins/opcache-manager/php-badge.svg)](https://plugintests.com/plugins/opcache-manager/latest)
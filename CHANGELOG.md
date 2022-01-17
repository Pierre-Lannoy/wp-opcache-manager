# Changelog
All notable changes to **OPcache Manager** are documented in this *changelog*.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and **OPcache Manager** adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.6.1] - 2022-01-17

### Fixed
- The Site Health page may launch deprecated tests.

## [2.6.0] - 2022-01-17

### Added
- Compatibility with PHP 8.1.

### Changed
- Charts allow now to display more than 2 months of data.
- Improved timescale computation and date display for all charts.
- Bar charts have now a resizable width.
- Tooltips indicate now the current value in bar charts.
- Updated DecaLog SDK from version 2.0.0 to version 2.0.2.
- Updated PerfOps One library from 2.2.1 to 2.2.2.
- Refactored cache mechanisms to fully support Redis and Memcached.
- Improved bubbles display when width is less than 500px (thanks to [Pat Ol](https://profiles.wordpress.org/pasglop/)).
- The tables headers have now a better contrast (thanks to [Paul Bonaldi](https://profiles.wordpress.org/bonaldi/)).

### Fixed
- Object caching method may be wrongly detected in Site Health status (thanks to [freshuk](https://profiles.wordpress.org/freshuk/)).
- The console menu may display an empty screen (thanks to [Renaud Pacouil](https://www.laboiteare.fr)).
- There may be name collisions with internal APCu cache.

## [2.5.0] - 2021-12-07

### Added
- Compatibility with WordPress 5.9.
- New button in settings to install recommended plugins.
- The available hooks (filters and actions) are now described in `HOOKS.md` file.

### Changed
- Improved update process on high-traffic sites to avoid concurrent resources accesses.
- Better publishing frequency for metrics.
- Updated labels and links in plugins page.
- X axis for graphs have been redesigned and are more accurate.
- Updated the `README.md` file.

### Fixed
- Country translation with i18n module may be wrong.
- There's typos in `CHANGELOG.md`.

## [2.4.0] - 2021-09-07

### Added
- New menu in the admin bar for "clear all caches" and "delete all caches" quick actions.
- It's now possible to hide the main PerfOps One menu via the `poo_hide_main_menu` filter or each submenu via the `poo_hide_analytics_menu`, `poo_hide_consoles_menu`, `poo_hide_insights_menu`, `poo_hide_tools_menu`, `poo_hide_records_menu` and `poo_hide_settings_menu` filters (thanks to [Jan Thiel](https://github.com/JanThiel)).

### Changed
- OPcache Manager is now compatible with WordPress installation on non-standard directories.
- Redesigned and improved warmup process: it's more fast and more reliable.
- Updated DecaLog SDK from version 1.2.0 to version 2.0.0.

### Fixed
- There may be name collisions for some functions if version of WordPress is lower than 5.6.
- The main PerfOps One menu is not hidden when it doesn't contain any items (thanks to [Jan Thiel](https://github.com/JanThiel)).
- In some very special conditions, the plugin may be in the default site language rather than the user's language.
- The PerfOps One menu builder is not compatible with Admin Menu Editor plugin (thanks to [dvokoun](https://wordpress.org/support/users/dvokoun/)).
- In some conditions, invalidation or warmup may produce many warnings.
- Some labels are not consistent on WordPress multisite.
- [SEC001] Warmup now ignores `wp-content` directory.

## [2.3.2] - 2021-08-11

### Changed
- New redesigned UI for PerfOps One plugins management and menus (thanks to [Loïc Antignac](https://github.com/webaxones), [Paul Bonaldi](https://profiles.wordpress.org/bonaldi/), [Axel Ducoron](https://github.com/aksld), [Laurent Millet](https://profiles.wordpress.org/wplmillet/), [Samy Rabih](https://github.com/samy) and [Raphaël Riehl](https://github.com/raphaelriehl) for their invaluable help).

### Fixed
- In some conditions, the plugin may be in the default site language rather than the user's language.

## [2.3.1] - 2021-06-30

### Fixed
- The statistics table may not be created at install/update (thanks to [Rookie](https://wordpress.org/support/users/alriksson/)).

## [2.3.0] - 2021-06-22

### Added
- Compatibility with WordPress 5.8.
- Integration with DecaLog SDK.
- Traces and metrics collation and publication.
- New option, available via settings page and wp-cli, to disable/enable metrics collation.
- [WPCLI] New command to invalidate site / network files: see `wp help opcache invalidate` for details.
- [WPCLI] New command to warm-up site / network files: see `wp help opcache warmup` for details.

### Changed
- [WP-CLI] `opcache status` command now displays DecaLog SDK version too.
- [WPCLI] Documentation updated.

## [2.2.0] - 2021-02-24

### Added
- Compatibility with WordPress 5.7.
- New warning box in plugin settings page when OPcache is disabled or OPcache API usage is restricted.

### Changed
- Consistent reset for settings.
- Improved translation loading.
- [WP_CLI] `opcache` command have now a definition and all synopsis are up to date.
- Controls in settings page are now disabled when OPcache is disabled or OPcache API usage is restricted.

### Fixed
- In Site Health section, Opcache status may be wrong (or generates PHP warnings) if OPcache API usage is restricted.

### Removed
- Analytics data collection when OPcache API usage is restricted.
- Tools and Analytics menus when OPcache is disabled or OPcache API usage is restricted.

## [2.1.0] - 2020-11-23

### Added
- Compatibility with WordPress 5.6.

### Changed
- Improvement in the way roles are detected.

### Fixed
- [SEC001] User may be wrongly detected in XML-RPC or Rest API calls.

## [2.0.0] - 2020-10-19

### Added
- [WP-CLI] New command to toggle on/off main settings: see `wp help opcache settings` for details.
- [WP-CLI] New command to display OPcache Manager status: see `wp help opcache status` for details.
- [WP-CLI] New command to display OPcache analytics: see `wp help opcache analytics` for details.
- New Site Health "info" section about shared memory.
- Compatibility with WordPress 5.5.
- Support for data feeds - reserved for future use.

### Changed
- The positions of PerfOps menus are pushed lower to avoid collision with other plugins (thanks to [Loïc Antignac](https://github.com/webaxones)).
- The analytics dashboard now displays a warning if analytics features are not activated.
- Improved layout for language indicator.
- Admin notices are now set to "don't display" by default.
- Improved IP detection  (thanks to [Ludovic Riaudel](https://github.com/lriaudel)).
- Improved changelog readability.
- The integrated markdown parser is now [Markdown](https://github.com/cebe/markdown) from Carsten Brandt.
- Prepares PerfOps menus to future 5.6 version of WordPress.

### Fixed
- The remote IP can be wrongly detected when behind some types of reverse-proxies.
- In admin dashboard, the statistics link is visible even if analytics features are not activated.
- With Firefox, some links are unclickable in the Control Center (thanks to [Emil1](https://wordpress.org/support/users/milouze/)).
- When site is in english and a user choose another language for herself/himself, menu may be stuck in english.

### Removed
- Parsedown as integrated markdown parser.

## [1.3.2] - 2020-06-29

### Changed
- Full compatibility with PHP 7.4.
- Automatic switching between memory and transient when a cache plugin is installed without a properly configured Redis / Memcached (thanks to [mmwbadmin](https://wordpress.org/support/users/mmwbadmin/)).

### Fixed
- When used for the first time, settings checkboxes may remain checked after being unchecked.

## [1.3.1] - 2020-05-05

### Changed
- The charts take now care of DST in user's browser.
- The daily distribution charts have now a better timeline.

### Fixed
- There's an error while activating the plugin when the server is Microsoft IIS with Windows 10.
- With Microsoft Edge, some layouts may be ugly.

## [1.3.0] - 2020-04-12

### Added
- Compatibility with [DecaLog](https://wordpress.org/plugins/decalog/) early loading feature.

### Changed
- The settings page have now the standard WordPress style.
- Better styling in "PerfOps Settings" page.
- The tool page is now called "OPcache Management".
- In site health "info" tab, the boolean are now clearly displayed.

### Removed
- Unneeded tool links in settings page.

## [1.2.0] - 2020-03-01

### Added
- Full compatibility with [APCu Manager](https://wordpress.org/plugins/apcu-manager/).
- Full integration with PerfOps One suite.
- Compatibility with WordPress 5.4.

### Changed
- New menus (in the left admin bar) for accessing features: "PerfOps Analytics", "PerfOps Tools" and "PerfOps Settings".
- In lists, it's now possible to navigate by direct page input.
- Analysis delta time has been increased to avoid holes in stats when cron is not fully reliable.

### Fixed
- A race condition can lead to "holes" in daily graphs.
- With some plugins, box tooltips may be misplaced (css collision).
- In lists, some navigation buttons are wrongly active.

### Removed
- Compatibility with WordPress versions prior to 5.2.
- Old menus entries, due to PerfOps integration.

## [1.1.0] - 2020-01-03

### Added
- Full compatibility (for internal cache) with Redis and Memcached.
- Using APCu rather than database transients if APCu is available.
- New Site Health "status" sections about OPcache and object cache. 
- New Site Health "status" section about i18n extension for non `en_US` sites.
- New Site Health "info" sections about OPcache and object cache. 
- New Site Health "info" section about the plugin itself. 

## [1.0.3] - 2019-12-19

### Changed
- Better cache management for old date ranges.

### Fixed
- Some plugin options may be not saved when needed (thanks to [Lucas Bustamante](https://github.com/Luc45)).

### Removed
- As a result of the Plugin Team's request, the auto-update feature has been removed.

## [1.0.2] - 2019-11-22

### Changed
- Better OPcache version detection (and reporting).

### Fixed
- The timescale for metrics variation graphs is wrong when day starts with missing data.
- The buttons of the date range picker may have a wrong size.
- The link to changelog after update is erroneous.

## [1.0.1] - 2019-11-21

### Fixed
- The cron for statistics compilation may be blocked on some configurations.
- There's some typos in the `readme.txt` file.

## [1.0.0] - 2019-11-20

Initial release
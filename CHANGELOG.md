# Changelog
All notable changes to **OPcache Manager** are documented in this *changelog*.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and **OPcache Manager** adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased - will be 1.4.0]

### Added
- New Site Health "info" section about shared memory.
- Compatibility with WordPress 5.5.

### Changed
- The positions of PerfOps menus are pushed lower to avoid collision with other plugins (thanks to [Loïc Antignac](https://github.com/webaxones)).
- Improved layout for language indicator.
- Admin notices are now set to "don't display" by default.
- Improved IP detection  (thanks to [Ludovic Riaudel](https://github.com/lriaudel)).
- Improved changelog readability.
- The integrated markdown parser is now [Markdown](https://github.com/cebe/markdown) from Carsten Brandt.

### Fixed
- With Firefox, some links are unclickable in the Control Center (thanks to [Emil1](https://wordpress.org/support/users/milouze/)).

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
- Full integration with PerfOps.One suite.
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
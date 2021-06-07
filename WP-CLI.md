OPcache Manager is fully usable from command-line, thanks to [WP-CLI](https://wp-cli.org/). You can set OPcache Manager options and much more, without using a web browser.

1. [Obtaining statistics about OPcache usage](#obtaining-statistics-about-opcache-usage) - `wp opcache analytics`
2. [Getting OPcache Manager status](#getting-opcache-manager-status) - `wp opcache status`
3. [Managing main settings](#managing-main-settings) - `wp opcache settings`
4. [Forced invalidation](#forced-invalidation) - `wp opcache invalidate`
5. [Forced warm-up](#forced-warm-up) - `wp opcache warmup`
6. [Misc flags](#misc-flags)

## Obtaining statistics about OPcache usage

You can get OPcache analytics for today (compared with yesterday). To do that, use the `wp opcache analytics` command.

By default, the outputted format is a simple table. If you want to customize the format, just use `--format=<format>`. Note if you choose `json` or `yaml` as format, the output will contain full data and metadata for the current day.

### Examples

To display OPcache analytics, type the following command:
```console
pierre@dev:~$ wp opcache analytics
+--------------+-----------------------------------------------+-------+--------+-----------+
| kpi          | description                                   | value | ratio  | variation |
+--------------+-----------------------------------------------+-------+--------+-----------+
| Hits         | Successful calls to the cache.                | 1.9M  | 99.9%  | +0.02%    |
| Total memory | Total memory available for OPcache.           | 192MB | 42.42% | -5.53%    |
| Keys         | Keys allocated by OPcache.                    | 3K    | 15.45% | +9.14%    |
| Buffer       | Buffer size.                                  | 6MB   | 99.08% | +0.84%    |
| Availability | Extrapolated availability time over 24 hours. | 24 hr | 100%   | 0%        |
| Scripts      | Scripts currently present in cache.           | 2.3K  | -      | -9.44%    |
+--------------+-----------------------------------------------+-------+--------+-----------+
```

## Getting OPcache Manager status

To get detailed status and operation mode, use the `wp opcache status` command.

> Note this command may tell you OPcache is not activated for command-line even if it's available for WordPress itself. It is due to the fact that PHP configuration is often different between command-line and web server.
>
> Nevertheless, if OPcache is available for WordPress, other OPcache Manager commands are operational.

## Managing main settings

To toggle on/off main settings, use `wp opcache settings <enable|disable> <analytics|metrics>`.

If you try to disable a setting, wp-cli will ask you to confirm. To force answer to yes without prompting, just use `--yes`.

### Available settings

- `analytics`: analytics feature
- `metrics`: metrics collation feature

### Example

To disable analytics without confirmation prompt, type the following command:
```console
pierre@dev:~$ wp opcache settings disable analytics --yes
Success: analytics are now deactivated.
```

## Forced invalidation

To initiate a forced invalidation, use `wp opcache invalidate`.

This invalidation will be done at the next scheduled cron.

### Example

To invalidate files without confirmation prompt, type the following command:
```console
pierre@dev:~$ wp opcache invalidate --yes
Success: invalidation scheduled to start in less than 5 minutes.
```

## Forced warm-up

To initiate a forced invalidation followed by a warm-up, use `wp opcache warmup`.

The invalidation and warm-up will be done at the next scheduled cron.

### Example

To invalidate and warm-up files without confirmation prompt, type the following command:
```console
pierre@dev:~$ wp opcache warmup --yes
Success: invalidation and warmup scheduled to start in less than 5 minutes.
```

## Misc flags

For most commands, OPcache Manager lets you use the following flags:
- `--yes`: automatically answer "yes" when a question is prompted during the command execution.
- `--stdout`: outputs a clean STDOUT string so you can pipe or store result of command execution.

> It's not mandatory to use `--stdout` when using `--format=count` or `--format=ids`: in such cases `--stdout` is assumed.

> Note OPcache Manager sets exit code so you can use `$?` to write scripts.
> To know the meaning of OPcache Manager exit codes, just use the command `wp opcache exitcode list`.

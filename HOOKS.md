This plugin has a number of hooks that you can use, as developer or as a user, to customize the user experience or to give access to extended functionalities.

## Customization of PerfOps One menus
You can use the `poo_hide_main_menu` filter to completely hide the main PerfOps One menu or use the `poo_hide_analytics_menu`, `poo_hide_consoles_menu`, `poo_hide_insights_menu`, `poo_hide_tools_menu`, `poo_hide_records_menu` and `poo_hide_settings_menu` filters to selectively hide submenus.

### Example
Hide the main menu:
```php
  add_filter( 'poo_hide_main_menu', '__return_true' );
```

## Customization of the admin bar
You can use the `poo_hide_adminbar` filter to completely hide this plugin's item(s) from the admin bar.

### Example
Remove this plugin's item(s) from the admin bar:
```php
  add_filter( 'poo_hide_adminbar', '__return_true' );
```

## Advanced settings and controls
By default, advanced settings and controls are hidden to avoid cluttering admin screens. Nevertheless, if this plugin have such settings and controls, you can force them to display with `perfopsone_show_advanced` filter.

### Example
Display advanced settings and controls in admin screens:
```php
  add_filter( 'perfopsone_show_advanced', '__return_true' );
```

## Frequencies customization
You can add available frequencies for warmup with the `opcache-manager_add_reset_frequencies` filter.

### Example 1
Adding the WordPress-native weekly frequency.
```php
add_filter('opcache-manager_add_reset_frequencies', function($frequencies) {
  $frequencies[] = [ 'weekly' => 'Weekly' ];
  return $frequencies;
});
```

### Example 2
Adding a customized 30 minutes frequency.
```php
add_filter('opcache-manager_add_reset_frequencies', function($frequencies) {
  $frequencies[] = [ 'twicehourly' => 'Twice Hourly' ];
  return $frequencies;
});
```
And don't forget to create the right schedule like that:
```php
add_filter('cron_schedules', function($schedules) {
  if ( ! array_key_exists( 'twicehourly', $schedules ) ) {
    $schedules['twicehourly'] = [
        'interval' => 1800,
        'display' => 'Twice Hourly',
    ];
});
```
<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace OPcacheManager\Plugin;

use OPcacheManager\Plugin\Feature\Analytics;
use OPcacheManager\Plugin\Feature\AnalyticsFactory;
use OPcacheManager\System\Assets;
use OPcacheManager\System\Environment;
use OPcacheManager\System\Logger;
use OPcacheManager\System\Role;
use OPcacheManager\System\Option;
use OPcacheManager\System\Form;
use OPcacheManager\System\Blog;
use OPcacheManager\System\Date;
use OPcacheManager\System\Timezone;

/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Opcache_Manager_Admin {

	/**
	 * The assets manager that's responsible for handling all assets of the plugin.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    Assets    $assets    The plugin assets manager.
	 */
	protected $assets;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->assets = new Assets();
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles() {
		$this->assets->register_style( OPCM_ASSETS_ID, OPCM_ADMIN_URL, 'css/opcache-manager.min.css' );
		$this->assets->register_style( 'opcm-daterangepicker', OPCM_ADMIN_URL, 'css/daterangepicker.min.css' );
		$this->assets->register_style( 'opcm-tooltip', OPCM_ADMIN_URL, 'css/tooltip.min.css' );
		$this->assets->register_style( 'opcm-chartist', OPCM_ADMIN_URL, 'css/chartist.min.css' );
		$this->assets->register_style( 'opcm-chartist-tooltip', OPCM_ADMIN_URL, 'css/chartist-plugin-tooltip.min.css' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		$this->assets->register_script( OPCM_ASSETS_ID, OPCM_ADMIN_URL, 'js/opcache-manager.min.js', [ 'jquery' ] );
		$this->assets->register_script( 'opcm-moment-with-locale', OPCM_ADMIN_URL, 'js/moment-with-locales.min.js', [ 'jquery' ] );
		$this->assets->register_script( 'opcm-daterangepicker', OPCM_ADMIN_URL, 'js/daterangepicker.min.js', [ 'jquery' ] );
		$this->assets->register_script( 'opcm-chartist', OPCM_ADMIN_URL, 'js/chartist.min.js', [ 'jquery' ] );
		$this->assets->register_script( 'opcm-chartist-tooltip', OPCM_ADMIN_URL, 'js/chartist-plugin-tooltip.min.js', [ 'opcm-chartist' ] );
	}

	/**
	 * Set the items in the settings menu.
	 *
	 * @since 1.0.0
	 */
	public function init_admin_menus() {
		if ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() ) {
			/* translators: as in the sentence "OPcache Manager Settings" or "WordPress Settings" */
			$settings = add_submenu_page( 'options-general.php', sprintf( esc_html__( '%s Settings', 'opcache-manager' ), OPCM_PRODUCT_NAME ), OPCM_PRODUCT_NAME, 'manage_options', 'opcm-settings', [ $this, 'get_settings_page' ] );
			$name     = add_submenu_page(
				'tools.php',
				esc_html__( 'OPcache Tools', 'opcache-manager' ),
				esc_html__( 'OPcache Tools', 'opcache-manager' ),
				'manage_options',
				'opcm-tools',
				[ $this, 'get_tools_page' ]
			);
		}
		if ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() || Role::LOCAL_ADMIN === Role::admin_type() ) {
			$name = add_submenu_page(
				'tools.php',
				esc_html__( 'OPcache Analytics', 'opcache-manager' ),
				esc_html__( 'OPcache Analytics', 'opcache-manager' ),
				'manage_options',
				'opcm-viewer',
				[ $this, 'get_viewer_page' ]
			);
		}
	}

	/**
	 * Initializes settings sections.
	 *
	 * @since 1.0.0
	 */
	public function init_settings_sections() {
		add_settings_section( 'opcm_plugin_features_section', esc_html__( 'Plugin Features', 'opcache-manager' ), [ $this, 'plugin_features_section_callback' ], 'opcm_plugin_features_section' );
		add_settings_section( 'opcm_plugin_options_section', esc_html__( 'Plugin options', 'opcache-manager' ), [ $this, 'plugin_options_section_callback' ], 'opcm_plugin_options_section' );
	}

	/**
	 * Add links in the "Actions" column on the plugins view page.
	 *
	 * @param string[] $actions     An array of plugin action links. By default this can include 'activate',
	 *                              'deactivate', and 'delete'.
	 * @param string   $plugin_file Path to the plugin file relative to the plugins directory.
	 * @param array    $plugin_data An array of plugin data. See `get_plugin_data()`.
	 * @param string   $context     The plugin context. By default this can include 'all', 'active', 'inactive',
	 *                              'recently_activated', 'upgrade', 'mustuse', 'dropins', and 'search'.
	 * @return array Extended list of links to print in the "Actions" column on the Plugins page.
	 * @since 1.0.0
	 */
	public function add_actions_links( $actions, $plugin_file, $plugin_data, $context ) {
		$actions[] = sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'options-general.php?page=opcm-settings' ) ), esc_html__( 'Settings', 'opcache-manager' ) );
		$actions[] = sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'tools.php?page=opcm-tools' ) ), esc_html__( 'Tools', 'opcache-manager' ) );
		$actions[] = sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'tools.php?page=opcm-viewer' ) ), esc_html__( 'Statistics', 'opcache-manager' ) );
		return $actions;
	}

	/**
	 * Add links in the "Description" column on the plugins view page.
	 *
	 * @param array  $links List of links to print in the "Description" column on the Plugins page.
	 * @param string $file Path to the plugin file relative to the plugins directory.
	 * @return array Extended list of links to print in the "Description" column on the Plugins page.
	 * @since 1.0.0
	 */
	public function add_row_meta( $links, $file ) {
		if ( 0 === strpos( $file, OPCM_SLUG . '/' ) ) {
			$links[] = '<a href="https://wordpress.org/support/plugin/' . OPCM_SLUG . '/">' . __( 'Support', 'opcache-manager' ) . '</a>';
			$links[] = '<a href="https://github.com/Pierre-Lannoy/wp-opcache-manager">' . __( 'GitHub repository', 'opcache-manager' ) . '</a>';
		}
		return $links;
	}

	/**
	 * Get the content of the tools page.
	 *
	 * @since 1.0.0
	 */
	public function get_tools_page() {
		include OPCM_ADMIN_DIR . 'partials/opcache-manager-admin-tools.php';
	}

	/**
	 * Get the content of the viewer page.
	 *
	 * @since 1.0.0
	 */
	public function get_viewer_page() {
		$analytics = AnalyticsFactory::get_analytics();
		include OPCM_ADMIN_DIR . 'partials/opcache-manager-admin-view-analytics.php';
	}

	/**
	 * Get the content of the settings page.
	 *
	 * @since 1.0.0
	 */
	public function get_settings_page() {
		if ( ! ( $tab = filter_input( INPUT_GET, 'tab' ) ) ) {
			$tab = filter_input( INPUT_POST, 'tab' );
		}
		if ( ! ( $action = filter_input( INPUT_GET, 'action' ) ) ) {
			$action = filter_input( INPUT_POST, 'action' );
		}
		if ( $action && $tab ) {
			switch ( $tab ) {
				case 'misc':
					switch ( $action ) {
						case 'do-save':
							if ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() ) {
								if ( ! empty( $_POST ) && array_key_exists( 'submit', $_POST ) ) {
									$this->save_options();
								} elseif ( ! empty( $_POST ) && array_key_exists( 'reset-to-defaults', $_POST ) ) {
									$this->reset_options();
								}
							}
							break;
					}
					break;
			}
		}
		include OPCM_ADMIN_DIR . 'partials/opcache-manager-admin-settings-main.php';
	}

	/**
	 * Save the plugin options.
	 *
	 * @since 1.0.0
	 */
	private function save_options() {
		if ( ! empty( $_POST ) ) {
			if ( array_key_exists( '_wpnonce', $_POST ) && wp_verify_nonce( $_POST['_wpnonce'], 'opcm-plugin-options' ) ) {
				$old_frequency = Option::network_get( 'reset_frequency' );
				Option::network_set( 'use_cdn', array_key_exists( 'opcm_plugin_options_usecdn', $_POST ) ? (bool) filter_input( INPUT_POST, 'opcm_plugin_options_usecdn' ) : false );
				Option::network_set( 'auto_update', array_key_exists( 'opcm_plugin_options_autoupdate', $_POST ) ? (bool) filter_input( INPUT_POST, 'opcm_plugin_options_autoupdate' ) : false );
				Option::network_set( 'display_nag', array_key_exists( 'opcm_plugin_options_nag', $_POST ) ? (bool) filter_input( INPUT_POST, 'opcm_plugin_options_nag' ) : false );
				Option::network_set( 'analytics', array_key_exists( 'opcm_plugin_features_analytics', $_POST ) ? (bool) filter_input( INPUT_POST, 'opcm_plugin_features_analytics' ) : false );
				Option::network_set( 'history', array_key_exists( 'opcm_plugin_features_history', $_POST ) ? (string) filter_input( INPUT_POST, 'opcm_plugin_features_history', FILTER_SANITIZE_NUMBER_INT ) : Option::network_get( 'history' ) );
				Option::network_set( 'reset_frequency', array_key_exists( 'opcm_plugin_features_reset_frequency', $_POST ) ? (string) filter_input( INPUT_POST, 'opcm_plugin_features_reset_frequency', FILTER_SANITIZE_STRING ) : $old_frequency );
				Option::network_set( 'warmup', array_key_exists( 'opcm_plugin_features_warmup', $_POST ) ? (bool) filter_input( INPUT_POST, 'opcm_plugin_features_warmup' ) : false );
				if ( Option::network_get( 'reset_frequency' ) !== $old_frequency ) {
					wp_clear_scheduled_hook( OPCM_CRON_RESET_NAME );
				}
				if ( ! Option::network_get( 'analytics' ) ) {
					wp_clear_scheduled_hook( OPCM_CRON_STATS_NAME );
				}
				$message = esc_html__( 'Plugin settings have been saved.', 'opcache-manager' );
				$code    = 0;
				add_settings_error( 'opcm_no_error', $code, $message, 'updated' );
				Logger::info( 'Plugin settings updated.', $code );
			} else {
				$message = esc_html__( 'Plugin settings have not been saved. Please try again.', 'opcache-manager' );
				$code    = 2;
				add_settings_error( 'opcm_nonce_error', $code, $message, 'error' );
				Logger::warning( 'Plugin settings not updated.', $code );
			}
		}
	}

	/**
	 * Reset the plugin options.
	 *
	 * @since 1.0.0
	 */
	private function reset_options() {
		if ( ! empty( $_POST ) ) {
			if ( array_key_exists( '_wpnonce', $_POST ) && wp_verify_nonce( $_POST['_wpnonce'], 'opcm-plugin-options' ) ) {
				Option::reset_to_defaults();
				$message = esc_html__( 'Plugin settings have been reset to defaults.', 'opcache-manager' );
				$code    = 0;
				add_settings_error( 'opcm_no_error', $code, $message, 'updated' );
				Logger::info( 'Plugin settings reset to defaults.', $code );
			} else {
				$message = esc_html__( 'Plugin settings have not been reset to defaults. Please try again.', 'opcache-manager' );
				$code    = 2;
				add_settings_error( 'opcm_nonce_error', $code, $message, 'error' );
				Logger::warning( 'Plugin settings not reset to defaults.', $code );
			}
		}
	}

	/**
	 * Callback for plugin options section.
	 *
	 * @since 1.0.0
	 */
	public function plugin_options_section_callback() {
		$form = new Form();
		if ( defined( 'DECALOG_VERSION' ) ) {
			$help  = '<img style="width:16px;vertical-align:text-bottom;" src="' . \Feather\Icons::get_base64( 'thumbs-up', 'none', '#00C800' ) . '" />&nbsp;';
			$help .= sprintf( esc_html__('Your site is currently using %s.', 'opcache-manager' ), '<em>DecaLog v' . DECALOG_VERSION .'</em>' );
		} else {
			$help  = '<img style="width:16px;vertical-align:text-bottom;" src="' . \Feather\Icons::get_base64( 'alert-triangle', 'none', '#FF8C00' ) . '" />&nbsp;';
			$help .= sprintf( esc_html__('Your site does not use any logging plugin. To log all events triggered in OPcache Manager, I recommend you to install the excellent (and free) %s. But it is not mandatory.', 'opcache-manager' ), '<a href="https://wordpress.org/plugins/decalog/">DecaLog</a>' );
		}
		add_settings_field(
			'opcm_plugin_options_logger',
			esc_html__( 'Logging', 'opcache-manager' ),
			[ $form, 'echo_field_simple_text' ],
			'opcm_plugin_options_section',
			'opcm_plugin_options_section',
			[
				'text' => $help
			]
		);
		register_setting( 'opcm_plugin_options_section', 'opcm_plugin_options_logger' );
		add_settings_field(
			'opcm_plugin_options_usecdn',
			esc_html__( 'Resources', 'opcache-manager' ),
			[ $form, 'echo_field_checkbox' ],
			'opcm_plugin_options_section',
			'opcm_plugin_options_section',
			[
				'text'        => esc_html__( 'Use public CDN', 'opcache-manager' ),
				'id'          => 'opcm_plugin_options_usecdn',
				'checked'     => Option::network_get( 'use_cdn' ),
				'description' => esc_html__( 'If checked, OPcache Manager will use a public CDN (jsDelivr) to serve scripts and stylesheets.', 'opcache-manager' ),
				'full_width'  => true,
				'enabled'     => true,
			]
		);
		register_setting( 'opcm_plugin_options_section', 'opcm_plugin_options_usecdn' );
		add_settings_field(
			'opcm_plugin_options_autoupdate',
			esc_html__( 'Plugin updates', 'opcache-manager' ),
			[ $form, 'echo_field_checkbox' ],
			'opcm_plugin_options_section',
			'opcm_plugin_options_section',
			[
				'text'        => esc_html__( 'Automatic (recommended)', 'opcache-manager' ),
				'id'          => 'opcm_plugin_options_autoupdate',
				'checked'     => Option::network_get( 'auto_update' ),
				'description' => esc_html__( 'If checked, OPcache Manager will update itself as soon as a new version is available.', 'opcache-manager' ),
				'full_width'  => true,
				'enabled'     => true,
			]
		);
		register_setting( 'opcm_plugin_options_section', 'opcm_plugin_options_autoupdate' );
		add_settings_field(
			'opcm_plugin_options_nag',
			esc_html__( 'Admin notices', 'opcache-manager' ),
			[ $form, 'echo_field_checkbox' ],
			'opcm_plugin_options_section',
			'opcm_plugin_options_section',
			[
				'text'        => esc_html__( 'Display', 'opcache-manager' ),
				'id'          => 'opcm_plugin_options_nag',
				'checked'     => Option::network_get( 'display_nag' ),
				'description' => esc_html__( 'Allows OPcache Manager to display admin notices throughout the admin dashboard.', 'opcache-manager' ) . '<br/>' . esc_html__( 'Note: OPcache Manager respects DISABLE_NAG_NOTICES flag.', 'opcache-manager' ),
				'full_width'  => true,
				'enabled'     => true,
			]
		);
		register_setting( 'opcm_plugin_options_section', 'opcm_plugin_options_nag' );
	}

	/**
	 * Get the available frequencies.
	 *
	 * @return array An array containing the history modes.
	 * @since  3.2.0
	 */
	protected function get_frequencies_array() {
		$result   = [];
		$result[] = [ 'never', esc_html__( 'Never', 'opcache-manager' ) ];
		$result[] = [ 'hourly', esc_html__( 'Once Hourly', 'opcache-manager' ) ];
		$result[] = [ 'twicedaily', esc_html__( 'Twice Daily', 'opcache-manager' ) ];
		$result[] = [ 'daily', esc_html__( 'Once Daily', 'opcache-manager' ) ];
		return $result;
	}

	/**
	 * Get the available history retentions.
	 *
	 * @return array An array containing the history modes.
	 * @since  3.2.0
	 */
	protected function get_retentions_array() {
		$result = [];
		for ( $i = 1; $i < 4; $i++ ) {
			// phpcs:ignore
			$result[] = [ (int) ( 7 * $i ), esc_html( sprintf( _n( '%d week', '%d weeks', $i, 'opcache-manager' ), $i ) ) ];
		}
		for ( $i = 1; $i < 4; $i++ ) {
			// phpcs:ignore
			$result[] = [ (int) ( 30 * $i ), esc_html( sprintf( _n( '%d month', '%d months', $i, 'opcache-manager' ), $i ) ) ];
		}
		return $result;
	}

	/**
	 * Callback for plugin features section.
	 *
	 * @since 1.0.0
	 */
	public function plugin_features_section_callback() {
		$form = new Form();
		add_settings_field(
			'opcm_plugin_features_analytics',
			esc_html__( 'Analytics', 'opcache-manager' ),
			[ $form, 'echo_field_checkbox' ],
			'opcm_plugin_features_section',
			'opcm_plugin_features_section',
			[
				'text'        => esc_html__( 'Activated', 'opcache-manager' ),
				'id'          => 'opcm_plugin_features_analytics',
				'checked'     => Option::network_get( 'analytics' ),
				'description' => esc_html__( 'If checked, OPcache Manager will analyze OPcache operations and store statistics every five minutes.', 'opcache-manager' ) . '<br/>' . esc_html__( 'Note: for this to work, your WordPress site must have an operational CRON.', 'opcache-manager' ),
				'full_width'  => true,
				'enabled'     => true,
			]
		);
		register_setting( 'opcm_plugin_features_section', 'opcm_plugin_features_analytics' );
		add_settings_field(
			'opcm_plugin_features_history',
			esc_html__( 'Historical data', 'opcache-manager' ),
			[ $form, 'echo_field_select' ],
			'opcm_plugin_features_section',
			'opcm_plugin_features_section',
			[
				'list'        => $this->get_retentions_array(),
				'id'          => 'opcm_plugin_features_history',
				'value'       => Option::network_get( 'history' ),
				'description' => esc_html__( 'Maximum age of data to keep for statistics.', 'opcache-manager' ),
				'full_width'  => true,
				'enabled'     => true,
			]
		);
		register_setting( 'opcm_plugin_features_section', 'opcm_plugin_features_history' );
		add_settings_field(
			'opcm_plugin_features_reset_frequency',
			esc_html__( 'Site invalidation', 'opcache-manager' ),
			[ $form, 'echo_field_select' ],
			'opcm_plugin_features_section',
			'opcm_plugin_features_section',
			[
				'list'        => $this->get_frequencies_array(),
				'id'          => 'opcm_plugin_features_reset_frequency',
				'value'       => Option::network_get( 'reset_frequency' ),
				'description' => esc_html__( 'Frequency at which files belonging to this site must be automatically reset.', 'opcache-manager' ),
				'full_width'  => true,
				'enabled'     => true,
			]
		);
		register_setting( 'opcm_plugin_features_section', 'opcm_plugin_features_reset_frequency' );
		if ( Environment::is_wordpress_multisite() ) {
			$warmup      = esc_html__( 'Network warm-up', 'opcache-manager' );
			$description = esc_html__( 'If checked, OPcache Manager will warm-up the full network (all sites) after each automatic site invalidation.', 'opcache-manager' );
		} else {
			$warmup      = esc_html__( 'Site warm-up', 'opcache-manager' );
			$description = esc_html__( 'If checked, OPcache Manager will warm-up the full site after each automatic site invalidation.', 'opcache-manager' );
		}
		add_settings_field(
			'opcm_plugin_features_warmup',
			$warmup,
			[ $form, 'echo_field_checkbox' ],
			'opcm_plugin_features_section',
			'opcm_plugin_features_section',
			[
				'text'        => esc_html__( 'Activated', 'opcache-manager' ),
				'id'          => 'opcm_plugin_features_warmup',
				'checked'     => Option::network_get( 'warmup' ),
				'description' => $description,
				'full_width'  => true,
				'enabled'     => true,
			]
		);
		register_setting( 'opcm_plugin_features_section', 'opcm_plugin_features_warmup' );
	}

}

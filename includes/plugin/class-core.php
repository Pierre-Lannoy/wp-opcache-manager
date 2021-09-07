<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @package Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace OPcacheManager\Plugin;

use OPcacheManager\System\Environment;
use OPcacheManager\System\Loader;
use OPcacheManager\System\I18n;
use OPcacheManager\System\Assets;
use OPcacheManager\Library\Libraries;
use OPcacheManager\System\Nag;
use OPcacheManager\System\Option;
use OPcacheManager\Plugin\Feature\Capture;

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * @package Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Core {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->loader = new Loader();
		$this->define_global_hooks();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		if ( \DecaLog\Engine::isDecalogActivated() && Option::network_get( 'metrics' ) && Environment::exec_mode_for_metrics() ) {
			Capture::metrics();
		}
	}

	/**
	 * Register all of the hooks related to the features of the plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function define_global_hooks() {
		add_action( 'cron_schedules', [ 'OPcacheManager\Plugin\Feature\Capture', 'add_cron_05_minutes_interval' ] );
		$bootstrap = new Initializer();
		$assets    = new Assets();
		$updater   = new Updater();
		$libraries = new Libraries();
		$this->loader->add_action( 'init', 'OPcacheManager\Plugin\Integration\Databeam', 'init' );
		$this->loader->add_filter( 'perfopsone_plugin_info', self::class, 'perfopsone_plugin_info' );
		$this->loader->add_action( 'init', $bootstrap, 'initialize' );
		$this->loader->add_action( 'init', $bootstrap, 'late_initialize', PHP_INT_MAX );
		$this->loader->add_action( 'wp_head', $assets, 'prefetch' );
		add_shortcode( 'opcm-changelog', [ $updater, 'sc_get_changelog' ] );
		add_shortcode( 'opcm-libraries', [ $libraries, 'sc_get_list' ] );
		add_shortcode( 'opcm-statistics', [ 'OPcacheManager\System\Statistics', 'sc_get_raw' ] );
		if ( ! wp_next_scheduled( OPCM_CRON_WATCHDOG_NAME ) ) {
			wp_schedule_event( time(), 'five_minutes', OPCM_CRON_WATCHDOG_NAME );
		}
		$this->loader->add_action( OPCM_CRON_WATCHDOG_NAME, 'OPcacheManager\System\OPcache', 'check' );
		$event = wp_get_scheduled_event( OPCM_CRON_RESET_NAME );
		if ( false !== $event ) {
			if ( Option::network_get( 'reset_frequency' ) !== $event->schedule ) {
				wp_clear_scheduled_hook( OPCM_CRON_RESET_NAME );
			}
		}
		if ( 'never' !== Option::network_get( 'reset_frequency' ) ) {
			$this->loader->add_action( OPCM_CRON_RESET_NAME, 'OPcacheManager\System\OPcache', 'reset' );
		}
		if ( ! wp_next_scheduled( OPCM_CRON_RESET_NAME ) ) {
			if ( 'never' !== Option::network_get( 'reset_frequency' ) ) {
				wp_schedule_event( time(), Option::network_get( 'reset_frequency' ), OPCM_CRON_RESET_NAME );
			}
		}
		if ( Option::network_get( 'analytics' ) ) {
			$this->loader->add_action( OPCM_CRON_STATS_NAME, 'OPcacheManager\Plugin\Feature\Capture', 'check' );
		}
		if ( ! wp_next_scheduled( OPCM_CRON_STATS_NAME ) ) {
			if ( Option::network_get( 'analytics' ) ) {
				wp_schedule_event( time(), 'five_minutes', OPCM_CRON_STATS_NAME );
			}
		}
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function define_admin_hooks() {
		$plugin_admin = new Opcache_Manager_Admin();
		$nag          = new Nag();
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'init_admin_menus' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'finalize_admin_menus', 100 );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'normalize_admin_menus', 110 );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'init_settings_sections' );
		$this->loader->add_filter( 'plugin_action_links_' . plugin_basename( OPCM_PLUGIN_DIR . OPCM_SLUG . '.php' ), $plugin_admin, 'add_actions_links', 10, 4 );
		$this->loader->add_filter( 'plugin_row_meta', $plugin_admin, 'add_row_meta', 10, 2 );
		$this->loader->add_action( 'admin_notices', $nag, 'display' );
		$this->loader->add_action( 'wp_ajax_hide_opcm_nag', $nag, 'hide_callback' );
		$this->loader->add_action( 'wp_ajax_opcm_get_stats', 'OPcacheManager\Plugin\Feature\AnalyticsFactory', 'get_stats_callback' );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function define_public_hooks() {
		$plugin_public = new Opcache_Manager_Public();
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since 1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since  1.0.0
	 * @return Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Adds full plugin identification.
	 *
	 * @param array $plugin The already set identification information.
	 * @return array The extended identification information.
	 * @since 1.0.0
	 */
	public static function perfopsone_plugin_info( $plugin ) {
		$plugin[ OPCM_SLUG ] = [
			'name'    => OPCM_PRODUCT_NAME,
			'code'    => OPCM_CODENAME,
			'version' => OPCM_VERSION,
			'url'     => OPCM_PRODUCT_URL,
			'icon'    => self::get_base64_logo(),
		];
		return $plugin;
	}

	/**
	 * Returns a base64 svg resource for the plugin logo.
	 *
	 * @return string The svg resource as a base64.
	 * @since 1.0.0
	 */
	public static function get_base64_logo() {
		$source  = '<svg width="100%" height="100%" viewBox="0 0 1001 1001" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-miterlimit:10;">';
		$source .= '<g id="OPcache-Manager" serif:id="OPcache Manager" transform="matrix(10.0067,0,0,10.0067,0,0)">';
		$source .= '<rect x="0" y="0" width="100" height="100" style="fill:none;"/>';
		$source .= '<g id="Icons" transform="matrix(0.416389,0,0,0.416389,28.481,2.3984)">';
		$source .= '<g transform="matrix(0,-119.484,-119.484,0,50.731,119.595)"><path d="M0.95,0.611C0.95,0.632 0.933,0.649 0.911,0.649L0.174,0.649C0.153,0.649 0.136,0.632 0.136,0.611L0.136,-0.611C0.136,-0.632 0.153,-0.649 0.174,-0.649L0.911,-0.649C0.933,-0.649 0.95,-0.632 0.95,-0.611L0.95,0.611Z" style="fill:url(#_Linear1);fill-rule:nonzero;"/></g>';
		$source .= '<g transform="matrix(2.31646,0,0,2.31646,-5.58445,47.5101)"><path d="M0,15.324L15.325,3.648L29.189,15.324L46.163,0" style="fill:none;fill-rule:nonzero;stroke:rgb(65,172,255);stroke-width:0.63px;"/></g>';
		$source .= '<g transform="matrix(0,-2.31646,-2.31646,0,-6.02226,77.8983)"><path d="M-2,-2C-3.104,-2 -4,-1.104 -4,0C-4,1.104 -3.104,2 -2,2C-0.896,2 0,1.104 0,0C0,-1.104 -0.896,-2 -2,-2" style="fill:white;fill-rule:nonzero;"/></g>';
		$source .= '<g transform="matrix(0,-2.31646,-2.31646,0,31.0411,52.4172)"><path d="M-2,-2C-3.104,-2 -4,-1.104 -4,0C-4,1.104 -3.104,2 -2,2C-0.896,2 0,1.104 0,0C0,-1.104 -0.896,-2 -2,-2" style="fill:white;fill-rule:nonzero;"/></g>';
		$source .= '<g transform="matrix(0,-2.31646,-2.31646,0,61.1551,77.8983)"><path d="M-2,-2C-3.104,-2 -4,-1.104 -4,0C-4,1.104 -3.104,2 -2,2C-0.896,2 0,1.104 0,0C0,-1.104 -0.896,-2 -2,-2" style="fill:white;fill-rule:nonzero;"/></g>';
		$source .= '<g transform="matrix(0,-2.31646,-2.31646,0,100.535,43.1514)"><path d="M-2,-2C-3.104,-2 -4,-1.104 -4,0C-4,1.104 -3.104,2 -2,2C-0.896,2 0,1.104 0,0C0,-1.104 -0.896,-2 -2,-2" style="fill:white;fill-rule:nonzero;"/></g>';
		$source .= '<g transform="matrix(-2.20436e-17,-6.27646,-5.91646,-2.20436e-17,117.335,83.7114)"><path d="M-2,-2C-3.104,-2 -4,-1.104 -4,0C-4,1.104 -3.104,2 -2,2C-0.896,2 0,1.104 0,0C0,-1.104 -0.896,-2 -2,-2" style="fill:white;fill-rule:nonzero;"/></g>';
		$source .= '<g transform="matrix(-2.31646,0,0,2.31646,265.004,3.77157)"><rect x="65" y="34" width="12" height="4" style="fill:white;"/></g>';
		$source .= '<g transform="matrix(-2.31646,0,0,2.31646,70.421,-88.8869)"><rect x="23" y="54" width="12" height="4" style="fill:white;"/></g>';
		$source .= '<g transform="matrix(2.31646,0,0,2.31646,-63.9338,-61.0893)"><g opacity="0.3"><g transform="matrix(1,0,0,1,83,29)"><path d="M0,6L-67,6L-67,2C-67,0.896 -66.104,0 -65,0L-2,0C-0.896,0 0,0.896 0,2L0,6Z" style="fill:white;fill-rule:nonzero;"/></g></g></g>';
		$source .= '<g transform="matrix(2.31646,0,0,2.31646,-63.9338,-61.0893)"><g opacity="0.3"><g transform="matrix(1,0,0,1,0,6)"><rect x="20" y="33" width="59" height="28" style="fill:white;"/></g></g></g>';
		$source .= '<g transform="matrix(0,-88.9995,-93.1112,0,54.5887,204.54)"><path d="M0.629,0.034L0.629,0.633C0.629,0.68 0.366,0.964 0.366,0.964L0.129,0.682C0.124,0.67 0.129,0.586 0.129,0.571L0.24,0.016L0.24,-0.018L0.24,-0.649C0.185,-0.666 0.185,-0.695 0.185,-0.695L0.351,-0.964C0.351,-0.964 0.629,-0.699 0.629,-0.647L0.629,0.034Z" style="fill:url(#_Linear2);fill-rule:nonzero;"/></g>';
		$source .= '<g transform="matrix(0,-72.1772,-75.5118,0,53.0533,251.205)"><path d="M1.148,1.095C1.148,1.17 1.087,1.232 1.011,1.232L0.532,1.232C0.457,1.232 0.396,1.17 0.396,1.095L0.396,-1.095C0.396,-1.17 0.457,-1.232 0.532,-1.232L1.011,-1.232C1.087,-1.232 1.148,-1.17 1.148,-1.095L1.148,1.095Z" style="fill:url(#_Linear3);fill-rule:nonzero;"/></g>';
		$source .= '<g transform="matrix(0,18.7374,19.6031,0,-14.1133,189.472)"><circle cx="0.453" cy="0" r="0.264" style="fill:url(#_Linear4);"/></g>';
		$source .= '<g transform="matrix(5.16667,0,0,4.93851,-50.28,39.9276)"><g opacity="0.3"><g transform="matrix(1,0,0,-1,0,40)"><rect x="21" y="7" width="14" height="2" style="fill:white;"/></g></g></g>';
		$source .= '<g transform="matrix(0,-4.93851,-5.16667,0,11.72,193.021)"><path d="M-1,-1C-1.552,-1 -2,-0.552 -2,0C-2,0.552 -1.552,1 -1,1C-0.448,1 0,0.552 0,0C0,-0.552 -0.448,-1 -1,-1" style="fill:rgb(255,216,111);fill-rule:nonzero;"/></g>';
		$source .= '<g transform="matrix(1.06581e-16,-4.93851,-5.16667,0,37.5533,193.021)"><path d="M-1,-1C-1.552,-1 -2,-0.552 -2,0C-2,0.552 -1.552,1 -1,1C-0.448,1 0,0.552 0,0C0,-0.552 -0.448,-1 -1,-1" style="fill:rgb(255,216,111);fill-rule:nonzero;"/></g>';
		$source .= '<g transform="matrix(-9.20683,68.032,68.0292,9.19682,119.123,75.567)"><path d="M-0.179,-0.132L-0.085,-0.126C-0.082,-0.128 -0.079,-0.13 -0.076,-0.132C-0.065,-0.162 -0.051,-0.19 -0.035,-0.216C-0.035,-0.22 -0.035,-0.223 -0.035,-0.228L-0.098,-0.298C-0.11,-0.312 -0.109,-0.334 -0.095,-0.347L-0.044,-0.392L0.007,-0.437C0.021,-0.449 0.043,-0.448 0.056,-0.434L0.118,-0.363C0.123,-0.362 0.128,-0.361 0.134,-0.36C0.158,-0.371 0.185,-0.38 0.212,-0.385C0.216,-0.39 0.22,-0.395 0.224,-0.4L0.23,-0.494C0.231,-0.513 0.248,-0.527 0.267,-0.526L0.335,-0.522L0.402,-0.518C0.421,-0.517 0.436,-0.5 0.435,-0.481L0.429,-0.387C0.433,-0.38 0.438,-0.373 0.442,-0.366C0.463,-0.358 0.483,-0.348 0.502,-0.336C0.512,-0.337 0.52,-0.337 0.53,-0.338L0.601,-0.4C0.615,-0.413 0.637,-0.412 0.649,-0.397L0.739,-0.296C0.752,-0.281 0.751,-0.26 0.736,-0.247L0.661,-0.18C0.657,-0.177 0.654,-0.174 0.651,-0.17C0.665,-0.142 0.675,-0.112 0.681,-0.081C0.686,-0.08 0.691,-0.079 0.696,-0.079L0.805,-0.072C0.808,-0.072 0.812,-0.071 0.815,-0.069C0.818,-0.068 0.821,-0.066 0.823,-0.063C0.827,-0.058 0.83,-0.052 0.829,-0.044L0.825,0.032L0.82,0.108C0.819,0.115 0.816,0.122 0.811,0.126C0.809,0.128 0.806,0.13 0.803,0.131C0.799,0.132 0.796,0.133 0.792,0.133L0.684,0.126C0.683,0.126 0.682,0.126 0.682,0.126C0.677,0.126 0.673,0.126 0.668,0.126C0.658,0.157 0.645,0.185 0.628,0.211C0.63,0.215 0.633,0.219 0.636,0.223L0.703,0.298C0.715,0.312 0.714,0.334 0.7,0.347L0.649,0.392L0.598,0.437C0.584,0.449 0.562,0.448 0.549,0.434L0.487,0.363C0.478,0.361 0.469,0.36 0.46,0.358C0.439,0.367 0.418,0.375 0.397,0.38C0.391,0.387 0.386,0.393 0.381,0.4L0.375,0.494C0.374,0.513 0.357,0.527 0.338,0.526L0.271,0.522L0.203,0.518C0.184,0.517 0.169,0.5 0.171,0.481L0.176,0.387C0.173,0.382 0.169,0.377 0.165,0.371C0.139,0.362 0.114,0.351 0.091,0.336C0.086,0.337 0.081,0.337 0.075,0.338L0.005,0.4C-0.01,0.413 -0.032,0.412 -0.044,0.397L-0.089,0.346L-0.134,0.296C-0.147,0.281 -0.145,0.259 -0.131,0.247L-0.061,0.184C-0.06,0.18 -0.059,0.177 -0.058,0.173C-0.072,0.145 -0.082,0.116 -0.089,0.085C-0.092,0.083 -0.094,0.08 -0.097,0.078L-0.191,0.072C-0.21,0.071 -0.225,0.055 -0.223,0.036L-0.219,-0.032L-0.215,-0.1C-0.215,-0.101 -0.215,-0.102 -0.215,-0.102C-0.213,-0.12 -0.197,-0.133 -0.179,-0.132ZM0.443,0.009C0.448,-0.073 0.386,-0.143 0.305,-0.148C0.223,-0.153 0.153,-0.091 0.148,-0.01C0.143,0.072 0.205,0.142 0.286,0.147C0.364,0.152 0.432,0.096 0.442,0.019C0.443,0.016 0.443,0.012 0.443,0.009Z" style="fill:url(#_Linear5);fill-rule:nonzero;"/></g>';
		$source .= '</g>';
		$source .= '<g transform="matrix(-1.20714e-16,2.3878,0.416389,-1.20714e-16,53.6143,56.429)"><path d="M-5.5,-5.5L5.5,-5.5" style="fill:none;fill-rule:nonzero;stroke:url(#_Linear6);stroke-width:0.24px;stroke-linecap:round;stroke-linejoin:round;stroke-dasharray:0.49,0.49;"/></g></g>';
		$source .= '<defs>';
		$source .= '<linearGradient id="_Linear1" x1="0" y1="0" x2="1" y2="0" gradientUnits="userSpaceOnUse" gradientTransform="matrix(1,0,0,-1,0,0)"><stop offset="0" style="stop-color:rgb(25,39,131);stop-opacity:1"/><stop offset="1" style="stop-color:rgb(65,172,255);stop-opacity:1"/></linearGradient>';
		$source .= '<linearGradient id="_Linear2" x1="0" y1="0" x2="1" y2="0" gradientUnits="userSpaceOnUse" gradientTransform="matrix(1,0,0,-1,0,-3.75329e-05)"><stop offset="0" style="stop-color:rgb(25,39,131);stop-opacity:1"/><stop offset="1" style="stop-color:rgb(65,172,255);stop-opacity:1"/></linearGradient>';
		$source .= '<linearGradient id="_Linear3" x1="0" y1="0" x2="1" y2="0" gradientUnits="userSpaceOnUse" gradientTransform="matrix(1,0,0,-1,0,0)"><stop offset="0" style="stop-color:rgb(25,39,131);stop-opacity:1"/><stop offset="1" style="stop-color:rgb(65,172,255);stop-opacity:1"/></linearGradient>';
		$source .= '<linearGradient id="_Linear4" x1="0" y1="0" x2="1" y2="0" gradientUnits="userSpaceOnUse" gradientTransform="matrix(-1,0,0,1,0.906002,4.44089e-16)"><stop offset="0" style="stop-color:rgb(25,39,131);stop-opacity:1"/><stop offset="1" style="stop-color:rgb(65,172,255);stop-opacity:1"/></linearGradient>';
		$source .= '<linearGradient id="_Linear5" x1="0" y1="0" x2="1" y2="-0.000139067" gradientUnits="userSpaceOnUse" gradientTransform="matrix(1,-2.77556e-17,-2.77556e-17,-1,0,-5.95027e-05)"><stop offset="0" style="stop-color:rgb(255,216,111);stop-opacity:1"/><stop offset="1" style="stop-color:rgb(255,147,8);stop-opacity:1"/></linearGradient>';
		$source .= '<linearGradient id="_Linear6" x1="0" y1="0" x2="1" y2="-0.940977" gradientUnits="userSpaceOnUse" gradientTransform="matrix(6.20711,6.20711,6.20711,-6.20711,-3.1036,-8.6036)"><stop offset="0" style="stop-color:rgb(255,216,111);stop-opacity:1"/><stop offset="1" style="stop-color:rgb(255,147,8);stop-opacity:1"/></linearGradient>';
		$source .= '</defs>';
		$source .= '</svg>';
		// phpcs:ignore
		return 'data:image/svg+xml;base64,' . base64_encode( $source );
	}

}

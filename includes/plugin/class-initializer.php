<?php
/**
 * Plugin initialization handling.
 *
 * @package Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace OPcacheManager\Plugin;

/**
 * Fired after 'plugins_loaded' hook.
 *
 * This class defines all code necessary to run during the plugin's initialization.
 *
 * @package Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Initializer {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since   1.0.0
	 */
	public function __construct() {

	}

	/**
	 * Initialize the plugin.
	 *
	 * @since 1.0.0
	 */
	public function initialize() {
		\OPcacheManager\System\Cache::init();
		\OPcacheManager\System\Sitehealth::init();
		\OPcacheManager\System\APCu::init();
		//if ( 'en_US' !== determine_locale() ) {
			unload_textdomain( OPCM_SLUG );
			load_plugin_textdomain( OPCM_SLUG );
		//}
	}

	/**
	 * Initialize the plugin.
	 *
	 * @since 1.0.0
	 */
	public function late_initialize() {
		require_once OPCM_PLUGIN_DIR . 'perfopsone/init.php';
	}

}

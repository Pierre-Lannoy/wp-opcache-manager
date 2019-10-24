<?php
/**
 * OPcache handling
 *
 * Handles all OPcache operations and detection.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace OPcacheManager\System;

use OPcacheManager\System\Logger;

/**
 * Define the OPcache functionality.
 *
 * Handles all OPcache operations and detection.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class OPcache {

	/**
	 * Initializes the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
	}

	/**
	 * Get a blog name.
	 *
	 * @param   boolean $automatic Optional. Is the reset automatically done (via cron, for example).
	 * @since   1.0.0
	 */
	public static function reset( $automatic = true ) {
		if ( function_exists( 'opcache_reset' ) ) {
			opcache_reset();
			Logger::info( $automatic ? 'OPcache reset.' : 'OPcache manually reset.' );
		}
	}

}

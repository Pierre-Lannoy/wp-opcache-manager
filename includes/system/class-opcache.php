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
use OPcacheManager\System\Option;

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
	 * The list of status.
	 *
	 * @since  1.0.0
	 * @var    array    $status    Maintains the status list.
	 */
	public static $status = [ 'disabled', 'enabled', 'cache_full', 'restart_pending', 'restart_in_progress', 'recycle_in_progress', 'warmup' ];

	/**
	 * The list of reset types.
	 *
	 * @since  1.0.0
	 * @var    array    $status    Maintains the status list.
	 */
	public static $resets = [ 'none', 'oom', 'hash', 'manual' ];

	/**
	 * Initializes the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
	}

	/**
	 * Reset the cache (force invalidate all).
	 *
	 * @param   boolean $automatic Optional. Is the reset automatically done (via cron, for example).
	 * @since   1.0.0
	 */
	public static function reset( $automatic = true ) {
		if ( function_exists( 'opcache_reset' ) ) {
			opcache_reset();
			Logger::info( $automatic ? 'OPcache reset via cron.' : 'OPcache reset via manual action.' );
			if ( $automatic && Option::network_get( 'warmup' ) ) {
				self::warmup();
			}
		}
	}

	/**
	 * Reset the cache (force invalidate all).
	 *
	 * @param   boolean $automatic Optional. Is the reset automatically done (via cron, for example).
	 * @since   1.0.0
	 */
	public static function warmup( $automatic = true ) {
		if ( function_exists( 'opcache_compile_file' ) ) {

			Logger::info( $automatic ? 'Site warmed-up via cron.' : 'Site warmed-up via manual action.' );
		}
	}

}

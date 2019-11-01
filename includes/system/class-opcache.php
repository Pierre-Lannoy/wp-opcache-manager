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
use OPcacheManager\System\File;

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
	 * The list of file not compilable/recompilable.
	 *
	 * @since  1.0.0
	 * @var    array    $status    Maintains the file list.
	 */
	public static $do_not_compile = [ 'includes/plugin.php', 'includes/options.php', 'includes/misc.php', 'includes/menu.php' ];

	/**
	 * Initializes the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
	}

	/**
	 * Invalidate files.
	 *
	 * @param   array $files List of files to invalidate.
	 * @param   boolean $force Optional. Has the invalidation to be forced.
	 * @return integer The number of invalidated files.
	 * @since   1.0.0
	 */
	public static function invalidate( $files, $force = false ) {
		$cpt = 0;
		if ( function_exists( 'opcache_invalidate' ) ) {
			foreach ( $files as $file ) {
				if ( 0 === strpos( $file, './' ) ) {
					$file = str_replace( '..', '', $file );
					$file = str_replace( './', ABSPATH, $file );
					if ( opcache_invalidate( $file, $force ) ) {
						$cpt++;
					}
				}
			}
			if ( $force ) {
				$s = 'Forced invalidation';
			} else {
				$s = 'Invalidation';
			}
			Logger::info( sprintf( '%s: %d file(s).', $s, $cpt ) );
		}
		return $cpt;
	}

	/**
	 * Recompile files.
	 *
	 * @param   array $files List of files to recompile.
	 * @param   boolean $force Optional. Has the invalidation to be forced.
	 * @return integer The number of recompiled files.
	 * @since   1.0.0
	 */
	public static function recompile( $files, $force = false ) {
		$cpt = 0;
		if ( function_exists( 'opcache_invalidate' ) && function_exists( 'opcache_compile_file' ) && function_exists( 'opcache_is_script_cached' ) ) {
			foreach ( $files as $file ) {
				if ( 0 === strpos( $file, './' ) ) {
					foreach ( self::$do_not_compile as $item ) {
						if ( false !== strpos( $file, $item ) ) {
							Logger::debug( sprintf( 'File "%s" must not be recompiled.', $file ) );
							continue 2;
						}
					}
					$file = str_replace( '..', '', $file );
					$file = str_replace( './', ABSPATH, $file );
					if ( $force ) {
						opcache_invalidate( $file, true );
					}
					if ( ! opcache_is_script_cached( $file ) ) {
						try {
							// phpcs:ignore
							if ( @opcache_compile_file( $file ) ) {
								$cpt++;
							} else {
								Logger::error( sprintf( 'Unable to compile file "%s".', $file ) );
							}
						} catch ( \Throwable $e ) {
							Logger::warning( sprintf( 'Unable to compile file "%s": %s.', $file, $e->getMessage() ), $e->getCode() );
						}
					} else {
						Logger::error( sprintf( 'File "%s" already cached.', $file ) );
					}
				}
			}
			Logger::info( sprintf( 'Recompilation: %d file(s).', $cpt ) );
		}
		return $cpt;
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
			Logger::info( $automatic ? 'OPcache reset initiated via cron.' : 'OPcache reset initiated via manual action.' );
			if ( $automatic && Option::network_get( 'warmup' ) ) {
				self::warmup();
			}
		}
	}

	/**
	 * Warm-up the site.
	 *
	 * @param   boolean $automatic Optional. Is the warmup done (via cron, for example).
	 * @return integer The number of recompiled files.
	 * @since   1.0.0
	 */
	public static function warmup( $automatic = true ) {
		$files = [];
		foreach ( File::list_files( ABSPATH, 100, [ '/^.*\.php$/i' ], [], true ) as $file ) {
			$files[] = str_replace( ABSPATH, './', $file );
		}
		$result = self::recompile( $files );
		Logger::info( $automatic ? 'Site warm-up initiated via cron.' : 'Site warmed-up initiated via manual action.' );
		return $result;
	}

}

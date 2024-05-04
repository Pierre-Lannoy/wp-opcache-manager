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
	public static $status = [ 'disabled', 'enabled', 'cache_full', 'restart_pending', 'restart_in_progress', 'recycle_in_progress', 'warmup', 'reset_warmup' ];

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
	 * Verify if OPcache API usage is restricted.
	 *
	 * @return  boolean     True if it is restricted, false otherwise.
	 * @since 1.0.0
	 */
	public static function is_restricted() {
		// phpcs:ignore
		set_error_handler( null );
		// phpcs:ignore
		$test = @opcache_get_configuration();
		// phpcs:ignore
		restore_error_handler();
		return ! is_array( $test );
	}

	/**
	 * Get the options infos for Site Health "info" tab.
	 *
	 * @since 1.0.0
	 */
	public static function debug_info() {
		$result['product'] = [
			'label' => 'Product',
			'value' => self::name(),
		];
		if ( function_exists( 'opcache_get_configuration' ) && function_exists( 'opcache_get_status' ) ) {
			if ( ! self::is_restricted() ) {
				// phpcs:ignore
				$raw = @opcache_get_configuration();
				if ( array_key_exists( 'directives', $raw ) ) {
					foreach ( $raw['directives'] as $key => $directive ) {
						$result[ 'directive_' . $key ] = [
							'label' => '[Directive] ' . str_replace( 'opcache.', '', $key ),
							'value' => $directive,
						];
					}
				}
				$raw = opcache_get_status();
				foreach ( $raw as $key => $status ) {
					if ( 'scripts' === $key ) {
						continue;
					}
					if ( is_array( $status ) ) {
						foreach ( $status as $skey => $sstatus ) {
							$result[ 'status_' . $skey ] = [
								'label' => '[Status] ' . $skey,
								'value' => $sstatus,
							];
						}
					} else {
						$result[ 'status_' . $key ] = [
							'label' => '[Status] ' . $key,
							'value' => $status,
						];
					}
				}
			} else {
				$result['product'] = [
					'label' => 'Status',
					'value' => 'Unknown - OPcache API usage is restricted',
				];
			}
		} else {
			$result['product'] = [
				'label' => 'Status',
				'value' => 'Disabled',
			];
		}
		return $result;
	}

	/**
	 * Get name and version.
	 *
	 * @return string The name and version of the product.
	 * @since   1.0.0
	 */
	public static function name() {
		$result = '';
		if ( function_exists( 'opcache_get_configuration' ) ) {
			if ( ! self::is_restricted() ) {
				// phpcs:ignore
				$raw = @opcache_get_configuration();
				if ( array_key_exists( 'version', $raw ) ) {
					if ( array_key_exists( 'opcache_product_name', $raw['version'] ) ) {
						$result = $raw['version']['opcache_product_name'];
					}
					if ( array_key_exists( 'version', $raw['version'] ) ) {
						$version = $raw['version']['version'];
						if ( false !== strpos( $version, '-' ) ) {
							$version = substr( $version, 0, strpos( $version, '-' ) );
						}
						$result .= ' ' . $version;
					}
				}
			} else {
				$result = '';
			}
		}
		return $result;
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
		if ( function_exists( 'opcache_invalidate' ) && ! self::is_restricted() ) {
			if ( $force ) {
				$s = 'Forced invalidation';
			} else {
				$s = 'Invalidation';
			}
			$span = \DecaLog\Engine::tracesLogger( OPCM_SLUG )->startSpan( $s, DECALOG_SPAN_MAIN_RUN );
			foreach ( $files as $file ) {
				if ( 0 === strpos( $file, './' ) ) {
					$file = str_replace( '..', '', $file );
					$file = str_replace( './', OPCM_ABSPATH, $file );
					if ( opcache_invalidate( $file, $force ) ) {
						$cpt++;
					}
				}
			}
			\DecaLog\Engine::eventsLogger( OPCM_SLUG )->info( sprintf( '%s: %d file(s).', $s, $cpt ) );
			\DecaLog\Engine::tracesLogger( OPCM_SLUG )->endSpan( $span );
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
		if ( function_exists( 'opcache_invalidate' ) && function_exists( 'opcache_compile_file' ) && function_exists( 'opcache_is_script_cached' ) && ! self::is_restricted() ) {
			if ( $force ) {
				$s = 'Recompilation';
			} else {
				$s = 'Compilation';
			}
			$span = \DecaLog\Engine::tracesLogger( OPCM_SLUG )->startSpan( $s, DECALOG_SPAN_MAIN_RUN );
			// phpcs:ignore
			set_error_handler( null );
			try {
				foreach ( $files as $file ) {
					if ( false !== strpos( $file, OPCM_CONTENT ) ) {
						\DecaLog\Engine::eventsLogger( OPCM_SLUG )->debug( sprintf( 'File "%s" must not be recompiled.', $file ) );
						continue;
					}
					if ( $force ) {
						opcache_invalidate( $file, true );
					}
					if ( ! opcache_is_script_cached( $file ) ) {
						// phpcs:ignore
						if ( @opcache_compile_file( $file ) ) {
							$cpt++;
						} else {
							\DecaLog\Engine::eventsLogger( OPCM_SLUG )->debug( sprintf( 'Unable to compile file "%s".', $file ) );
						}
					} else {
						\DecaLog\Engine::eventsLogger( OPCM_SLUG )->debug( sprintf( 'File "%s" already cached.', $file ) );
					}
				}
			} catch ( \Throwable $e ) {
				\DecaLog\Engine::eventsLogger( OPCM_SLUG )->warning( sprintf( 'Unable to complete full warmup: %s.', $e->getMessage() ), [ 'code' => $e->getCode() ] );
			} finally {
				// phpcs:ignore
				restore_error_handler();
			}
			\DecaLog\Engine::eventsLogger( OPCM_SLUG )->info( sprintf( 'Recompilation: %d files.', $cpt ) );
			\DecaLog\Engine::tracesLogger( OPCM_SLUG )->endSpan( $span );
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
		if ( $automatic && Option::network_get( 'warmup', false ) ) {
			self::warmup( $automatic, true );
		} else {
			$files = [];
			if ( function_exists( 'opcache_get_status' ) && ! self::is_restricted() ) {
				try {
					$raw = opcache_get_status( true );
					if ( array_key_exists( 'scripts', $raw ) ) {
						foreach ( $raw['scripts'] as $script ) {
							if ( false === strpos( $script['full_path'], OPCM_ABSPATH ) ) {
								continue;
							}
							$files[] = str_replace( OPCM_ABSPATH, './', $script['full_path'] );
						}
						self::invalidate( $files, true );
					}
				} catch ( \Throwable $e ) {
					\DecaLog\Engine::eventsLogger( OPCM_SLUG )->error( sprintf( 'Unable to query OPcache status: %s.', $e->getMessage() ), [ 'code' => $e->getCode() ] );
				}
			}
		}
	}

	/**
	 * Warm-up the site.
	 *
	 * @param   boolean $automatic Optional. Is the warmup automatically done (via cron, for example).
	 * @param   boolean $force Optional. Has invalidation to be forced.
	 * @return integer The number of recompiled files.
	 * @since   1.0.0
	 */
	public static function warmup( $automatic = true, $force = false ) {
		$files = File::list_files( OPCM_ABSPATH, 100, [ '/^.*\.php$/i' ], [], true );
		if ( Environment::is_wordpress_multisite() ) {
			\DecaLog\Engine::eventsLogger( OPCM_SLUG )->info( $automatic ? 'Network reset and warm-up initiated via cron.' : 'Network warm-up initiated via manual action.' );
		} else {
			\DecaLog\Engine::eventsLogger( OPCM_SLUG )->info( $automatic ? 'Site reset and warm-up initiated via cron.' : 'Site warm-up initiated via manual action.' );
		}
		$result = self::recompile( $files, $force );
		if ( $automatic ) {
			Cache::set_global( '/Data/ResetWarmupTimestamp', time(), 'check' );
		} else {
			Cache::set_global( '/Data/WarmupTimestamp', time(), 'check' );
		}
		if ( Environment::is_wordpress_multisite() ) {
			\DecaLog\Engine::eventsLogger( OPCM_SLUG )->info( sprintf( 'Network warm-up terminated. %d files were recompiled', $result ) );
		} else {
			\DecaLog\Engine::eventsLogger( OPCM_SLUG )->info( sprintf( 'Site warm-up terminated. %d files were recompiled', $result ) );
		}
		return $result;
	}

	/**
	 * Checks if short-scheduled invalidation/warmup is needed.
	 *
	 * @since   3.0.0
	 */
	public static function check() {
		$invalidate = Option::network_get( 'flash_invalidate' );
		Option::network_set( 'flash_invalidate', false );
		$warmup = Option::network_get( 'flash_warmup' );
		Option::network_set( 'flash_warmup', false );
		if ( $warmup ) {
			self::warmup( false, true );
		} elseif ( $invalidate ) {
			self::reset( false );
		}
	}

}

<?php
/**
 * OPcacheManager capture
 *
 * Handles all captures operations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace OPcacheManager\Plugin\Feature;

use OPcacheManager\System\Cache;
use OPcacheManager\System\Logger;
use OPcacheManager\System\OPcache;
use OPcacheManager\Plugin\Feature\Schema;

/**
 * Define the captures functionality.
 *
 * Handles all captures operations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Capture {

	/**
	 * Delta time.
	 *
	 * @since  1.0.0
	 * @var    integer    $delta    The authorized delta time in seconds.
	 */
	public static $delta = 90;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
	}

	/**
	 * Add a new 5 minutes interval capacity to the WP cron feature.
	 *
	 * @since 1.0.0
	 */
	public static function add_cron_05_minutes_interval( $schedules ) {
		$schedules['five_minutes'] = [
			'interval' => 300,
			'display'  => __( 'Every five minutes', 'opcache-manager' ),
		];
		return $schedules;
	}

	/**
	 * Check status and record it if needed.
	 *
	 * @since    1.0.0
	 */
	public static function check() {
		$schema = new Schema();
		$record = $schema->init_record();
		$time   = time();
		if ( function_exists( 'opcache_get_status' ) && ! OPcache::is_restricted() ) {
			$cache_id = '/Data/LastCheck';
			$old      = Cache::get_global( $cache_id );
			if ( ! isset( $old ) ) {
				\DecaLog\Engine::eventsLogger( OPCM_SLUG )->debug( 'No OPcache transient.' );
			} elseif ( ! array_key_exists( 'timestamp', $old ) ) {
				\DecaLog\Engine::eventsLogger( OPCM_SLUG )->debug( 'No OPcache timestamp.' );
			} elseif ( 300 - self::$delta > $time - $old['timestamp'] ) {
				\DecaLog\Engine::eventsLogger( OPCM_SLUG )->debug( sprintf( 'Delta time too short: %d sec. Launching recycling process.', $time - $old['timestamp'] ) );
			} elseif ( 300 + self::$delta < $time - $old['timestamp'] ) {
				\DecaLog\Engine::eventsLogger( OPCM_SLUG )->debug( sprintf( 'Delta time too long: %d sec. Launching recycling process.', $time - $old['timestamp'] ) );
			}
			if ( isset( $old ) && array_key_exists( 'timestamp', $old ) && ( 300 + self::$delta > $time - $old['timestamp'] ) ) {
				try {
					$restart            = false;
					$value              = [];
					$value['raw']       = opcache_get_status( false );
					$value['timestamp'] = $time;
					$record['status']   = 'enabled';
					// Trying to figure out the status.
					if ( array_key_exists( 'cache_full', $value['raw'] ) && (bool) $value['raw']['cache_full'] ) {
						$record['status'] = 'cache_full';
					}
					if ( array_key_exists( 'restart_pending', $value['raw'] ) && (bool) $value['raw']['restart_pending'] ) {
						$record['status'] = 'restart_pending';
					}
					if ( array_key_exists( 'restart_in_progress', $value['raw'] ) && (bool) $value['raw']['restart_in_progress'] ) {
						$record['status'] = 'restart_in_progress';
					}
					$warmup_timestamp = Cache::get_global( '/Data/WarmupTimestamp' );
					if ( false !== $warmup_timestamp ) {
						if ( 300 > $time - $warmup_timestamp ) {
							$record['status'] = 'warmup';
						}
					}
					$reset_warmup_timestamp = Cache::get_global( '/Data/ResetWarmupTimestamp' );
					if ( false !== $reset_warmup_timestamp ) {
						if ( 300 > $time - $reset_warmup_timestamp ) {
							$record['status'] = 'reset_warmup';
						}
					}
					// Trying to figure out the restart type.
					if ( array_key_exists( 'opcache_statistics', $old['raw'] ) && array_key_exists( 'opcache_statistics', $value['raw'] ) && array_key_exists( 'oom_restarts', $old['raw']['opcache_statistics'] ) && array_key_exists( 'oom_restarts', $value['raw']['opcache_statistics'] ) ) {
						if ( (int) $old['raw']['opcache_statistics']['oom_restarts'] !== (int) $value['raw']['opcache_statistics']['oom_restarts'] ) {
							$restart         = true;
							$record['reset'] = 'oom';
						}
					}
					if ( array_key_exists( 'opcache_statistics', $old['raw'] ) && array_key_exists( 'opcache_statistics', $value['raw'] ) && array_key_exists( 'hash_restarts', $old['raw']['opcache_statistics'] ) && array_key_exists( 'hash_restarts', $value['raw']['opcache_statistics'] ) ) {
						if ( (int) $old['raw']['opcache_statistics']['hash_restarts'] !== (int) $value['raw']['opcache_statistics']['hash_restarts'] ) {
							$restart         = true;
							$record['reset'] = 'hash';
						}
					}
					if ( array_key_exists( 'opcache_statistics', $old['raw'] ) && array_key_exists( 'opcache_statistics', $value['raw'] ) && array_key_exists( 'manual_restarts', $old['raw']['opcache_statistics'] ) && array_key_exists( 'manual_restarts', $value['raw']['opcache_statistics'] ) ) {
						if ( (int) $old['raw']['opcache_statistics']['manual_restarts'] !== (int) $value['raw']['opcache_statistics']['manual_restarts'] ) {
							$restart         = true;
							$record['reset'] = 'manual';
						}
					}
					if ( array_key_exists( 'memory_usage', $value['raw'] ) && array_key_exists( 'used_memory', $value['raw']['memory_usage'] ) ) {
						$record['mem_used'] = (int) $value['raw']['memory_usage']['used_memory'];
					}
					if ( array_key_exists( 'memory_usage', $value['raw'] ) && array_key_exists( 'wasted_memory', $value['raw']['memory_usage'] ) ) {
						$record['mem_wasted'] = (int) $value['raw']['memory_usage']['wasted_memory'];
					}
					if ( array_key_exists( 'memory_usage', $value['raw'] ) && array_key_exists( 'free_memory', $value['raw']['memory_usage'] ) ) {
						$free = (int) $value['raw']['memory_usage']['free_memory'];
					} else {
						$free = 0;
					}
					$record['mem_total'] = $free + $record['mem_used'] + $record['mem_wasted'];
					if ( array_key_exists( 'opcache_statistics', $value['raw'] ) && array_key_exists( 'max_cached_keys', $value['raw']['opcache_statistics'] ) ) {
						$record['key_total'] = (int) $value['raw']['opcache_statistics']['max_cached_keys'];
					}
					if ( array_key_exists( 'opcache_statistics', $value['raw'] ) && array_key_exists( 'num_cached_keys', $value['raw']['opcache_statistics'] ) ) {
						$record['key_used'] = (int) $value['raw']['opcache_statistics']['num_cached_keys'];
					}
					if ( array_key_exists( 'interned_strings_usage', $value['raw'] ) && array_key_exists( 'buffer_size', $value['raw']['interned_strings_usage'] ) ) {
						$record['buf_total'] = (int) $value['raw']['interned_strings_usage']['buffer_size'];
					}
					if ( array_key_exists( 'interned_strings_usage', $value['raw'] ) && array_key_exists( 'used_memory', $value['raw']['interned_strings_usage'] ) ) {
						$record['buf_used'] = (int) $value['raw']['interned_strings_usage']['used_memory'];
					}
					if ( $restart ) {
						if ( array_key_exists( 'opcache_statistics', $value['raw'] ) && array_key_exists( 'hits', $value['raw']['opcache_statistics'] ) ) {
							$record['hit'] = (int) $value['raw']['opcache_statistics']['hits'];
						}
						if ( array_key_exists( 'opcache_statistics', $value['raw'] ) && array_key_exists( 'misses', $value['raw']['opcache_statistics'] ) ) {
							$record['miss'] = (int) $value['raw']['opcache_statistics']['misses'];
						}
					} else {
						if ( array_key_exists( 'opcache_statistics', $old['raw'] ) && array_key_exists( 'opcache_statistics', $value['raw'] ) && array_key_exists( 'hits', $old['raw']['opcache_statistics'] ) && array_key_exists( 'hits', $value['raw']['opcache_statistics'] ) ) {
							$record['hit'] = (int) $value['raw']['opcache_statistics']['hits'] - (int) $old['raw']['opcache_statistics']['hits'];
						}
						if ( array_key_exists( 'opcache_statistics', $old['raw'] ) && array_key_exists( 'opcache_statistics', $value['raw'] ) && array_key_exists( 'misses', $old['raw']['opcache_statistics'] ) && array_key_exists( 'misses', $value['raw']['opcache_statistics'] ) ) {
							$record['miss'] = (int) $value['raw']['opcache_statistics']['misses'] - (int) $old['raw']['opcache_statistics']['misses'];
						}
					}
					if ( array_key_exists( 'opcache_statistics', $value['raw'] ) && array_key_exists( 'num_cached_scripts', $value['raw']['opcache_statistics'] ) ) {
						$record['scripts'] = (int) $value['raw']['opcache_statistics']['num_cached_scripts'];
					}
					if ( array_key_exists( 'interned_strings_usage', $value['raw'] ) && array_key_exists( 'number_of_strings', $value['raw']['interned_strings_usage'] ) ) {
						$record['strings'] = (int) $value['raw']['interned_strings_usage']['number_of_strings'];
					}
					Cache::set_global( $cache_id, $value, 'check' );
					$schema->write_statistics_record_to_database( $record );
					\DecaLog\Engine::eventsLogger( OPCM_SLUG )->debug( 'OPcache is enabled. Statistics recorded.' );
				} catch ( \Throwable $e ) {
					\DecaLog\Engine::eventsLogger( OPCM_SLUG )->error( sprintf( 'Unable to query OPcache status: %s.', $e->getMessage() ), [ 'code' => $e->getCode() ] );
				}
			} elseif ( isset( $old ) && array_key_exists( 'timestamp', $old ) && ( 300 - self::$delta > $time - $old['timestamp'] ) ) {
				// What to do when delta is less 59 sec ?
			} else {
				try {
					$value              = [];
					$value['raw']       = opcache_get_status( false );
					$value['timestamp'] = $time;
					Cache::set_global( $cache_id, $value, 'check' );
					$record['status'] = 'recycle_in_progress';
					$schema->write_statistics_record_to_database( $record );
					\DecaLog\Engine::eventsLogger( OPCM_SLUG )->debug( 'OPcache is enabled. Recovery cycle.' );
				} catch ( \Throwable $e ) {
					\DecaLog\Engine::eventsLogger( OPCM_SLUG )->error( sprintf( 'Unable to query OPcache status: %s.', $e->getMessage() ), [ 'code' => $e->getCode() ] );
				}
			}
		} else {
			$schema->write_statistics_record_to_database( $record );
			\DecaLog\Engine::eventsLogger( OPCM_SLUG )->debug( 'OPcache is disabled. No statistics to record.' );
		}
	}

	/**
	 * Publish metrics.
	 *
	 * @since    2.3.0
	 */
	public static function metrics() {
		if ( function_exists( 'opcache_get_status' ) && ! OPcache::is_restricted() ) {
			$span     = \DecaLog\Engine::tracesLogger( OPCM_SLUG )->startSpan( 'Metrics collation', DECALOG_SPAN_PLUGINS_LOAD );
			$cache_id = 'metrics/lastcheck';
			$metrics  = Cache::get_global( $cache_id );
			if ( ! isset( $metrics ) ) {
				try {
					$value = opcache_get_status( false );
					if ( array_key_exists( 'memory_usage', $value ) && array_key_exists( 'used_memory', $value['memory_usage'] ) ) {
						$used = (int) $value['memory_usage']['used_memory'];
					} else {
						$used = 0;
					}
					if ( array_key_exists( 'memory_usage', $value ) && array_key_exists( 'wasted_memory', $value['memory_usage'] ) ) {
						$wasted = (int) $value['memory_usage']['wasted_memory'];
					} else {
						$wasted = 0;
					}
					if ( array_key_exists( 'memory_usage', $value ) && array_key_exists( 'free_memory', $value['memory_usage'] ) ) {
						$free = (int) $value['memory_usage']['free_memory'];
					} else {
						$free = 0;
					}
					$total = $free + $used + $wasted;
					if ( 0 < $total ) {
						$metrics['mem']    = $used / $total;
						$metrics['wasted'] = $wasted / $total;
					} else {
						$metrics['mem']    = 0.0;
						$metrics['wasted'] = 0.0;
					}
					if ( array_key_exists( 'opcache_statistics', $value ) && array_key_exists( 'max_cached_keys', $value['opcache_statistics'] ) ) {
						$key_total = (int) $value['opcache_statistics']['max_cached_keys'];
					} else {
						$key_total = 0;
					}
					if ( array_key_exists( 'opcache_statistics', $value ) && array_key_exists( 'num_cached_keys', $value['opcache_statistics'] ) ) {
						$key_used = (int) $value['opcache_statistics']['num_cached_keys'];
					} else {
						$key_used = 0;
					}
					if ( 0 < $key_total ) {
						$metrics['key'] = $key_used / $key_total;
					} else {
						$metrics['key'] = 0.0;
					}
					if ( array_key_exists( 'interned_strings_usage', $value ) && array_key_exists( 'buffer_size', $value['interned_strings_usage'] ) ) {
						$buf_total = (int) $value['interned_strings_usage']['buffer_size'];
					} else {
						$buf_total = 0;
					}
					if ( array_key_exists( 'interned_strings_usage', $value ) && array_key_exists( 'used_memory', $value['interned_strings_usage'] ) ) {
						$buf_used = (int) $value['interned_strings_usage']['used_memory'];
					} else {
						$buf_used = 0;
					}
					if ( 0 < $buf_total ) {
						$metrics['buffer'] = $buf_used / $buf_total;
					} else {
						$metrics['buffer'] = 0.0;
					}
					if ( array_key_exists( 'opcache_statistics', $value ) && array_key_exists( 'hits', $value['opcache_statistics'] ) ) {
						$hit = (int) $value['opcache_statistics']['hits'];
					} else {
						$hit = 0;
					}
					if ( array_key_exists( 'opcache_statistics', $value ) && array_key_exists( 'misses', $value['opcache_statistics'] ) ) {
						$miss = (int) $value['opcache_statistics']['misses'];
					} else {
						$miss = 0;
					}
					$total = $hit + $miss;
					if ( 0 < $total ) {
						$metrics['hit_ratio'] = $hit / $total;
					} else {
						$metrics['hit_ratio'] = 0.0;
					}
					if ( array_key_exists( 'opcache_statistics', $value ) && array_key_exists( 'num_cached_scripts', $value['opcache_statistics'] ) ) {
						$metrics['scripts'] = (int) $value['opcache_statistics']['num_cached_scripts'];
					} else {
						$metrics['scripts'] = 0;
					}
					Cache::set_global( $cache_id, $metrics, 'metrics' );
					\DecaLog\Engine::eventsLogger( OPCM_SLUG )->debug( 'OPcache is enabled. Statistics recorded.' );
				} catch ( \Throwable $e ) {
					$metrics = null;
					\DecaLog\Engine::eventsLogger( OPCM_SLUG )->error( sprintf( 'Unable to query OPcache status: %s.', $e->getMessage() ), [ 'code' => $e->getCode() ] );
				}
			}
			if ( isset( $metrics ) ) {
				$monitor = \DecaLog\Engine::metricsLogger( OPCM_SLUG );
				if ( array_key_exists( 'mem', $metrics ) ) {
					$monitor->createProdGauge( 'memory_saturation', $metrics['mem'], 'OPcache used memory - [percent]' );
				}
				if ( array_key_exists( 'wasted', $metrics ) ) {
					$monitor->createProdGauge( 'memory_wasted', $metrics['wasted'], 'OPcache wasted memory - [percent]' );
				}
				if ( array_key_exists( 'key', $metrics ) ) {
					$monitor->createProdGauge( 'key_saturation', $metrics['key'], 'OPcache used keys - [percent]' );
				}
				if ( array_key_exists( 'buffer', $metrics ) ) {
					$monitor->createProdGauge( 'buffer_saturation', $metrics['buffer'], 'OPcache used buffers - [percent]' );
				}
				if ( array_key_exists( 'hit_ratio', $metrics ) ) {
					$monitor->createProdGauge( 'hit_ratio', $metrics['hit_ratio'], 'OPcache hit ratio - [percent]' );
				}
				if ( array_key_exists( 'scripts', $metrics ) ) {
					$monitor->createProdGauge( 'script_total', $metrics['scripts'], 'Number of cached scripts - [count]' );
				}
			}
			\DecaLog\Engine::tracesLogger( OPCM_SLUG )->endSpan( $span );
		} else {
			\DecaLog\Engine::eventsLogger( OPCM_SLUG )->debug( 'OPcache is disabled. No metrics to collate.' );
		}
	}

}

<?php
/**
 * Analytics factory
 *
 * Handles all analytics creation and queries.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace OPcacheManager\Plugin\Feature;

use OPcacheManager\Plugin\Feature\Analytics;
use OPcacheManager\System\Blog;
use OPcacheManager\System\Date;
use OPcacheManager\System\Timezone;

/**
 * Define the analytics factory functionality.
 *
 * Handles all analytics creation and queries.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class AnalyticsFactory {

	/**
	 * Ajax callback.
	 *
	 * @since    1.0.0
	 */
	public static function get_stats_callback() {
		check_ajax_referer( 'ajax_opcm', 'nonce' );
		$analytics = self::get_analytics( true );
		$query     = filter_input( INPUT_POST, 'query' );
		$queried   = filter_input( INPUT_POST, 'queried' );
		exit( wp_json_encode( $analytics->query( $query, $queried ) ) );
	}

	/**
	 * Get the content of the analytics page.
	 *
	 * @param   boolean $reload  Optional. Is it a reload of an already displayed analytics.
	 * @since 1.0.0
	 */
	public static function get_analytics( $reload = false ) {
		if ( ! ( $start = filter_input( INPUT_GET, 'start' ) ) ) {
			$start = filter_input( INPUT_POST, 'start' );
		}
		if ( empty( $start ) || ! Date::is_date_exists( $start, 'Y-m-d' ) ) {
			$sdatetime = new \DateTime( 'now' );
			$start     = $sdatetime->format( 'Y-m-d' );
		} else {
			$sdatetime = new \DateTime( $start );
		}
		if ( ! ( $end = filter_input( INPUT_GET, 'end' ) ) ) {
			$end = filter_input( INPUT_POST, 'end' );
		}
		if ( empty( $end ) || ! Date::is_date_exists( $end, 'Y-m-d' ) ) {
			$edatetime = new \DateTime( 'now' );
			$end       = $edatetime->format( 'Y-m-d' );
		} else {
			$edatetime = new \DateTime( $end );
		}
		if ( $edatetime->getTimestamp() < $sdatetime->getTimestamp() ) {
			$start = $edatetime->format( 'Y-m-d' );
			$end   = $sdatetime->format( 'Y-m-d' );
		}
		return new Analytics( $start, $end, $reload );
	}

}

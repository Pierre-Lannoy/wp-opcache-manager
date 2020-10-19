<?php
/**
 * DataBeam integration
 *
 * Handles all DataBeam integration and queries.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace OPcacheManager\Plugin\Integration;

use OPcacheManager\System\Option;
use OPcacheManager\System\Role;
use OPcacheManager\Plugin\Core;

/**
 * Define the DataBeam integration.
 *
 * Handles all DataBeam integration and queries.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.1.1
 */
class Databeam {

	/**
	 * Init the class.
	 *
	 * @since    1.0.0
	 */
	public static function init() {
		add_filter( 'databeam_source_register', [ static::class, 'register_kpi' ] );
	}

	/**
	 * Register OPcache kpis endpoints for DataBeam.
	 *
	 * @param   array   $integrations   The already registered integrations.
	 * @return  array   The new integrations.
	 * @since    1.0.0
	 */
	public static function register_kpi( $integrations ) {
		$integrations[ OPCM_SLUG . '::kpi' ] = [
			'name'         => OPCM_PRODUCT_NAME,
			'version'      => OPCM_VERSION,
			'subname'      => __( 'KPIs', 'opcache-manager' ),
			'description'  => __( 'Allows to integrate, as a DataBeam source, all KPIs related to OPcache.', 'opcache-manager' ),
			'instruction'  => __( 'Just add this and use it as source in your favorite visualizers and publishers.', 'opcache-manager' ),
			'note'         => __( 'In multisite environments, this source is available for all network sites.', 'opcache-manager' ),
			'legal'        =>
				[
					'author'  => 'Pierre Lannoy',
					'url'     => 'https://github.com/Pierre-Lannoy',
					'license' => 'gpl3',
				],
			'icon'         =>
				[
					'static' => [
						'class'  => '\OPcacheManager\Plugin\Core',
						'method' => 'get_base64_logo',
					],
				],
			'type'         => 'collection::kpi',
			'restrictions' => [ 'only_network' ],
			'ttl'          => '0-3600:300',
			'caching'      => [ 'locale' ],
			'data_call'    =>
				[
					'static' => [
						'class'  => '\OPcacheManager\Plugin\Feature\Analytics',
						'method' => 'get_status_kpi_collection',
					],
				],
			'data_args'    => [],
		];
		return $integrations;
	}

	/**
	 * Returns a base64 svg resource for the banner.
	 *
	 * @return string The svg resource as a base64.
	 * @since 1.0.0
	 */
	public static function get_base64_banner() {
		$filename = __DIR__ . '/banner.svg';
		if ( file_exists( $filename ) ) {
			// phpcs:ignore
			$content = @file_get_contents( $filename );
		} else {
			$content = '';
		}
		if ( $content ) {
			// phpcs:ignore
			return 'data:image/svg+xml;base64,' . base64_encode( $content );
		}
		return '';
	}

	/**
	 * Register server infos endpoints for DataBeam.
	 *
	 * @param   array   $integrations   The already registered integrations.
	 * @return  array   The new integrations.
	 * @since    1.0.0
	 */
	public static function register_info( $integrations ) {
		return $integrations;
	}

}

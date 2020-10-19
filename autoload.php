<?php
/**
 * Autoload for OPcache Manager.
 *
 * @package Bootstrap
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

spl_autoload_register(
	function ( $class ) {
		$classname = $class;
		$filepath  = __DIR__ . '/';
		if ( strpos( $classname, 'OPcacheManager\\' ) === 0 ) {
			while ( strpos( $classname, '\\' ) !== false ) {
				$classname = substr( $classname, strpos( $classname, '\\' ) + 1, 1000 );
			}
			$filename = 'class-' . str_replace( '_', '-', strtolower( $classname ) ) . '.php';
			if ( strpos( $class, 'OPcacheManager\System\\' ) === 0 ) {
				$filepath = OPCM_INCLUDES_DIR . 'system/';
			}
			if ( strpos( $class, 'OPcacheManager\Plugin\Feature\\' ) === 0 ) {
				$filepath = OPCM_INCLUDES_DIR . 'features/';
			} elseif ( strpos( $class, 'OPcacheManager\Plugin\Integration\\' ) === 0 ) {
				$filepath = OPCM_INCLUDES_DIR . 'integrations/';
			} elseif ( strpos( $class, 'OPcacheManager\Plugin\\' ) === 0 ) {
				$filepath = OPCM_INCLUDES_DIR . 'plugin/';
			}
			if ( strpos( $class, 'OPcacheManager\Library\\' ) === 0 ) {
				$filepath = OPCM_VENDOR_DIR;
			}
			if ( strpos( $filename, '-public' ) !== false ) {
				$filepath = OPCM_PUBLIC_DIR;
			}
			if ( strpos( $filename, '-admin' ) !== false ) {
				$filepath = OPCM_ADMIN_DIR;
			}
			$file = $filepath . $filename;
			if ( file_exists( $file ) ) {
				include_once $file;
			}
		}
	}
);

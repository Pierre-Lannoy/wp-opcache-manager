<?php
/**
 * WP-CLI for OPcache Manager.
 *
 * Adds WP-CLI commands to OPcache Manager
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.0.0
 */

namespace OPcacheManager\Plugin\Feature;

use OPcacheManager\System\OPcache;
use OPcacheManager\System\Environment;
use OPcacheManager\System\Option;
use OPcacheManager\Plugin\Feature\Analytics;
use OPcacheManager\System\Markdown;
use OPcacheManager\Plugin\Opcache_Manager_Admin;
use Spyc;

/**
 * Manages OPcache and get analytics about its use.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.0.0
 */
class Wpcli {

	/**
	 * List of exit codes.
	 *
	 * @since    2.0.0
	 * @var array $exit_codes Exit codes.
	 */
	private $exit_codes = [
		0   => 'operation successful.',
		1   => 'unrecognized setting.',
		2   => 'unrecognized action.',
		3   => 'analytics are disabled.',
		4   => 'invalidation or warmup already scheduled.',
		255 => 'unknown error.',
	];

	/**
	 * Write ids as clean stdout.
	 *
	 * @param   array   $ids   The ids.
	 * @param   string  $field  Optional. The field to output.
	 * @since   2.0.0
	 */
	private function write_ids( $ids, $field = '' ) {
		$result = '';
		$last   = end( $ids );
		foreach ( $ids as $key => $id ) {
			if ( '' === $field ) {
				$result .= $key;
			} else {
				$result .= $id[ $field ];
			}
			if ( $id !== $last ) {
				$result .= ' ';
			}
		}
		// phpcs:ignore
		fwrite( STDOUT, $result );
	}

	/**
	 * Write an error.
	 *
	 * @param   integer  $code      Optional. The error code.
	 * @param   boolean  $stdout    Optional. Clean stdout output.
	 * @since   2.0.0
	 */
	private function error( $code = 255, $stdout = false ) {
		$msg = '[' . OPCM_PRODUCT_NAME . '] ' . ucfirst( $this->exit_codes[ $code ] );
		if ( \WP_CLI\Utils\isPiped() ) {
			// phpcs:ignore
			fwrite( STDOUT, '' );
			// phpcs:ignore
			exit( $code );
		} elseif ( $stdout ) {
			// phpcs:ignore
			fwrite( STDERR, $msg );
			// phpcs:ignore
			exit( $code );
		} else {
			\WP_CLI::error( $msg );
		}
	}

	/**
	 * Write a warning.
	 *
	 * @param   string   $msg       The message.
	 * @param   string   $result    Optional. The result.
	 * @param   boolean  $stdout    Optional. Clean stdout output.
	 * @since   2.0.0
	 */
	private function warning( $msg, $result = '', $stdout = false ) {
		$msg = '[' . OPCM_PRODUCT_NAME . '] ' . ucfirst( $msg );
		if ( \WP_CLI\Utils\isPiped() || $stdout ) {
			// phpcs:ignore
			fwrite( STDOUT, $result );
		} else {
			\WP_CLI::warning( $msg );
		}
	}

	/**
	 * Write a success.
	 *
	 * @param   string   $msg       The message.
	 * @param   string   $result    Optional. The result.
	 * @param   boolean  $stdout    Optional. Clean stdout output.
	 * @since   2.0.0
	 */
	private function success( $msg, $result = '', $stdout = false ) {
		$msg = '[' . OPCM_PRODUCT_NAME . '] ' . ucfirst( $msg );
		if ( \WP_CLI\Utils\isPiped() || $stdout ) {
			// phpcs:ignore
			fwrite( STDOUT, $result );
		} else {
			\WP_CLI::success( $msg );
		}
	}

	/**
	 * Write a wimple line.
	 *
	 * @param   string   $msg       The message.
	 * @param   string   $result    Optional. The result.
	 * @param   boolean  $stdout    Optional. Clean stdout output.
	 * @since   2.0.0
	 */
	private function line( $msg, $result = '', $stdout = false ) {
		if ( \WP_CLI\Utils\isPiped() || $stdout ) {
			// phpcs:ignore
			fwrite( STDOUT, $result );
		} else {
			\WP_CLI::line( $msg );
		}
	}

	/**
	 * Write a wimple log line.
	 *
	 * @param   string   $msg       The message.
	 * @param   boolean  $stdout    Optional. Clean stdout output.
	 * @since   2.0.0
	 */
	private function log( $msg, $stdout = false ) {
		if ( ! \WP_CLI\Utils\isPiped() && ! $stdout ) {
			\WP_CLI::log( $msg );
		}
	}

	/**
	 * Get params from command line.
	 *
	 * @param   array   $args   The command line parameters.
	 * @return  array The true parameters.
	 * @since   2.0.0
	 */
	private function get_params( $args ) {
		$result = '';
		if ( array_key_exists( 'settings', $args ) ) {
			$result = \json_decode( $args['settings'], true );
		}
		if ( ! $result || ! is_array( $result ) ) {
			$result = [];
		}
		return $result;
	}

	/**
	 * Get OPcache Manager details and operation modes.
	 *
	 * ## EXAMPLES
	 *
	 * wp opcache status
	 *
	 *
	 *     === For other examples and recipes, visit https://github.com/Pierre-Lannoy/wp-opcache-manager/blob/master/WP-CLI.md ===
	 *
	 */
	public function status( $args, $assoc_args ) {
		\WP_CLI::line( sprintf( '%s is running.', Environment::plugin_version_text() ) );
		$name = OPcache::name();
		if ( '' === $name ) {
			\WP_CLI::line( 'OPcache is not activated for command-line.' );
		} else {
			\WP_CLI::line( sprintf( '%s is activated for command-line.', $name ) );
		}

		if ( 'reset' === Option::network_get( 'reset_frequency' ) ) {
			\WP_CLI::line( 'Site invalidation: disabled.' );
		} else {
			$message = 'Site invalidation: ';
			foreach ( Opcache_Manager_Admin::get_frequencies() as $f ) {
				if ( $f[0] === Option::network_get( 'reset_frequency' ) ) {
					$message .= $f[1];
				}
			}
			if ( Option::network_get( 'warmup' ) ) {
				$message .= ' - followed by site warm-up';
			}
			\WP_CLI::line( $message . '.' );
		}
		if ( Option::network_get( 'analytics' ) ) {
			\WP_CLI::line( 'Analytics: enabled.' );
		} else {
			\WP_CLI::line( 'Analytics: disabled.' );
		}
		if ( Option::network_get( 'metrics' ) ) {
			\WP_CLI::line( 'Metrics collation: enabled.' );
		} else {
			\WP_CLI::line( 'Metrics collation: disabled.' );
		}
		if ( \DecaLog\Engine::isDecalogActivated() ) {
			\WP_CLI::line( 'Logging support: ' . \DecaLog\Engine::getVersionString() . '.' );
		} else {
			\WP_CLI::line( 'Logging support: no.' );
		}
	}

	/**
	 * Modify OPcache Manager main settings.
	 *
	 * ## OPTIONS
	 *
	 * <enable|disable>
	 * : The action to take.
	 *
	 * <analytics|metrics>
	 * : The setting to change.
	 *
	 * [--yes]
	 * : Answer yes to the confirmation message, if any.
	 *
	 * [--stdout]
	 * : Use clean STDOUT output to use results in scripts. Unnecessary when piping commands because piping is detected by OPcache Manager.
	 *
	 * ## EXAMPLES
	 *
	 * wp opcache settings enable analytics
	 *
	 *
	 *     === For other examples and recipes, visit https://github.com/Pierre-Lannoy/wp-opcache-manager/blob/master/WP-CLI.md ===
	 *
	 */
	public function settings( $args, $assoc_args ) {
		$stdout  = \WP_CLI\Utils\get_flag_value( $assoc_args, 'stdout', false );
		$action  = isset( $args[0] ) ? (string) $args[0] : '';
		$setting = isset( $args[1] ) ? (string) $args[1] : '';
		switch ( $action ) {
			case 'enable':
				switch ( $setting ) {
					case 'analytics':
						Option::network_set( 'analytics', true );
						$this->success( 'analytics are now activated.', '', $stdout );
						break;
					case 'metrics':
						Option::network_set( 'metrics', true );
						$this->success( 'metrics collation is now activated.', '', $stdout );
						break;
					default:
						$this->error( 1, $stdout );
				}
				break;
			case 'disable':
				switch ( $setting ) {
					case 'analytics':
						\WP_CLI::confirm( 'Are you sure you want to deactivate analytics?', $assoc_args );
						Option::network_set( 'analytics', false );
						$this->success( 'analytics are now deactivated.', '', $stdout );
						break;
					case 'metrics':
						\WP_CLI::confirm( 'Are you sure you want to deactivate metrics collation?', $assoc_args );
						Option::network_set( 'metrics', false );
						$this->success( 'metrics collation is now deactivated.', '', $stdout );
						break;
					default:
						$this->error( 1, $stdout );
				}
				break;
			default:
				$this->error( 2, $stdout );
		}
	}

	/**
	 * Get OPcache analytics for today.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Set the output format. Note if json or yaml is chosen: full metadata is outputted too.
	 * ---
	 * default: table
	 * options:
	 *  - table
	 *  - json
	 *  - csv
	 *  - yaml
	 *  - count
	 * ---
	 *
	 * [--stdout]
	 * : Use clean STDOUT output to use results in scripts. Unnecessary when piping commands because piping is detected by OPcache Manager.
	 *
	 * ## EXAMPLES
	 *
	 * wp opcache analytics
	 *
	 *
	 *    === For other examples and recipes, visit https://github.com/Pierre-Lannoy/wp-opcache-manager/blob/master/WP-CLI.md ===
	 *
	 */
	public function analytics( $args, $assoc_args ) {
		$stdout = \WP_CLI\Utils\get_flag_value( $assoc_args, 'stdout', false );
		if ( ! Option::network_get( 'analytics' ) ) {
			$this->error( 3, $stdout );
		}
		$analytics = Analytics::get_status_kpi_collection();
		$result    = [];
		if ( array_key_exists( 'data', $analytics ) ) {
			foreach ( $analytics['data'] as $kpi ) {
				$item                = [];
				$item['kpi']         = $kpi['name'];
				$item['description'] = $kpi['description'];
				$item['value']       = $kpi['value']['human'];
				if ( array_key_exists( 'ratio', $kpi ) && isset( $kpi['ratio'] ) ) {
					$item['ratio'] = $kpi['ratio']['percent'] . '%';
				} else {
					$item['ratio'] = '-';
				}
				$item['variation'] = ( 0 < $kpi['variation']['percent'] ? '+' : '' ) . (string) $kpi['variation']['percent'] . '%';
				$result[]          = $item;
			}
		}
		$format = \WP_CLI\Utils\get_flag_value( $assoc_args, 'format', 'table' );
		if ( 'json' === $format ) {
			$detail = wp_json_encode( $analytics );
			$this->line( $detail, $detail, $stdout );
		} elseif ( 'yaml' === $format ) {
			unset( $analytics['assets'] );
			$detail = Spyc::YAMLDump( $analytics, true, true, true );
			$this->line( $detail, $detail, $stdout );
		} else {
			\WP_CLI\Utils\format_items( $assoc_args['format'], $result, [ 'kpi', 'description', 'value', 'ratio', 'variation' ] );
		}
	}

	/**
	 * Get information on exit codes.
	 *
	 * ## OPTIONS
	 *
	 * <list>
	 * : The action to take.
	 * ---
	 * options:
	 *  - list
	 * ---
	 *
	 * [--format=<format>]
	 * : Allows overriding the output of the command when listing exit codes.
	 * ---
	 * default: table
	 * options:
	 *  - table
	 *  - json
	 *  - csv
	 *  - yaml
	 *  - ids
	 *  - count
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 * Lists available exit codes:
	 * + wp opcache exitcode list
	 * + wp opcache exitcode list --format=json
	 *
	 *
	 *   === For other examples and recipes, visit https://github.com/Pierre-Lannoy/wp-traffic/blob/master/WP-CLI.md ===
	 *
	 */
	public function exitcode( $args, $assoc_args ) {
		$stdout = \WP_CLI\Utils\get_flag_value( $assoc_args, 'stdout', false );
		$format = \WP_CLI\Utils\get_flag_value( $assoc_args, 'format', 'table' );
		$action = isset( $args[0] ) ? $args[0] : 'list';
		$codes  = [];
		foreach ( $this->exit_codes as $key => $msg ) {
			$codes[ $key ] = [
				'code'    => $key,
				'meaning' => ucfirst( $msg ),
			];
		}
		switch ( $action ) {
			case 'list':
				if ( 'ids' === $format ) {
					$this->write_ids( $codes );
				} else {
					\WP_CLI\Utils\format_items( $format, $codes, [ 'code', 'meaning' ] );
				}
				break;
		}
	}

	/**
	 * Schedules an immediate invalidation.
	 *
	 * [--yes]
	 * : Answer yes to the confirmation message, if any.
	 *
	 * [--stdout]
	 * : Use clean STDOUT output to use results in scripts. Unnecessary when piping commands because piping is detected by OPcache Manager.
	 *
	 * ## EXAMPLES
	 *
	 * Invalidate all site/network files:
	 * + wp opcache invalidate
	 *
	 *
	 *   === For other examples and recipes, visit https://github.com/Pierre-Lannoy/wp-traffic/blob/master/WP-CLI.md ===
	 *
	 */
	public function invalidate( $args, $assoc_args ) {
		$stdout     = \WP_CLI\Utils\get_flag_value( $assoc_args, 'stdout', false );
		$invalidate = Option::network_get( 'flash_invalidate' );
		$warmup     = Option::network_get( 'flash_warmup' );
		if ( $invalidate || $warmup ) {
			$this->error( 4, $stdout );
		} else {
			\WP_CLI::confirm( 'Are you sure you want to schedule a full invalidation?', $assoc_args );
			Option::network_set( 'flash_invalidate', true );
			$this->success( 'invalidation scheduled to start in less than 5 minutes.', '', $stdout );
		}
	}

	/**
	 * Schedules an immediate invalidation followed by a warmup.
	 *
	 * [--yes]
	 * : Answer yes to the confirmation message, if any.
	 *
	 * [--stdout]
	 * : Use clean STDOUT output to use results in scripts. Unnecessary when piping commands because piping is detected by OPcache Manager.
	 *
	 * ## EXAMPLES
	 *
	 * Invalidate and warmup all site/network files:
	 * + wp opcache warmup
	 *
	 *
	 *   === For other examples and recipes, visit https://github.com/Pierre-Lannoy/wp-traffic/blob/master/WP-CLI.md ===
	 *
	 */
	public function warmup( $args, $assoc_args ) {
		$stdout     = \WP_CLI\Utils\get_flag_value( $assoc_args, 'stdout', false );
		$invalidate = Option::network_get( 'flash_invalidate' );
		$warmup     = Option::network_get( 'flash_warmup' );
		if ( $invalidate || $warmup ) {
			$this->error( 4, $stdout );
		} else {
			\WP_CLI::confirm( 'Are you sure you want to schedule a full invalidation followed by a warmup?', $assoc_args );
			Option::network_set( 'flash_warmup', true );
			$this->success( 'invalidation and warmup scheduled to start in less than 5 minutes.', '', $stdout );
		}
	}

	/**
	 * Get the WP-CLI help file.
	 *
	 * @param   array $attributes  'style' => 'markdown', 'html'.
	 *                             'mode'  => 'raw', 'clean'.
	 * @return  string  The output of the shortcode, ready to print.
	 * @since 1.0.0
	 */
	public static function sc_get_helpfile( $attributes ) {
		$md = new Markdown();
		return $md->get_shortcode( 'WP-CLI.md', $attributes );
	}

}

add_shortcode( 'opcm-wpcli', [ 'OPcacheManager\Plugin\Feature\Wpcli', 'sc_get_helpfile' ] );

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	\WP_CLI::add_command( 'opcache', 'OPcacheManager\Plugin\Feature\Wpcli' );
}

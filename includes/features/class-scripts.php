<?php
/**
 * Scripts list
 *
 * Lists all available scripts.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace OPcacheManager\Plugin\Feature;

use OPcacheManager\System\Conversion;
use OPcacheManager\System\Logger;
use OPcacheManager\System\Date;
use OPcacheManager\System\Timezone;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Define the scripts list functionality.
 *
 * Lists all available scripts.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Scripts extends \WP_List_Table {

	/**
	 * The scripts handler.
	 *
	 * @since    1.0.0
	 * @var      array    $scripts    The scripts list.
	 */
	private $scripts = [];

	/**
	 * The number of lines to display.
	 *
	 * @since    1.0.0
	 * @var      integer    $limit    The number of lines to display.
	 */
	private $limit = 50;

	/**
	 * The order by of the list.
	 *
	 * @since    1.0.0
	 * @var      string    $orderby    The order by of the list.
	 */
	private $orderby = 'script';

	/**
	 * The order of the list.
	 *
	 * @since    1.0.0
	 * @var      string    $order    The order of the list.
	 */
	private $order = 'desc';

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		parent::__construct(
			[
				'singular' => 'script',
				'plural'   => 'scripts',
				'ajax'     => true,
			]
		);
		global $wp_version;
		if ( version_compare( $wp_version, '4.2-z', '>=' ) && $this->compat_fields && is_array( $this->compat_fields ) ) {
			array_push( $this->compat_fields, 'all_items' );
		}
		$this->scripts = [];
		if ( function_exists( 'opcache_get_status' ) ) {
			try {
				$raw = opcache_get_status( true );
				if ( array_key_exists( 'scripts', $raw ) ) {
					foreach ( $raw['scripts'] as $script ) {
						$item              = [];
						$item['script']    = str_replace( ABSPATH, './', $script['full_path'] );
						$item['hit']       = $script['hits'];
						$item['memory']    = $script['memory_consumption'];
						$item['timestamp'] = $script['timestamp'];
						$item['used']      = $script['last_used_timestamp'];
						$this->scripts[]   = $item;
					}
				}
			} catch ( \Throwable $e ) {
				Logger::error( sprintf( 'Unable to query OPcache status: %s.', $e->getMessage() ), $e->getCode() );
			}
		}
		$this->limit = filter_input( INPUT_GET, 'limit', FILTER_SANITIZE_NUMBER_INT );
		if ( ! $this->limit ) {
			$this->limit = 50;
		}
		$this->order = filter_input( INPUT_GET, 'order', FILTER_SANITIZE_STRING );
		if ( ! $this->order ) {
			$this->order = 'desc';
		}
		$this->orderby = filter_input( INPUT_GET, 'orderby', FILTER_SANITIZE_STRING );
		if ( ! $this->orderby ) {
			$this->orderby = 'script';
		}
		$this->process_action();
	}

	/**
	 * Default column formatter.
	 *
	 * @param   array  $item   The current item.
	 * @param   string $column_name The current column name.
	 * @return  string  The cell formatted, ready to print.
	 * @since    1.0.0
	 */
	protected function column_default( $item, $column_name ) {
		return $item[ $column_name ];
	}

	/**
	 * Check box column formatter.
	 *
	 * @param   array $item   The current item.
	 * @return  string  The cell formatted, ready to print.
	 * @since    1.0.0
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="bulk[]" value="%s" />',
			$item['script']
		);
	}

	/**
	 * "hit" column formatter.
	 *
	 * @param   array $item   The current item.
	 * @return  string  The cell formatted, ready to print.
	 * @since    1.0.0
	 */
	protected function column_hit( $item ) {
		return Conversion::number_shorten( $item['hit'] );
	}

	/**
	 * "memory" column formatter.
	 *
	 * @param   array $item   The current item.
	 * @return  string  The cell formatted, ready to print.
	 * @since    1.0.0
	 */
	protected function column_memory( $item ) {
		return Conversion::data_shorten( $item['memory'] );
	}

	/**
	 * "used" column formatter.
	 *
	 * @param   array $item   The current item.
	 * @return  string  The cell formatted, ready to print.
	 * @since    1.0.0
	 */
	protected function column_used( $item ) {
		$time = new \DateTime();
		$time->setTimestamp( $item['used'] );
		return ucfirst( Date::get_positive_time_diff_from_mysql_utc( $time->format( 'Y-m-d H:i:s' ) ) );
	}

	/**
	 * "timestamp" column formatter.
	 *
	 * @param   array $item   The current item.
	 * @return  string  The cell formatted, ready to print.
	 * @since    1.0.0
	 */
	protected function column_timestamp( $item ) {
	    $time = new \DateTime();
	    $time->setTimestamp( $item['timestamp'] );
		return Date::get_date_from_mysql_utc( $time->format( 'Y-m-d H:i:s' ), Timezone::network_get()->getName(), 'Y-m-d H:i:s' );
	}

	/**
	 * Enumerates columns.
	 *
	 * @return      array   The columns.
	 * @since    1.0.0
	 */
	public function get_columns() {
		$columns = [
			'cb'        => '<input type="checkbox" />',
			'script'    => esc_html__( 'File', 'opcache-manager' ),
			'timestamp' => esc_html__( 'Timestamp', 'opcache-manager' ),
			'hit'       => esc_html__( 'Hits', 'opcache-manager' ),
			'memory'    => esc_html__( 'Memory size', 'opcache-manager' ),
			'used'      => esc_html__( 'Used', 'opcache-manager' ),
		];
		return $columns;
	}

	/**
	 * Enumerates hidden columns.
	 *
	 * @return      array   The hidden columns.
	 * @since    1.0.0
	 */
	protected function get_hidden_columns() {
		return [];
	}

	/**
	 * Enumerates sortable columns.
	 *
	 * @return      array   The sortable columns.
	 * @since    1.0.0
	 */
	protected function get_sortable_columns() {
		$sortable_columns = [
			'script'    => [ 'script', true ],
			'hit'       => [ 'hit', false ],
			'memory'    => [ 'memory', false ],
			'timestamp' => [ 'timestamp', false ],
			'used'      => [ 'used', false ],
		];
		return $sortable_columns;
	}

	/**
	 * Enumerates bulk actions.
	 *
	 * @return      array   The bulk actions.
	 * @since    1.0.0
	 */
	public function get_bulk_actions() {
		return [
			'invalidate' => esc_html__( 'Invalidate', 'opcache-manager' ),
			'force'      => esc_html__( 'Force invalidate', 'opcache-manager' ),
			'recompile'  => esc_html__( 'Recompile', 'opcache-manager' ),
		];
	}

	/**
	 * Generate the table navigation above or below the table
	 *
	 * @param string $which Position of extra control.
	 * @since 1.0.0
	 */
	protected function display_tablenav( $which ) {
	    if ( 'top' === $which ) {
			wp_nonce_field( 'bulk-scripts' );
		}
		echo '<div class="tablenav ' . esc_attr( $which ) . '">';
		if ( $this->has_items() ) {
			echo '<div class="alignleft actions bulkactions">';
			$this->bulk_actions( $which );
			echo '</div>';
        }
		$this->extra_tablenav( $which );
		$this->pagination( $which );
		echo '<br class="clear" />';
		echo '</div>';
	}

	/**
	 * Extra controls to be displayed between bulk actions and pagination.
	 *
	 * @param string $which Position of extra control.
	 * @since 1.0.0
	 */
	public function extra_tablenav( $which ) {
		$list = $this;
		$args = compact( 'list' );
		foreach ( $args as $key => $val ) {
			$$key = $val;
		}
		if ( 'top' === $which || 'bottom' === $which ) {
			include OPCM_ADMIN_DIR . 'partials/opcache-manager-admin-tools-lines.php';
		}
	}

	/**
	 * Prepares the list to be displayed.
	 *
	 * @since    1.0.0
	 */
	public function prepare_items() {
		$current_page = $this->get_pagenum();
		$total_items  = count( $this->scripts );
		$this->set_pagination_args(
			[
				'total_items' => $total_items,
				'per_page'    => $this->limit,
				'total_pages' => ceil( $total_items / $this->limit ),
			]
		);
		$columns               = $this->get_columns();
		$hidden                = $this->get_hidden_columns();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = [ $columns, $hidden, $sortable ];
		$data                  = $this->scripts;
		usort(
			$data,
			function ( $a, $b ) {
				if ( 'script' === $this->orderby ) {
					$result = strcmp( strtolower( $a[ $this->orderby ] ), strtolower( $b[ $this->orderby ] ) );
				} else {
					$result = intval( $a[ $this->orderby ] ) < intval( $b[ $this->orderby ] ) ? 1 : -1;
				}
				return ( 'asc' === $this->order ) ? -$result : $result;
			}
		);
		$this->items = array_slice( $data, ( ( $current_page - 1 ) * $this->limit ), $this->limit );
	}

	/**
	 * Get available lines breakdowns.
	 *
	 * @since 1.0.0
	 */
	public function get_line_number_select() {
		$_disp  = [ 50, 100, 250, 500 ];
		$result = [];
		foreach ( $_disp as $d ) {
			$l          = [];
			$l['value'] = $d;
			// phpcs:ignore
			$l['text']     = sprintf( esc_html__( 'Display %d files per page', 'opcache-manager' ), $d );
			$l['selected'] = ( intval( $d ) === intval( $this->limit ) ? 'selected="selected" ' : '' );
			$result[]      = $l;
		}
		return $result;
	}

	public function process_action() {
		if ( ! isset( $_POST['bulk'] ) || empty( $_POST['bulk'] ) ) {
			return; // Thou shall not pass! There is nothing to do
		}

		/*
		$jpms = Jetpack_Network::init();

		$action = $this->current_action();
		switch ( $action ) {
			case 'connect':
				foreach( $_POST['bulk'] as $k => $site ) {
					$jpms->do_subsiteregister( $site );
				}
				break;
			case 'disconnect':
				foreach( $_POST['bulk'] as $k => $site ) {
					$jpms->do_subsitedisconnect( $site );
				}
				break;
		}*/
	}

}

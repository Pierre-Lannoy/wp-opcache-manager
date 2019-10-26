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
						$item             = [];
						$item['script']   = str_replace( ABSPATH, './', $script['full_path'] );
						$item['hit']      = $script['hits'];
						$item['memory']   = $script['memory_consumption'];
						$item['compiled'] = $script['timestamp'];
						$item['used']     = $script['last_used_timestamp'];
						$this->scripts[]  = $item;
					}
				}
			} catch ( \Throwable $e ) {
				Logger::error( sprintf( 'Unable to query OPcache status: %s.', $e->getMessage() ), $e->getCode() );
			}
		}
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
	 * "name" column formatter.
	 *
	 * @param   array $item   The current item.
	 * @return  string  The cell formatted, ready to print.
	 * @since    1.0.0
	 *
	protected function column_name( $item ) {
		$edit              = esc_url(
			add_query_arg(
				[
					'page'   => 'decalog-settings',
					'action' => 'form-edit',
					'tab'    => 'scripts',
					'uuid'   => $item['uuid'],
				],
				admin_url( 'options-general.php' )
			)
		);
		$delete            = esc_url(
			add_query_arg(
				[
					'page'   => 'decalog-settings',
					'action' => 'form-delete',
					'tab'    => 'scripts',
					'uuid'   => $item['uuid'],
				],
				admin_url( 'options-general.php' )
			)
		);
		$pause             = esc_url(
			add_query_arg(
				[
					'page'   => 'decalog-settings',
					'action' => 'pause',
					'tab'    => 'scripts',
					'uuid'   => $item['uuid'],
					'nonce'  => wp_create_nonce( 'decalog-logger-pause-' . $item['uuid'] ),
				],
				admin_url( 'options-general.php' )
			)
		);
		$test              = esc_url(
			add_query_arg(
				[
					'page'   => 'decalog-settings',
					'action' => 'test',
					'tab'    => 'scripts',
					'uuid'   => $item['uuid'],
					'nonce'  => wp_create_nonce( 'decalog-logger-test-' . $item['uuid'] ),
				],
				admin_url( 'options-general.php' )
			)
		);
		$start             = esc_url(
			add_query_arg(
				[
					'page'   => 'decalog-settings',
					'action' => 'start',
					'tab'    => 'scripts',
					'uuid'   => $item['uuid'],
					'nonce'  => wp_create_nonce( 'decalog-logger-start-' . $item['uuid'] ),
				],
				admin_url( 'options-general.php' )
			)
		);
		$view              = esc_url(
			add_query_arg(
				[
					'page'      => 'decalog-viewer',
					'logger_id' => $item['uuid'],
				],
				admin_url( 'tools.php' )
			)
		);
		$handler           = $this->handler_types->get( $item['handler'] );
		$icon              = '<img style="width:34px;float:left;padding-right:6px;" src="' . $handler['icon'] . '" />';
		$actions['edit']   = sprintf( '<a href="%s">' . esc_html__( 'Edit', 'decalog' ) . '</a>', $edit );
		$actions['delete'] = sprintf( '<a href="%s">' . esc_html__( 'Remove', 'decalog' ) . '</a>', $delete );
		if ( $item['running'] ) {
			$actions['pause'] = sprintf( '<a href="%s">' . esc_html__( 'Pause', 'decalog' ) . '</a>', $pause );
		} else {
			$actions['start'] = sprintf( '<a href="%s">' . esc_html__( 'Start', 'decalog' ) . '</a>', $start );
		}
		if ( 'WordpressHandler' === $handler['id'] ) {
			$actions['view'] = sprintf( '<a href="%s">' . esc_html__( 'View', 'decalog' ) . '</a>', $view );
		}
		if ( $item['running'] ) {
			$actions['test'] = sprintf( '<a href="%s">' . esc_html__( 'Send Test', 'decalog' ) . '</a>', $test );
		}
		return $icon . '&nbsp;' . sprintf( '<a href="%1$s">%2$s</a><br /><span style="color:silver">&nbsp;%3$s</span>%4$s', $edit, $item['name'], $handler['name'], $this->row_actions( $actions ) );
	}

	/**
	 * "status" column formatter.
	 *
	 * @param   array $item   The current item.
	 * @return  string  The cell formatted, ready to print.
	 * @since    1.0.0
	 *
	protected function column_status( $item ) {
		$status = ( $item['running'] ? '▶&nbsp;' . esc_html__( 'Running', 'decalog' ) : '❙❙&nbsp;' . esc_html__( 'Paused', 'decalog' ) );
		return $status;
	}

	/**
	 * "details" column formatter.
	 *
	 * @param   array $item   The current item.
	 * @return  string  The cell formatted, ready to print.
	 * @since    1.0.0
	 *
	protected function column_details( $item ) {
		$list = [ esc_html__( 'Standard', 'decalog' ) ];
		foreach ( $item['processors'] as $processor ) {
			$list[] = $this->processor_types->get( $processor )['name'];
		}
		return implode( ', ', $list );
	}

	/**
	 * "minimal level" column formatter.
	 *
	 * @param   array $item   The current item.
	 * @return  string  The cell formatted, ready to print.
	 * @since    1.0.0
	 *
	protected function column_level( $item ) {
		$name = ucfirst( strtolower( Log::level_name( $item['level'] ) ) );
		return $name;
	}*/

	/**
	 * Enumerates columns.
	 *
	 * @return      array   The columns.
	 * @since    1.0.0
	 */
	public function get_columns() {
		$columns = [
			'cb'       => '<input type="checkbox" />',
			'script'   => esc_html__( 'File', 'opcache-manager' ),
			'hit'      => esc_html__( 'Hits', 'opcache-manager' ),
			'memory'   => esc_html__( 'Memory size', 'opcache-manager' ),
			'compiled' => esc_html__( 'Compiled at', 'opcache-manager' ),
			'used'     => esc_html__( 'Last used', 'opcache-manager' ),
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
			'script'   => [ 'script', true ],
			'hit'      => [ 'hit', false ],
			'memory'   => [ 'memory', false ],
			'compiled' => [ 'compiled', false ],
			'used'     => [ 'used', false ],
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
		if ( 'top' === $which ) {
			include OPCM_ADMIN_DIR . 'partials/opcache-manager-admin-tools-lines.php';
		}
		if ( 'bottom' === $which ) {
			include OPCM_ADMIN_DIR . 'partials/opcache-manager-admin-tools-lines.php';
		}
	}

	/**
	 * Prepares the list to be displayed.
	 *
	 * @since    1.0.0
	 */
	public function prepare_items() {
		$this->limit = filter_input( INPUT_GET, 'limit', FILTER_SANITIZE_NUMBER_INT );
		if ( ! $this->limit ) {
			$this->limit = 50;
		}
		$per_page     = $this->limit;
		$current_page = $this->get_pagenum();
		$total_items  = count( $this->scripts );
		$this->set_pagination_args(
			[
				'total_items' => $total_items,
				'per_page'    => $this->limit,
				'total_pages' => ceil( $total_items / $this->limit ),
			]
		);
		$this->process_bulk_action();
		$columns               = $this->get_columns();
		$hidden                = $this->get_hidden_columns();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = [ $columns, $hidden, $sortable ];
		$data                  = $this->scripts;
		usort(
			$data,
			function ( $a, $b ) {
				//phpcs:ignore
				$orderby = ( ! empty( $_REQUEST['orderby'] ) ) ? $_REQUEST['orderby'] : 'script';
				//phpcs:ignore
				$order  = ( ! empty( $_REQUEST['order'] ) ) ? $_REQUEST['order'] : 'desc';
				if ( 'script' === $orderby ) {
					$result = strcmp( strtolower( $a[ $orderby ] ), strtolower( $b[ $orderby ] ) );
				} else {
					$result = intval( $a[ $orderby ] ) < intval( $b[ $orderby ] ) ? 1 : -1;
				}
				return ( 'asc' === $order ) ? -$result : $result;
			}
		);
		$this->items = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );
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
			$l['selected'] = ( $d === $this->limit ? 'selected="selected" ' : '' );
			$result[]      = $l;
		}
		return $result;
	}

	public function process_bulk_action() {
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

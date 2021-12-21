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
use OPcacheManager\System\OPcache;
use OPcacheManager\System\Environment;

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
	private $limit = 0;

	/**
	 * The page to display.
	 *
	 * @since    1.0.0
	 * @var      integer    $limit    The page to display.
	 */
	private $paged = 1;

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
	 * The current url.
	 *
	 * @since    1.0.0
	 * @var      string    $url    The current url.
	 */
	private $url = '';

	/**
	 * The form nonce.
	 *
	 * @since    1.0.0
	 * @var      string    $nonce    The form nonce.
	 */
	private $nonce = '';

	/**
	 * The action to perform.
	 *
	 * @since    1.0.0
	 * @var      string    $action    The action to perform.
	 */
	private $action = '';

	/**
	 * The bulk args.
	 *
	 * @since    1.0.0
	 * @var      array    $bulk    The bulk args.
	 */
	private $bulk = [];

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
		$this->process_args();
		$this->process_action();
		$this->scripts = [];
		if ( function_exists( 'opcache_get_status' ) ) {
			try {
				$raw = opcache_get_status( true );
				if ( array_key_exists( 'scripts', $raw ) ) {
					foreach ( $raw['scripts'] as $script ) {
						if ( false === strpos( $script['full_path'], OPCM_ABSPATH ) ) {
							continue;
						}
						$item              = [];
						$item['script']    = str_replace( OPCM_ABSPATH, './', $script['full_path'] );
						$item['hit']       = $script['hits'];
						$item['memory']    = $script['memory_consumption'];
						$item['timestamp'] = $script['timestamp'];
						$item['used']      = $script['last_used_timestamp'];
						$this->scripts[]   = $item;
					}
				}
			} catch ( \Throwable $e ) {
				\DecaLog\Engine::eventsLogger( OPCM_SLUG )->error( sprintf( 'Unable to query OPcache status: %s.', $e->getMessage() ), [ 'code' => $e->getCode() ] );
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
			wp_nonce_field( 'bulk-opcm-tools', '_wpnonce', false );
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
		$args = compact( 'list', 'which' );
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
		$this->set_pagination_args(
			[
				'total_items' => count( $this->scripts ),
				'per_page'    => $this->limit,
				'total_pages' => ceil( count( $this->scripts ) / $this->limit ),
			]
		);
		$current_page          = $this->get_pagenum();
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

	/**
	 * Pagination links.
	 *
	 * @param string $which Position of extra control.
	 * @since 1.0.0
	 */
	protected function pagination( $which ) {
		if ( empty( $this->_pagination_args ) ) {
			return;
		}
		$total_items     = (int) $this->_pagination_args['total_items'];
		$total_pages     = (int) $this->_pagination_args['total_pages'];
		$infinite_scroll = false;
		if ( isset( $this->_pagination_args['infinite_scroll'] ) ) {
			$infinite_scroll = $this->_pagination_args['infinite_scroll'];
		}
		if ( 'top' === $which && $total_pages > 1 ) {
			$this->screen->render_screen_reader_content( 'heading_pagination' );
		}
		// phpcs:ignore
		$output               = '<span class="displaying-num">' . sprintf( _n( '%s item', '%s items', $total_items ), number_format_i18n( $total_items ) ) . '</span>';
		$current              = (int) $this->get_pagenum();
		$removable_query_args = wp_removable_query_args();
		$current_url          = $this->url;
		$current_url          = remove_query_arg( $removable_query_args, $current_url );
		$page_links           = [];
		$total_pages_before   = '<span class="paging-input">';
		$total_pages_after    = '</span></span>';
		$disable_first        = false;
		$disable_last         = false;
		$disable_prev         = false;
		$disable_next         = false;
		if ( 1 === $current ) {
			$disable_first = true;
			$disable_prev  = true;
		}
		if ( 2 === $current ) {
			$disable_first = true;
		}
		if ( $current === $total_pages ) {
			$disable_last = true;
			$disable_next = true;
		}
		if ( $current === $total_pages - 1 ) {
			$disable_last = true;
		}
		if ( $disable_first ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&laquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a class='first-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				$this->get_url( remove_query_arg( 'paged', $current_url ), true ),
				__( 'First page' ),
				'&laquo;'
			);
		}
		if ( $disable_prev ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a class='prev-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				$this->get_url( add_query_arg( 'paged', max( 1, $current - 1 ), $current_url ), true ),
				__( 'Previous page' ),
				'&lsaquo;'
			);
		}
		if ( 'bottom' === $which ) {
			$html_current_page  = $current;
			$total_pages_before = '<span class="screen-reader-text">' . __( 'Current Page' ) . '</span><span id="table-paging" class="paging-input"><span class="tablenav-paging-text">';
		} else {
			$html_current_page = sprintf(
				"%s<input class='current-page' id='current-page-selector' type='text' name='paged' value='%s' size='%d' aria-describedby='table-paging' /><span class='tablenav-paging-text'>",
				'<label for="current-page-selector" class="screen-reader-text">' . __( 'Current Page' ) . '</label>',
				$current,
				strlen( $total_pages )
			);
		}
		$html_total_pages = sprintf( "<span class='total-pages'>%s</span>", number_format_i18n( $total_pages ) );
		// phpcs:ignore
		$page_links[]     = $total_pages_before . sprintf( _x( '%1$s of %2$s', 'paging' ), $html_current_page, $html_total_pages ) . $total_pages_after;
		if ( $disable_next ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a class='next-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				$this->get_url( add_query_arg( 'paged', min( $total_pages, $current + 1 ), $current_url ), true ),
				__( 'Next page' ),
				'&rsaquo;'
			);
		}
		if ( $disable_last ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&raquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a class='last-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				$this->get_url( add_query_arg( 'paged', $total_pages, $current_url ), true ),
				__( 'Last page' ),
				'&raquo;'
			);
		}
		$pagination_links_class = 'pagination-links';
		if ( ! empty( $infinite_scroll ) ) {
			$pagination_links_class .= ' hide-if-js';
		}
		$output .= "\n<span class='$pagination_links_class'>" . join( "\n", $page_links ) . '</span>';
		if ( $total_pages ) {
			$page_class = $total_pages < 2 ? ' one-page' : '';
		} else {
			$page_class = ' no-pages';
		}
		$this->_pagination = "<div class='tablenav-pages{$page_class}'>$output</div>";
		// phpcs:ignore
		echo $this->_pagination;
	}

	/**
	 * Print column headers, accounting for hidden and sortable columns.
	 *
	 * @staticvar int $cb_counter.
	 * @param bool $with_id Whether to set the id attribute or not.
	 * @since 1.0.0
	 */
	public function print_column_headers( $with_id = true ) {
		list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();
		if ( ! empty( $columns['cb'] ) ) {
			static $cb_counter = 1;
			$columns['cb']     = '<label class="screen-reader-text" for="cb-select-all-' . $cb_counter . '">' . __( 'Select All' ) . '</label><input id="cb-select-all-' . $cb_counter . '" type="checkbox" />';
			$cb_counter++;
		}
		foreach ( $columns as $column_key => $column_display_name ) {
			$class = [ 'manage-column', "column-$column_key" ];
			if ( in_array( $column_key, $hidden, true ) ) {
				$class[] = 'hidden';
			}
			if ( 'cb' === $column_key ) {
				$class[] = 'check-column';
			} elseif ( in_array( $column_key, [ 'posts', 'comments', 'links' ], true ) ) {
				$class[] = 'num';
			}
			if ( $column_key === $primary ) {
				$class[] = 'column-primary';
			}
			if ( isset( $sortable[ $column_key ] ) ) {
				list( $orderby, $desc_first ) = $sortable[ $column_key ];
				if ( $this->orderby === $orderby ) {
					$order   = 'asc' === $this->order ? 'desc' : 'asc';
					$class[] = 'sorted';
					$class[] = $this->order;
				} else {
					$order   = $desc_first ? 'desc' : 'asc';
					$class[] = 'sortable';
					$class[] = $desc_first ? 'asc' : 'desc';
				}
				$column_display_name = '<a href="' . $this->get_url( add_query_arg( compact( 'orderby', 'order' ), $this->url ), true ) . '"><span>' . $column_display_name . '</span><span class="sorting-indicator"></span></a>';
			}
			$tag   = ( 'cb' === $column_key ) ? 'td' : 'th';
			$scope = ( 'th' === $tag ) ? 'scope="col"' : '';
			$id    = $with_id ? "id='$column_key'" : '';
			if ( ! empty( $class ) ) {
				$class = "class='" . join( ' ', $class ) . "'";
			}
			// phpcs:ignore
			echo "<$tag $scope $id $class>$column_display_name</$tag>";
		}
	}

	/**
	 * Display a warning if needed.
	 *
	 * @since    1.0.0
	 */
	public function warning() {
		$message = '';
		if ( function_exists( 'opcache_get_status' ) ) {
			$raw = opcache_get_status( false );
			if ( ! (bool) $raw['opcache_enabled'] ) {
				$message = esc_html__( 'OPcache is not enabled on this site. There\'s nothing to see here.', 'opcache-manager' );
			}
			if ( (bool) $raw['restart_pending'] ) {
				$message = esc_html__( 'A full reset is currently pending. Displayed values may be inaccurate.', 'opcache-manager' );
			}
			if ( (bool) $raw['restart_in_progress'] ) {
				$message = esc_html__( 'A full reset is currently in progress. Displayed values may be inaccurate.', 'opcache-manager' );
			}
		} else {
			$message = esc_html__( 'OPcache is not enabled on this site. There\'s nothing to see here.', 'opcache-manager' );
		}
		if ( '' !== $message ) {
			// phpcs:ignore
			echo '<div id="opcm-warning" class="notice notice-warning"><p><strong>' . $message . '</strong></p></div>';
		}
	}

	/**
	 * Get the cleaned url.
	 *
	 * @param boolean $url Optional. The url, false for current url.
	 * @param boolean $limit Optional. Has the limit to be in the url.
	 * @return string The url cleaned, ready to use.
	 * @since 1.0.0
	 */
	public function get_url( $url = false, $limit = false ) {
		global $wp;
		$url = remove_query_arg( 'limit', $url );
		if ( $limit ) {
			$url .= ( false === strpos( $url, '?' ) ? '?' : '&' ) . 'limit=' . $this->limit;
		}
		return esc_url( $url );
	}

	/**
	 * Initializes all the list properties.
	 *
	 * @since 1.0.0
	 */
	public function process_args() {
		if ( ! ( $this->nonce = filter_input( INPUT_POST, '_wpnonce' ) ) ) {
			$this->nonce = filter_input( INPUT_GET, '_wpnonce' );
		}
		$this->url   = set_url_scheme( 'http://' . filter_input( INPUT_SERVER, 'HTTP_HOST' ) . filter_input( INPUT_SERVER, 'REQUEST_URI' ) );
		$this->limit = filter_input( INPUT_GET, 'limit', FILTER_SANITIZE_NUMBER_INT );
		foreach ( [ 'top', 'bottom' ] as $which ) {
			if ( wp_verify_nonce( $this->nonce, 'bulk-opcm-tools' ) && array_key_exists( 'dolimit-' . $which, $_POST ) ) {
				$this->limit = filter_input( INPUT_POST, 'limit-' . $which, FILTER_SANITIZE_NUMBER_INT );
			}
		}
		if ( 0 === intval( $this->limit ) ) {
			$this->limit = filter_input( INPUT_POST, 'limit-top', FILTER_SANITIZE_NUMBER_INT );
		}
		if ( 0 === intval( $this->limit ) ) {
			$this->limit = 50;
		}
		$this->paged = filter_input( INPUT_GET, 'paged', FILTER_SANITIZE_NUMBER_INT );
		if ( ! $this->paged ) {
			$this->paged = filter_input( INPUT_POST, 'paged', FILTER_SANITIZE_NUMBER_INT );
			if ( ! $this->paged ) {
				$this->paged = 1;
			}
		}
		$this->order = filter_input( INPUT_GET, 'order', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( ! $this->order ) {
			$this->order = 'desc';
		}
		$this->orderby = filter_input( INPUT_GET, 'orderby', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( ! $this->orderby ) {
			$this->orderby = 'script';
		}
		foreach ( [ 'top', 'bottom' ] as $which ) {
			if ( wp_verify_nonce( $this->nonce, 'bulk-opcm-tools' ) && array_key_exists( 'dowarmup-' . $which, $_POST ) ) {
				$this->action = 'warmup';
			}
			if ( wp_verify_nonce( $this->nonce, 'bulk-opcm-tools' ) && array_key_exists( 'doinvalidate-' . $which, $_POST ) ) {
				$this->action = 'reset';
			}
		}
		if ( array_key_exists( 'quick-action', $_GET ) && wp_verify_nonce( $this->nonce, 'quick-action-opcm-tools' ) ) {
			$this->action = filter_input( INPUT_GET, 'quick-action', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		}
		if ( '' === $this->action ) {
			$action = '-1';
			if ( '-1' === $action && wp_verify_nonce( $this->nonce, 'bulk-opcm-tools' ) && array_key_exists( 'action', $_POST ) ) {
				$action = filter_input( INPUT_POST, 'action', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			}
			if ( '-1' === $action && wp_verify_nonce( $this->nonce, 'bulk-opcm-tools' ) && array_key_exists( 'action2', $_POST ) ) {
				$action = filter_input( INPUT_POST, 'action2', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			}
			if ( '-1' !== $action && wp_verify_nonce( $this->nonce, 'bulk-opcm-tools' ) && array_key_exists( 'bulk', $_POST ) ) {
				$this->bulk = filter_input( INPUT_POST, 'bulk', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_FORCE_ARRAY );
				if ( 0 < count( $this->bulk ) ) {
					$this->action = $action;
				}
			}
		}
	}

	/**
	 * Processes the selected action.
	 *
	 * @since 1.0.0
	 */
	public function process_action() {
		switch ( $this->action ) {
			case 'warmup':
				if ( Environment::is_wordpress_multisite() ) {
					// phpcs:ignore
					$message = esc_html( sprintf( __( 'Network warm-up has been initiated. %d relevant files.', 'opcache-manager' ), OPcache::warmup( false, true ) ) );
				} else {
					// phpcs:ignore
					$message = esc_html( sprintf( __( 'Site warm-up has been initiated. %d relevant files.', 'opcache-manager' ), OPcache::warmup( false, true ) ) );
				}
				$code = 0;
				break;
			case 'reset':
				OPcache::reset( false );
				$message = esc_html__( 'Site invalidation has been initiated. It may take a few seconds to complete.', 'opcache-manager' );
				$code    = 0;
				break;
			case 'invalidate':
				// phpcs:ignore
				$message = esc_html( sprintf( __( 'Invalidation done: %d file(s).', 'opcache-manager' ), OPcache::invalidate( $this->bulk, false ) ) );
				$code    = 0;
				break;
			case 'force':
				// phpcs:ignore
				$message = esc_html( sprintf( __( 'Forced invalidation done: %d file(s).', 'opcache-manager' ), OPcache::invalidate( $this->bulk, true ) ) );
				$code    = 0;
				break;
			case 'recompile':
				// phpcs:ignore
				$message = esc_html( sprintf( __( 'Recompilation done: %d file(s).', 'opcache-manager' ), OPcache::recompile( $this->bulk, true ) ) );
				$code    = 0;
				break;
			default:
				return;
		}
		if ( 0 === $code ) {
			add_settings_error( 'opcache_manager_no_error', $code, $message, 'updated' );
		} else {
			add_settings_error( 'opcache_manager_error', $code, $message, 'error' );
		}
	}
}

<?php
/**
 * OPcache Manager analytics
 *
 * Handles all analytics operations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace OPcacheManager\Plugin\Feature;

use OPcacheManager\Plugin\Feature\Schema;
use OPcacheManager\System\Blog;
use OPcacheManager\System\Cache;
use OPcacheManager\System\Date;
use OPcacheManager\System\Conversion;
use OPcacheManager\System\Role;
use OPcacheManager\System\Logger;
use OPcacheManager\System\L10n;
use OPcacheManager\System\OPcache;
use OPcacheManager\System\Timezone;
use OPcacheManager\System\UUID;
use Feather;


/**
 * Define the analytics functionality.
 *
 * Handles all analytics operations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Analytics {

	/**
	 * The start date.
	 *
	 * @since  1.0.0
	 * @var    string    $start    The start date.
	 */
	private $start = '';

	/**
	 * The end date.
	 *
	 * @since  1.0.0
	 * @var    string    $end    The end date.
	 */
	private $end = '';

	/**
	 * The period duration in days.
	 *
	 * @since  1.0.0
	 * @var    integer    $duration    The period duration in days.
	 */
	private $duration = 0;

	/**
	 * The timezone.
	 *
	 * @since  1.0.0
	 * @var    string    $timezone    The timezone.
	 */
	private $timezone = 'UTC';

	/**
	 * The main query filter.
	 *
	 * @since  1.0.0
	 * @var    array    $filter    The main query filter.
	 */
	private $filter = [];

	/**
	 * The query filter fro the previous range.
	 *
	 * @since  1.0.0
	 * @var    array    $previous    The query filter fro the previous range.
	 */
	private $previous = [];

	/**
	 * Is the start date today's date.
	 *
	 * @since  1.0.0
	 * @var    boolean    $today    Is the start date today's date.
	 */
	private $is_today = false;

	/**
	 * Colors for graphs.
	 *
	 * @since  1.0.0
	 * @var    array    $colors    The colors array.
	 */
	private $colors = [ '#73879C', '#3398DB', '#9B59B6', '#b2c326', '#BDC3C6' ];

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string  $start   The start date.
	 * @param string  $end     The end date.
	 * @param boolean $reload  Is it a reload of an already displayed analytics.
	 * @since 1.0.0
	 */
	public function __construct( $start, $end, $reload ) {
		$this->timezone = Timezone::site_get();
		$this->start    = $start;
		$this->end      = $end;
		$datetime       = new \DateTime( 'now' );
		$this->is_today = ( $this->start === $datetime->format( 'Y-m-d' ) || $this->end === $datetime->format( 'Y-m-d' ) );
		$start          = Date::get_mysql_utc_from_date( $this->start . ' 00:00:00', $this->timezone->getName() );
		$end            = Date::get_mysql_utc_from_date( $this->end . ' 23:59:59', $this->timezone->getName() );
		$this->filter[] = "timestamp>='" . $start . "' and timestamp<='" . $end . "'";
		$start          = new \DateTime( $start, $this->timezone );
		$end            = new \DateTime( $end, $this->timezone );
		$start->sub( new \DateInterval( 'PT1S' ) );
		$end->sub( new \DateInterval( 'PT1S' ) );
		$delta = $start->diff( $end, true );
		if ( $delta ) {
			$start->sub( $delta );
			$end->sub( $delta );
		}
		$this->duration   = $delta->days + 1;
		$this->previous[] = "timestamp>='" . $start->format( 'Y-m-d H:i:s' ) . "' and timestamp<='" . $end->format( 'Y-m-d H:i:s' ) . "'";
	}

	/**
	 * Query statistics table.
	 *
	 * @param   string $query   The query type.
	 * @param   mixed  $queried The query params.
	 * @return array  The result of the query, ready to encode.
	 * @since    1.0.0
	 */
	public function query( $query, $queried ) {
		switch ( $query ) {
			case 'main-chart':
				return $this->query_chart();
			case 'kpi':
				return $this->query_kpi( $queried );
			case 'events':
				return $this->query_events();
			/*case 'top-domains':
				return $this->query_top( 'domains', (int) $queried );
			case 'top-authorities':
				return $this->query_top( 'authorities', (int) $queried );
			case 'top-endpoints':
				return $this->query_top( 'endpoints', (int) $queried );
			case 'sites':
				return $this->query_list( 'sites' );
			case 'domains':
				return $this->query_list( 'domains' );
			case 'authorities':
				return $this->query_list( 'authorities' );
			case 'endpoints':
				return $this->query_list( 'endpoints' );
			case 'codes':
				return $this->query_list( 'codes' );
			case 'schemes':
				return $this->query_list( 'schemes' );
			case 'methods':
				return $this->query_list( 'methods' );
			case 'countries':
				return $this->query_list( 'countries' );
			case 'code':
				return $this->query_pie( 'code', (int) $queried );
			case 'security':
				return $this->query_pie( 'security', (int) $queried );
			case 'method':
				return $this->query_pie( 'method', (int) $queried );*/
		}
		return [];
	}

	/**
	 * Query statistics table.
	 *
	 * @param   string  $type    The type of pie.
	 * @param   integer $limit  The number to display.
	 * @return array  The result of the query, ready to encode.
	 * @since    1.0.0
	 */
	private function query_pie( $type, $limit ) {
		$extra_field = '';
		$extra       = [];
		$not         = false;
		$uuid        = UUID::generate_unique_id( 5 );
		switch ( $type ) {
			case 'code':
				$group       = 'code';
				$follow      = 'authority';
				$extra_field = 'code';
				$extra       = [ 0 ];
				$not         = true;
				break;
			case 'security':
				$group       = 'scheme';
				$follow      = 'endpoint';
				$extra_field = 'scheme';
				$extra       = [ 'http', 'https' ];
				$not         = false;
				break;
			case 'method':
				$group  = 'verb';
				$follow = 'domain';
				break;

		}
		$data  = Schema::get_grouped_list( $group, [], $this->filter, ! $this->is_today, $extra_field, $extra, $not, 'ORDER BY sum_hit DESC' );
		$total = 0;
		$other = 0;
		foreach ( $data as $key => $row ) {
			$total = $total + $row['sum_hit'];
			if ( $limit <= $key ) {
				$other = $other + $row['sum_hit'];
			}
		}
		$result = '';
		$cpt    = 0;
		$labels = [];
		$series = [];
		while ( $cpt < $limit && array_key_exists( $cpt, $data ) ) {
			if ( 0 < $total ) {
				$percent = round( 100 * $data[ $cpt ]['sum_hit'] / $total, 1 );
			} else {
				$percent = 100;
			}
			if ( 0.1 > $percent ) {
				$percent = 0.1;
			}
			$meta = strtoupper( $data[ $cpt ][ $group ] );
			if ( 'code' === $type ) {
				$meta = $data[ $cpt ][ $group ] . ' ' . Http::$http_status_codes[ (int) $data[ $cpt ][ $group ] ];
			}
			$labels[] = strtoupper( $data[ $cpt ][ $group ] );
			$series[] = [
				'meta'  => $meta,
				'value' => (float) $percent,
			];
			++$cpt;
		}
		if ( 0 < $other ) {
			if ( 0 < $total ) {
				$percent = round( 100 * $other / $total, 1 );
			} else {
				$percent = 100;
			}
			if ( 0.1 > $percent ) {
				$percent = 0.1;
			}
			$labels[] = esc_html__( 'Other', 'opcache-manager' );
			$series[] = [
				'meta'  => esc_html__( 'Other', 'opcache-manager' ),
				'value' => (float) $percent,
			];
		}
		$result  = '<div class="opcm-pie-box">';
		$result .= '<div class="opcm-pie-graph">';
		$result .= '<div class="opcm-pie-graph-handler" id="opcm-pie-' . $group . '"></div>';
		$result .= '</div>';
		$result .= '<div class="opcm-pie-legend">';
		foreach ( $labels as $key => $label ) {
			$icon    = '<img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'square', $this->colors[ $key ], $this->colors[ $key ] ) . '" />';
			$result .= '<div class="opcm-pie-legend-item">' . $icon . '&nbsp;&nbsp;' . $label . '</div>';
		}
		$result .= '';
		$result .= '</div>';
		$result .= '</div>';
		$result .= '<script>';
		$result .= 'jQuery(function ($) {';
		$result .= ' var data' . $uuid . ' = ' . wp_json_encode(
			[
				'labels' => $labels,
				'series' => $series,
			]
		) . ';';
		$result .= ' var tooltip' . $uuid . ' = Chartist.plugins.tooltip({percentage: true, appendToBody: true});';
		$result .= ' var option' . $uuid . ' = {width: 120, height: 120, showLabel: false, donut: true, donutWidth: "40%", startAngle: 270, plugins: [tooltip' . $uuid . ']};';
		$result .= ' new Chartist.Pie("#opcm-pie-' . $group . '", data' . $uuid . ', option' . $uuid . ');';
		$result .= '});';
		$result .= '</script>';
		return [ 'opcm-' . $type => $result ];
	}

	/**
	 * Query statistics table.
	 *
	 * @param   string  $type    The type of top.
	 * @param   integer $limit  The number to display.
	 * @return array  The result of the query, ready to encode.
	 * @since    1.0.0
	 */
	private function _D_query_top( $type, $limit ) {
		switch ( $type ) {
			case 'authorities':
				$group  = 'authority';
				$follow = 'authority';
				break;
			case 'endpoints':
				$group  = 'endpoint';
				$follow = 'endpoint';
				break;
			default:
				$group  = 'id';
				$follow = 'domain';
				break;

		}
		$data  = Schema::get_grouped_list( $group, [], $this->filter, ! $this->is_today, '', [], false, 'ORDER BY sum_hit DESC' );
		$total = 0;
		$other = 0;
		foreach ( $data as $key => $row ) {
			$total = $total + $row['sum_hit'];
			if ( $limit <= $key ) {
				$other = $other + $row['sum_hit'];
			}
		}
		$result = '';
		$cpt    = 0;
		while ( $cpt < $limit && array_key_exists( $cpt, $data ) ) {
			if ( 0 < $total ) {
				$percent = round( 100 * $data[ $cpt ]['sum_hit'] / $total, 1 );
			} else {
				$percent = 100;
			}
			$url = $this->get_url(
				[],
				[
					'type'   => $follow,
					'id'     => $data[ $cpt ][ $group ],
					'domain' => $data[ $cpt ]['id'],
				]
			);
			if ( 0.5 > $percent ) {
				$percent = 0.5;
			}
			$result .= '<div class="opcm-top-line">';
			$result .= '<div class="opcm-top-line-title">';
			$result .= '<img style="width:16px;vertical-align:bottom;" src="' . Favicon::get_base64( $data[ $cpt ]['id'] ) . '" />&nbsp;&nbsp;<span class="opcm-top-line-title-text"><a href="' . esc_url( $url ) . '">' . $data[ $cpt ][ $group ] . '</a></span>';
			$result .= '</div>';
			$result .= '<div class="opcm-top-line-content">';
			$result .= '<div class="opcm-bar-graph"><div class="opcm-bar-graph-value" style="width:' . $percent . '%"></div></div>';
			$result .= '<div class="opcm-bar-detail">' . Conversion::number_shorten( $data[ $cpt ]['sum_hit'], 2, false, '&nbsp;' ) . '</div>';
			$result .= '</div>';
			$result .= '</div>';
			++$cpt;
		}
		if ( 0 < $total ) {
			$percent = round( 100 * $other / $total, 1 );
		} else {
			$percent = 100;
		}
		$result .= '<div class="opcm-top-line opcm-minor-data">';
		$result .= '<div class="opcm-top-line-title">';
		$result .= '<span class="opcm-top-line-title-text">' . esc_html__( 'Other', 'opcache-manager' ) . '</span>';
		$result .= '</div>';
		$result .= '<div class="opcm-top-line-content">';
		$result .= '<div class="opcm-bar-graph"><div class="opcm-bar-graph-value" style="width:' . $percent . '%"></div></div>';
		$result .= '<div class="opcm-bar-detail">' . Conversion::number_shorten( $other, 2, false, '&nbsp;' ) . '</div>';
		$result .= '</div>';
		$result .= '</div>';
		return [ 'opcm-top-' . $type => $result ];
	}

	/**
	 * Query statistics table.
	 *
	 * @return array  The result of the query, ready to encode.
	 * @since    1.0.0
	 */
	private function query_events() {
		$data    = Schema::get_list( $this->filter, ! $this->is_today, '', [], false, 'ORDER BY timestamp ASC' );
		$result  = '<table class="opcm-table">';
		$result .= '<tr>';
		$result .= '<th>&nbsp;</th>';
		$result .= '<th>' . esc_html__( 'Timeframe', 'opcache-manager' ) . '</th>';
		$result .= '<th>' . esc_html__( 'Details', 'opcache-manager' ) . '</th>';
		$result .= '</tr>';
		$found   = false;
		foreach ( $data as $key => $row ) {
			$op      = $row['reset'];
			$name    = '';
			$time    = '';
			$details = '';
			$str     = [];
			switch ( $row['reset'] ) {
				case 'oom':
					$icon = '<img style="width:14px;vertical-align:text-bottom;" src="' . Feather\Icons::get_base64( 'cpu', 'none', '#73879C' ) . '" />';
					$name = esc_html__( 'Reset due to free memory exhaustion.', 'opcache-manager' );
					break;
				case 'hash':
					$icon = '<img style="width:14px;vertical-align:text-bottom;" src="' . Feather\Icons::get_base64( 'database', 'none', '#73879C' ) . '" />';
					$name = esc_html__( 'Reset due to excessive keys saturation.', 'opcache-manager' );
					break;
				case 'manual':
					$icon = '<img style="width:14px;vertical-align:text-bottom;" src="' . Feather\Icons::get_base64( 'settings', 'none', '#73879C' ) . '" />';
					$name = esc_html__( 'Programmatic or manual reset.', 'opcache-manager' );
			}
			switch ( $row['status'] ) {
				case 'reset_warmup':
					$icon = '<img style="width:14px;vertical-align:text-bottom;" src="' . Feather\Icons::get_base64( 'clock', 'none', '#73879C' ) . '" />';
					$name = esc_html__( 'Programmatic reset and warm-up.', 'opcache-manager' );
					$op   = $row['status'];
					break;
				case 'warmup':
					$icon = '<img style="width:14px;vertical-align:text-bottom;" src="' . Feather\Icons::get_base64( 'mouse-pointer', 'none', '#73879C' ) . '" />';
					$name = esc_html__( 'Manual warm-up.', 'opcache-manager' );
					$op   = $row['status'];
					break;
				case 'cache_full':
					if ( array_key_exists( $key - 1, $data ) && 'cache_full' !== $data[ $key - 1 ]['status'] ) {
						$icon = '<img style="width:14px;vertical-align:text-bottom;" src="' . Feather\Icons::get_base64( 'alert-triangle', 'none', '#73879C' ) . '" />';
						$name = esc_html__( 'Cache is full.', 'opcache-manager' );
						$op   = $row['status'];
					}
					break;
			}
			switch ( $row['status'] ) {
				case 'disabled':
					if ( array_key_exists( $key - 1, $data ) && 'disabled' !== $data[ $key - 1 ]['status'] ) {
						$icon = '<img style="width:14px;vertical-align:text-bottom;" src="' . Feather\Icons::get_base64( 'power', 'none', '#73879C' ) . '" />';
						$name = esc_html__( 'OPcache disabled.', 'opcache-manager' );
						$op   = $row['status'];
					}
					break;
				default:
					if ( array_key_exists( $key - 1, $data ) && 'disabled' === $data[ $key - 1 ]['status'] ) {
						$icon = '<img style="width:14px;vertical-align:text-bottom;" src="' . Feather\Icons::get_base64( 'power', 'none', '#73879C' ) . '" />';
						$name = esc_html__( 'OPcache enabled.', 'opcache-manager' );
						$op   = 'enabled';
					}
			}
			if ( array_key_exists( $key - 1, $data ) && 'recycle_in_progress' === $data[ $key - 1 ]['status'] ) {
				$op = 'recycle_in_progress';
			}
			$conf = [];
			if ( array_key_exists( $key - 1, $data ) ) {
				foreach ( [ 'mem', 'key', 'buf' ] as $idx ) {
					if ( $row[ $idx . '_total' ] !== $data[ $key - 1 ][ $idx . '_total' ] ) {
						$conf[] = $idx;
					}
				}
			}
			if ( 0 < count( $conf ) && 'disabled' !== $op && 'enabled' !== $op && 'recycle_in_progress' !== $op ) {
				foreach ( $conf as $idx ) {
					$val = $row[ $idx . '_total' ] - $data[ $key - 1 ][ $idx . '_total' ];
					switch ( $idx ) {
						case 'mem':
							if ( 0 < $val ) {
								$str[] = sprintf( esc_html__( 'Total memory size increased by %s.', 'opcache-manager' ), Conversion::data_shorten( abs( $val ), 0, false, '&nbsp;' ) );
							} elseif ( 0 > $val ) {
								$str[] = sprintf( esc_html__( 'Total memory size decreased by %s.', 'opcache-manager' ), Conversion::data_shorten( abs( $val ), 0, false, '&nbsp;' ) );
							}
							$op = 'settings';
							break;
						case 'buf':
							if ( 0 < $val ) {
								$str[] = sprintf( esc_html__( 'Total buffer size increased by %s.', 'opcache-manager' ), Conversion::data_shorten( abs( $val ), 0, false, '&nbsp;' ) );
							} elseif ( 0 > $val ) {
								$str[] = sprintf( esc_html__( 'Total buffer size decreased by %s.', 'opcache-manager' ), Conversion::data_shorten( abs( $val ), 0, false, '&nbsp;' ) );
							}
							$op = 'settings';
							break;
						case 'key':
							if ( 0 < $val ) {
								$str[] = sprintf( esc_html__( 'Maximum keys slots increased by %s.', 'opcache-manager' ), Conversion::number_shorten( abs( $val ), 0, false, '' ) );
							} elseif ( 0 > $val ) {
								$str[] = sprintf( esc_html__( 'Maximum keys slots decreased by %s.', 'opcache-manager' ), Conversion::number_shorten( abs( $val ), 0, false, '' ) );
							}
							$op = 'settings';
							break;
					}
				}
			}
			if ( 'none' === $op || '' === $name ) {
				continue;
			}
			$found     = true;
			$timestamp = new \DateTime( $row['timestamp'] );
			$timestamp->setTimezone( $this->timezone );
			$time = $timestamp->format( 'H:i' );
			$timestamp->sub( new \DateInterval( 'PT5M' ) );
			$time = $timestamp->format( 'Y-m-d H:i' ) . ' ⇥ ' . $time;
			if ( 0 < count( $str ) ) {
				$sicon    = '<img style="width:14px;vertical-align:text-bottom;" src="' . Feather\Icons::get_base64( 'tool', 'none', '#73879C' ) . '" />';
				$sname    = esc_html__( 'Settings changed.', 'opcache-manager' );
				$sdetails = implode( ' ', $str );
				$row_str  = '<tr>';
				$row_str .= '<td data-th="">' . $sicon . '&nbsp;&nbsp;<span class="opcm-table-text">' . $sname . '</span></td>';
				$row_str .= '<td data-th="' . esc_html__( 'Timeframe', 'opcache-manager' ) . '">' . $time . '</td>';
				$row_str .= '<td data-th="' . esc_html__( 'Details', 'opcache-manager' ) . '">' . $sdetails . '</td>';
				$row_str .= '</tr>';
				$result  .= $row_str;
			}
			$str = [];
			switch ( $op ) {
				case 'oom':
				case 'hash':
				case 'manual':
				case 'reset_warmup':
					if ( array_key_exists( $key - 1, $data ) ) {
						$val = ( $row['mem_total'] - $row['mem_used'] - $row['mem_wasted'] ) - ( $data[ $key - 1 ]['mem_total'] - $data[ $key - 1 ]['mem_used'] - $data[ $key - 1 ]['mem_wasted'] );
						if ( 0 < $val ) {
							$str[] = sprintf( esc_html__( 'Free memory size increased by %s.', 'opcache-manager' ), Conversion::data_shorten( abs( $val ), 0, false, '&nbsp;' ) );
						} elseif ( 0 > $val ) {
							$str[] = sprintf( esc_html__( 'Free memory size decreased by %s.', 'opcache-manager' ), Conversion::data_shorten( abs( $val ), 0, false, '&nbsp;' ) );
						}
						$val = ( $row['buf_total'] - $row['buf_used'] ) - ( $data[ $key - 1 ]['buf_total'] - $data[ $key - 1 ]['buf_used'] );
						if ( 0 < $val ) {
							$str[] = sprintf( esc_html__( 'Free buffer size increased by %s.', 'opcache-manager' ), Conversion::data_shorten( abs( $val ), 0, false, '&nbsp;' ) );
						} elseif ( 0 > $val ) {
							$str[] = sprintf( esc_html__( 'Free buffer size decreased by %s.', 'opcache-manager' ), Conversion::data_shorten( abs( $val ), 0, false, '&nbsp;' ) );
						}
						$val = ( $row['key_total'] - $row['key_used'] ) - ( $data[ $key - 1 ]['key_total'] - $data[ $key - 1 ]['key_used'] );
						if ( 0 < $val ) {
							$str[] = sprintf( esc_html__( 'Free keys slots increased by %s.', 'opcache-manager' ), Conversion::number_shorten( abs( $val ), 0, false, '' ) );
						} elseif ( 0 > $val ) {
							$str[] = sprintf( esc_html__( 'Free keys slots decreased by %s.', 'opcache-manager' ), Conversion::number_shorten( abs( $val ), 0, false, '' ) );
						}
					}
					$details = implode( ' ', $str );
					break;
				case 'warmup':
				case 'disabled':
				case 'enabled':
					break;
				case 'cache_full':
					$details = sprintf( esc_html__( 'Current wasted memory: %s.', 'opcache-manager' ), Conversion::data_shorten( $row['mem_wasted'], 0, false, '&nbsp;' ) );
					break;
			}
			if ( '' === $details ) {
				$details = '-';
			}
			$row_str  = '<tr>';
			$row_str .= '<td data-th="">' . $icon . '&nbsp;&nbsp;<span class="opcm-table-text">' . $name . '</span></td>';
			$row_str .= '<td data-th="' . esc_html__( 'Timeframe', 'opcache-manager' ) . '">' . $time . '</td>';
			$row_str .= '<td data-th="' . esc_html__( 'Details', 'opcache-manager' ) . '">' . $details . '</td>';
			$row_str .= '</tr>';
			$result  .= $row_str;
		}
		if ( ! $found ) {
			$row_str  = '<tr>';
			$row_str .= '<td data-th=""><em>' . esc_html__( 'No status events in the selected time range.', 'opcache-manager' ) . '</em></span></td>';
			$row_str .= '<td data-th="' . esc_html__( 'Timeframe', 'opcache-manager' ) . '">&nbsp;</td>';
			$row_str .= '<td data-th="' . esc_html__( 'Details', 'opcache-manager' ) . '">&nbsp;</td>';
			$row_str .= '</tr>';
			$result  .= $row_str;
		}
		$result .= '</table>';
		return [ 'opcm-events' => $result ];
	}

	/**
	 * Query statistics table.
	 *
	 * @return array The result of the query, ready to encode.
	 * @since    1.0.0
	 */
	private function query_chart() {
		$uuid   = UUID::generate_unique_id( 5 );
		$query  = Schema::get_time_series( $this->filter, ! $this->is_today, '', [], false );
		$data   = [];
		$series = [];
		$labels = [];
		$items  = [ 'mem_total', 'mem_used', 'mem_wasted', 'key_used', 'buf_total', 'buf_used', 'hit', 'miss', 'strings', 'scripts' ];
		$maxhit = 0;
		// Data normalization.
		if ( 1 === $this->duration ) {
			foreach ( $query as $timestamp => $row ) {
				$datetime    = new \DateTime( $timestamp, new \DateTimeZone( 'UTC' ) );
				$offset      = $this->timezone->getOffset( $datetime );
				$ts          = $datetime->getTimestamp() + $offset;
				$data[ $ts ] = $row;
			}
			$end       = new \DateTime( Date::get_mysql_utc_from_date( $this->end . ' 23:59:59', $this->timezone->getName() ), $this->timezone );
			$end       = $end->getTimestamp();
			$datetime  = new \DateTime( $timestamp, new \DateTimeZone( 'UTC' ) );
			$offset    = $this->timezone->getOffset( $datetime );
			$timestamp = $datetime->getTimestamp() + 300;
			while ( $timestamp <= $end + $offset ) {
				$datetime = new \DateTime( date( 'Y-m-d H:i:s', $timestamp ), new \DateTimeZone( 'UTC' ) );
				$offset   = $this->timezone->getOffset( $datetime );
				$ts       = $datetime->getTimestamp() + $offset;
				$record   = [];
				foreach ( $items as $item ) {
					$record[ $item ] = 0;
				}
				$data[ $ts ] = $record;
				$timestamp   = $timestamp + 300;
			}
		} else {
			$buffer = [];
			foreach ( $query as $timestamp => $row ) {
				$datetime = new \DateTime( $timestamp, new \DateTimeZone( 'UTC' ) );
				$datetime->setTimezone( $this->timezone );
				$buffer[ $datetime->format( 'Y-m-d' ) ][] = $row;
			}
			foreach ( $buffer as $timestamp => $rows ) {
				$record = [];
				foreach ( $items as $item ) {
					$record[ $item ] = 0;
				}
				foreach ( $rows as $row ) {
					foreach ( $items as $item ) {
						$record[ $item ] = $record[ $item ] + $row[ $item ];
					}
				}
				$cpt = count( $rows );
				if ( 0 < $cpt ) {
					foreach ( $items as $item ) {
						$record[ $item ] = (int) round( $record[ $item ] / $cpt, 0 );
					}
				}
				$data[ strtotime( $timestamp . ' 12:00:00' ) ] = $record;
			}
		}
		// Series computation.
		foreach ( $data as $timestamp => $datum ) {
			$ts = 'new Date(' . (string) $timestamp . '000)';
			// Hit ratio.
			$val = 'null';
			if ( 0 !== (int) $datum['hit'] + (int) $datum['miss'] ) {
				$val = round (100 * $datum['hit'] / ( $datum['hit'] + $datum['miss'] ), 3 ) ;
			}
			$series['ratio'][] = [
				'x' => $ts,
				'y' => $val,
			];
			// Hit distribution.
			$val = 'null';
			$val = (int) $datum['hit'] ;
			$series['hit'][] = [
				'x' => $ts,
				'y' => $val,
			];
			if ( $maxhit < $val ) {
				$maxhit = $val;
			}
			// Miss distribution.
			$val = (int) $datum['miss'] ;
			$series['miss'][] = [
				'x' => $ts,
				'y' => $val,
			];
			if ( $maxhit < $val ) {
				$maxhit = $val;
			}





			
			// Memory.
			$factor                = 1024 * 1024;
			$series['memory'][0][] = round( $datum['mem_used'] / $factor, 2 );
			$series['memory'][1][] = round( ( $datum['mem_total'] - $datum['mem_used'] - $datum['mem_wasted'] ) / $factor, 2 );
			$series['memory'][2][] = round( $datum['mem_wasted'] / $factor, 2 );



			// Labels.
			$control = ( $timestamp % 86400 ) % ( 3 * HOUR_IN_SECONDS );
			if ( 300 > $control ) {
				$hour = (string) (int) floor( ( $timestamp % 86400 ) / ( HOUR_IN_SECONDS ) );
				if ( 1 === strlen( $hour ) ) {
					$hour = '0' . $hour;
				}
				$labels[] = $hour . ':00';
			} else {
				$labels[] = 'null';
			}
		}



		$json_memory  = wp_json_encode(
			[
				'labels' => $labels,
				'series' => $series['memory'],
			]
		);
		$json_memory = str_replace( '"null"', 'null', $json_memory );

		if ( 1 < $this->duration ) {
			$shift = 86400;
		} else {
			$shift = 0;
		}
		$datetime = new \DateTime( $this->start . ' 00:00:00', $this->timezone );
		$offset   = $this->timezone->getOffset( $datetime );
		$datetime = $datetime->getTimestamp() + $offset;
		$before   = [
			'x' => 'new Date(' . (string) ( $datetime - $shift ) . '000)',
			'y' => 'null',
		];
		$datetime = new \DateTime( $this->end . ' 23:59:59', $this->timezone );
		$offset   = $this->timezone->getOffset( $datetime );
		$datetime = $datetime->getTimestamp() + $offset;
		$after    = [
			'x' => 'new Date(' . (string) ( $datetime + $shift ) . '000)',
			'y' => 'null',
		];
		// Hit ratio.
		array_unshift( $series['ratio'], $before );
		$series['ratio'][] = $after;
		$json_ratio        = wp_json_encode(
			[
				'series' => [
					[
						'name' => esc_html__( 'Hit Ratio', 'opcache-manager' ),
						'data' => $series['ratio'],
					],
				],
			]
		);
		$json_ratio        = str_replace( '"x":"new', '"x":new', $json_ratio );
		$json_ratio        = str_replace( ')","y"', '),"y"', $json_ratio );
		$json_ratio        = str_replace( '"null"', 'null', $json_ratio );
		// Hit & miss distribution.
		array_unshift( $series['hit'], $before );
		$series['hit'][] = $after;
		array_unshift( $series['miss'], $before );
		$series['miss'][] = $after;
		$json_hit        = wp_json_encode(
			[
				'series' => [
					[
						'name' => esc_html__( 'Hit Count', 'traffic' ),
						'data' => $series['hit'],
					],
					[
						'name' => esc_html__( 'Miss Count', 'traffic' ),
						'data' => $series['miss'],
					],
				],
			]
		);
		$json_hit        = str_replace( '"x":"new', '"x":new', $json_hit );
		$json_hit        = str_replace( ')","y"', '),"y"', $json_hit );
		$json_hit        = str_replace( '"null"', 'null', $json_hit );


		// Rendering.
		if ( 4 < $this->duration ) {
			if ( 1 === $this->duration % 2 ) {
				$divisor = 6;
			} else {
				$divisor = 5;
			}
		} else {
			$divisor = $this->duration + 1;
		}
		$result  = '<div class="opcm-multichart-handler">';
		$result .= '<div class="opcm-multichart-item active" id="opcm-chart-ratio">';
		$result .= '</div>';
		$result .= '<script>';
		$result .= 'jQuery(function ($) {';
		$result .= ' var ratio_data' . $uuid . ' = ' . $json_ratio . ';';
		$result .= ' var ratio_tooltip' . $uuid . ' = Chartist.plugins.tooltip({percentage: false, appendToBody: true});';
		$result .= ' var ratio_option' . $uuid . ' = {';
		$result .= '  height: 300,';
		$result .= '  fullWidth: true,';
		$result .= '  showArea: true,';
		$result .= '  showLine: true,';
		$result .= '  showPoint: false,';
		$result .= '  plugins: [ratio_tooltip' . $uuid . '],';
		if ( 1 < $this->duration ) {
			$result .= '  axisX: {labelOffset: {x: 3,y: 0},scaleMinSpace: 100, type: Chartist.FixedScaleAxis, divisor:' . $divisor . ', labelInterpolationFnc: function (value) {return moment(value).format("MMM DD");}},';
		} else {
			$result .= '  axisX: {labelOffset: {x: 3,y: 0},scaleMinSpace: 100, type: Chartist.FixedScaleAxis, divisor:8, labelInterpolationFnc: function (value) {return moment(value).format("HH:00");}},';
		}
		$result .= '  axisY: {type: Chartist.AutoScaleAxis, labelInterpolationFnc: function (value) {return value.toString() + " %";}},';
		$result .= ' };';
		$result .= ' new Chartist.Line("#opcm-chart-ratio", ratio_data' . $uuid . ', ratio_option' . $uuid . ');';
		$result .= '});';
		$result .= '</script>';
		$result .= '<div class="opcm-multichart-item" id="opcm-chart-hit">';
		$result .= '</div>';
		$result .= '<script>';
		$result .= 'jQuery(function ($) {';
		$result .= ' var hit_data' . $uuid . ' = ' . $json_hit . ';';
		$result .= ' var hit_tooltip' . $uuid . ' = Chartist.plugins.tooltip({percentage: false, appendToBody: true});';
		$result .= ' var hit_option' . $uuid . ' = {';
		$result .= '  height: 300,';
		$result .= '  fullWidth: true,';
		$result .= '  showArea: true,';
		$result .= '  showLine: true,';
		$result .= '  showPoint: false,';
		$result .= '  plugins: [hit_tooltip' . $uuid . '],';
		if ( 1 < $this->duration ) {
			$result .= '  axisX: {labelOffset: {x: 3,y: 0},scaleMinSpace: 100, type: Chartist.FixedScaleAxis, divisor:' . $divisor . ', labelInterpolationFnc: function (value) {return moment(value).format("MMM DD");}},';
		} else {
			$result .= '  axisX: {labelOffset: {x: 3,y: 0},scaleMinSpace: 100, type: Chartist.FixedScaleAxis, divisor:8, labelInterpolationFnc: function (value) {return moment(value).format("HH:00");}},';
		}
		if ( $maxhit < 1000 ) {
			$result .= '  axisY: {type: Chartist.AutoScaleAxis, labelInterpolationFnc: function (value) {return value.toString();}},';
		} elseif ( $maxhit < 1000000 ) {
			$result .= '  axisY: {type: Chartist.AutoScaleAxis, labelInterpolationFnc: function (value) {value = value / 1000; return value.toString() + " K";}},';
		} else {
			$result .= '  axisY: {type: Chartist.AutoScaleAxis, labelInterpolationFnc: function (value) {value = value / 1000000; return value.toString() + " M";}},';
		}
		$result .= ' };';
		$result .= ' new Chartist.Line("#opcm-chart-hit", hit_data' . $uuid . ', hit_option' . $uuid . ');';
		$result .= '});';
		$result .= '</script>';
		$result .= '<div class="opcm-multichart-item" id="opcm-chart-memory">';
		$result .= '</div>';
		$result .= '<script>';
		$result .= 'jQuery(function ($) {';
		$result .= ' var memory_data' . $uuid . ' = ' . $json_memory . ';';
		$result .= ' var memory_tooltip' . $uuid . ' = Chartist.plugins.tooltip({percentage: false, appendToBody: true});';
		$result .= ' var memory_option' . $uuid . ' = {';
		$result .= '  height: 300,';
		$result .= '  stackBars: true,';
		$result .= '  stackMode: "accumulate",';
		$result .= '  seriesBarDistance: 1,';
		$result .= '  plugins: [memory_tooltip' . $uuid . '],';
		$result .= '  axisX: {showGrid: true, labelOffset: {x: 18,y: 0}},';
		$result .= '  axisY: {showGrid: true, labelInterpolationFnc: function (value) {return value.toString() + " ' . esc_html_x( 'MB', 'Abbreviation - Stands for "megabytes".', 'opcache-manager' ) . '";}},';
		$result .= ' };';
		$result .= ' new Chartist.Bar("#opcm-chart-memory", memory_data' . $uuid . ', memory_option' . $uuid . ');';
		$result .= '});';
		$result .= '</script>';
		$result .= '<div class="opcm-multichart-item" id="opcm-chart-data">';
		$result .= '</div>';
		return [ 'opcm-main-chart' => $result ];
	}

	/**
	 * Query statistics table.
	 *
	 * @param   mixed $queried The query params.
	 * @return array  The result of the query, ready to encode.
	 * @since    1.0.0
	 */
	private function query_kpi( $queried ) {
		$result = [];
		if ( 'ratio' === $queried || 'memory' === $queried || 'key' === $queried || 'buffer' === $queried || 'uptime' === $queried ) {
			$data        = Schema::get_std_kpi( $this->filter, ! $this->is_today );
			$pdata       = Schema::get_std_kpi( $this->previous );
			$base_value  = 0.0;
			$pbase_value = 0.0;
			$data_value  = 0.0;
			$pdata_value = 0.0;
			$current     = 0.0;
			$previous    = 0.0;
			if ( 'uptime' === $queried ) {
				$disabled_data  = Schema::get_std_kpi( $this->filter, ! $this->is_today, 'status', ['disabled'] );
				$disabled_pdata = Schema::get_std_kpi( $this->previous, true,  'status', ['disabled'] );
				if ( is_array( $data ) && array_key_exists( 'records', $data ) && is_array( $disabled_data ) && array_key_exists( 'records', $disabled_data ) ) {
					if ( empty( $data['records'] ) ) {
						$data['records'] = 0;
					}
					if ( ! is_array( $disabled_data ) || ! array_key_exists( 'records', $disabled_data ) ) {
						$disabled_data['records'] = 0;
					}
					$base_value = (float) $data['records'] + $disabled_data['records'];
					$data_value = (float) $data['records'];
				}
				if ( is_array( $pdata ) && array_key_exists( 'records', $pdata ) && is_array( $disabled_pdata ) && array_key_exists( 'records', $disabled_pdata ) ) {
					if ( empty( $pdata['records'] ) ) {
						$pdata['records'] = 0;
					}
					if ( ! is_array( $disabled_pdata ) || ! array_key_exists( 'records', $disabled_pdata ) ) {
						$disabled_pdata['records'] = 0;
					}
					$pbase_value = (float) $pdata['records'] + $disabled_pdata['records'];
					$pdata_value = (float) $pdata['records'];
				}
			}
			if ( 'ratio' === $queried ) {
				if ( is_array( $data ) && array_key_exists( 'avg_hit', $data ) && ! empty( $data['avg_hit'] ) && array_key_exists( 'avg_miss', $data ) && ! empty( $data['avg_miss'] ) ) {
					$base_value = (float) $data['avg_hit'] + (float) $data['avg_miss'];
					$data_value = (float) $data['avg_hit'];
				}
				if ( is_array( $pdata ) && array_key_exists( 'avg_hit', $pdata ) && ! empty( $pdata['avg_hit'] ) && array_key_exists( 'avg_miss', $pdata ) && ! empty( $pdata['avg_miss'] ) ) {
					$pbase_value = (float) $pdata['avg_hit'] + (float) $pdata['avg_miss'];
					$pdata_value = (float) $pdata['avg_hit'];
				}
			}
			if ( 'key' === $queried ) {
				if ( is_array( $data ) && array_key_exists( 'avg_key_used', $data ) && ! empty( $data['avg_key_used'] ) && array_key_exists( 'avg_key_total', $data ) && ! empty( $data['avg_key_total'] ) ) {
					$base_value = (float) $data['avg_key_total'];
					$data_value = (float) $data['avg_key_used'];
				}
				if ( is_array( $pdata ) && array_key_exists( 'avg_key_used', $pdata ) && ! empty( $pdata['avg_key_used'] ) && array_key_exists( 'avg_key_total', $pdata ) && ! empty( $pdata['avg_key_total'] ) ) {
					$pbase_value = (float) $pdata['avg_key_total'];
					$pdata_value = (float) $pdata['avg_key_used'];
				}
			}
			if ( 'buffer' === $queried ) {
				if ( is_array( $data ) && array_key_exists( 'avg_buf_used', $data ) && ! empty( $data['avg_buf_used'] ) && array_key_exists( 'avg_buf_total', $data ) && ! empty( $data['avg_buf_total'] ) ) {
					$base_value = (float) $data['avg_buf_total'];
					$data_value = (float) $data['avg_buf_used'];
				}
				if ( is_array( $pdata ) && array_key_exists( 'avg_buf_used', $pdata ) && ! empty( $pdata['avg_buf_used'] ) && array_key_exists( 'avg_buf_total', $pdata ) && ! empty( $pdata['avg_buf_total'] ) ) {
					$pbase_value = (float) $pdata['avg_buf_total'];
					$pdata_value = (float) $pdata['avg_buf_used'];
				}
			}
			if ( 'memory' === $queried ) {
				if ( is_array( $data ) && array_key_exists( 'avg_mem_total', $data ) && ! empty( $data['avg_mem_total'] ) && array_key_exists( 'avg_mem_used', $data ) && ! empty( $data['avg_mem_used'] ) && array_key_exists( 'avg_mem_wasted', $data ) && ! empty( $data['avg_mem_wasted'] ) ) {
					$base_value = (float) $data['avg_mem_total'];
					$data_value = (float) $data['avg_mem_total'] - (float) $data['avg_mem_used'] - (float) $data['avg_mem_wasted'];
				}
				if ( is_array( $pdata ) && array_key_exists( 'avg_mem_total', $pdata ) && ! empty( $pdata['avg_mem_total'] ) && array_key_exists( 'avg_mem_used', $pdata ) && ! empty( $pdata['avg_mem_used'] ) && array_key_exists( 'avg_mem_wasted', $pdata ) && ! empty( $pdata['avg_mem_wasted'] ) ) {
					$pbase_value = (float) $pdata['avg_mem_total'];
					$pdata_value = (float) $pdata['avg_mem_total'] - (float) $pdata['avg_mem_used'] - (float) $pdata['avg_mem_wasted'];
				}
			}
			if ( 0.0 !== $base_value && 0.0 !== $data_value ) {
				$current                          = 100 * $data_value / $base_value;
				$result[ 'kpi-main-' . $queried ] = round( $current, 1 ) . '&nbsp;%';
			} else {
				if ( 0.0 !== $data_value ) {
					$result[ 'kpi-main-' . $queried ] = '100&nbsp;%';
				} elseif ( 0.0 !== $base_value ) {
					$result[ 'kpi-main-' . $queried ] = '0&nbsp;%';
				} else {
					$result[ 'kpi-main-' . $queried ] = '-';
				}
			}
			if ( 0.0 !== $pbase_value && 0.0 !== $pdata_value ) {
				$previous = 100 * $pdata_value / $pbase_value;
			} else {
				if ( 0.0 !== $pdata_value ) {
					$previous = 100.0;
				}
			}
			if ( 0.0 !== $current && 0.0 !== $previous ) {
				$percent = round( 100 * ( $current - $previous ) / $previous, 1 );
				if ( 0.1 > abs( $percent ) ) {
					$percent = 0;
				}
				$result[ 'kpi-index-' . $queried ] = '<span style="color:' . ( 0 <= $percent ? '#18BB9C' : '#E74C3C' ) . ';">' . ( 0 < $percent ? '+' : '' ) . $percent . '&nbsp;%</span>';
			} elseif ( 0.0 === $previous && 0.0 !== $current ) {
				$result[ 'kpi-index-' . $queried ] = '<span style="color:#18BB9C;">+∞</span>';
			} elseif ( 0.0 !== $previous && 100 !== $previous && 0.0 === $current ) {
				$result[ 'kpi-index-' . $queried ] = '<span style="color:#E74C3C;">-∞</span>';
			}
			switch ( $queried ) {
				case 'ratio':
					if ( is_array( $data ) && array_key_exists( 'sum_hit', $data ) ) {
						$result[ 'kpi-bottom-' . $queried ] = '<span class="opcm-kpi-large-bottom-text">' . sprintf( esc_html__( '%s hits', 'opcache-manager' ), Conversion::number_shorten( $data['sum_hit'], 2, false, '&nbsp;' ) ) . '</span>';
					}
					break;
				case 'memory':
					$result[ 'kpi-bottom-' . $queried ] = '<span class="opcm-kpi-large-bottom-text">' . sprintf( esc_html__( 'total memory: %s', 'opcache-manager' ), Conversion::data_shorten( $base_value, 0, false, '&nbsp;' ) ) . '</span>';
					break;
				case 'buffer':
					$result[ 'kpi-bottom-' . $queried ] = '<span class="opcm-kpi-large-bottom-text">' . sprintf( esc_html__( 'buffer size: %s', 'opcache-manager' ), Conversion::data_shorten( $base_value, 0, false, '&nbsp;' ) ) . '</span>';
					break;
				case 'key':
					$result[ 'kpi-bottom-' . $queried ] = '<span class="opcm-kpi-large-bottom-text">' . sprintf( esc_html__( '%s keys (avg.)', 'opcache-manager' ), (int) round( $data_value, 0 ) ) . '</span>';
					break;
				case 'uptime':
					if ( 0.0 !== $base_value ) {
						$duration = implode( ', ', Date::get_age_array_from_seconds( $this->duration * DAY_IN_SECONDS * ( $data_value / $base_value ), true, true ) );
						if ( '' === $duration ) {
							$duration = esc_html__( 'no availability', 'opcache-manager' );
						} else {
							$duration = sprintf( esc_html__( 'available %s', 'opcache-manager' ), $duration );
						}
						$result[ 'kpi-bottom-' . $queried ] = '<span class="opcm-kpi-large-bottom-text">' . $duration . '</span>';
					}
					break;
			}
		}
		if ( 'script' === $queried ) {
			$data     = Schema::get_std_kpi( $this->filter, ! $this->is_today );
			$pdata    = Schema::get_std_kpi( $this->previous );
			$current  = 0.0;
			$previous = 0.0;
			if ( is_array( $data ) && array_key_exists( 'avg_scripts', $data ) && ! empty( $data['avg_scripts'] ) ) {
				$current = (float) $data['avg_scripts'];
			}
			if ( is_array( $pdata ) && array_key_exists( 'avg_scripts', $pdata ) && ! empty( $pdata['avg_scripts'] ) ) {
				$previous = (float) $pdata['avg_scripts'];
			}
			$result[ 'kpi-main-' . $queried ] = (int) round( $current, 0 );
			if ( 0.0 !== $current && 0.0 !== $previous ) {
				$percent = round( 100 * ( $current - $previous ) / $previous, 1 );
				if ( 0.1 > abs( $percent ) ) {
					$percent = 0;
				}
				$result[ 'kpi-index-' . $queried ] = '<span style="color:' . ( 0 <= $percent ? '#18BB9C' : '#E74C3C' ) . ';">' . ( 0 < $percent ? '+' : '' ) . $percent . '&nbsp;%</span>';
			} elseif ( 0.0 === $previous && 0.0 !== $current ) {
				$result[ 'kpi-index-' . $queried ] = '<span style="color:#18BB9C;">+∞</span>';
			} elseif ( 0.0 !== $previous && 100 !== $previous && 0.0 === $current ) {
				$result[ 'kpi-index-' . $queried ] = '<span style="color:#E74C3C;">-∞</span>';
			}
			if ( is_array( $data ) && array_key_exists( 'min_scripts', $data ) && array_key_exists( 'max_scripts', $data ) ) {
				if ( empty( $data['min_scripts'] ) ) {
					$data['min_scripts'] = 0;
				}
				if ( empty( $data['max_scripts'] ) ) {
					$data['max_scripts'] = 0;
				}
				$result[ 'kpi-bottom-' . $queried ] = '<span class="opcm-kpi-large-bottom-text">' . (int) round( $data['min_scripts'], 0 ) . '&nbsp;<img style="width:12px;vertical-align:middle;" src="' . Feather\Icons::get_base64( 'arrow-right', 'none', '#73879C' ) . '" />&nbsp;' . (int) round( $data['max_scripts'], 0 ) . '&nbsp;</span>';
			}
		}
		return $result;
	}

	/**
	 * Get the title bar.
	 *
	 * @return string  The bar ready to print.
	 * @since    1.0.0
	 */
	public function get_title_bar() {
		$result  = '<div class="opcm-box opcm-box-full-line">';
		$result .= '<span class="opcm-title">' . esc_html__( 'OPcache Analytics', 'opcache-manager' ) . '</span>';
		$result .= '<span class="opcm-subtitle">' . OPcache::name() . '</span>';
		$result .= '<span class="opcm-datepicker">' . $this->get_date_box() . '</span>';
		$result .= '</div>';
		return $result;
	}

	/**
	 * Get the KPI bar.
	 *
	 * @return string  The bar ready to print.
	 * @since    1.0.0
	 */
	public function get_kpi_bar() {
		$result  = '<div class="opcm-box opcm-box-full-line">';
		$result .= '<div class="opcm-kpi-bar">';
		$result .= '<div class="opcm-kpi-large">' . $this->get_large_kpi( 'ratio' ) . '</div>';
		$result .= '<div class="opcm-kpi-large">' . $this->get_large_kpi( 'memory' ) . '</div>';
		$result .= '<div class="opcm-kpi-large">' . $this->get_large_kpi( 'script' ) . '</div>';
		$result .= '<div class="opcm-kpi-large">' . $this->get_large_kpi( 'key' ) . '</div>';
		$result .= '<div class="opcm-kpi-large">' . $this->get_large_kpi( 'buffer' ) . '</div>';
		$result .= '<div class="opcm-kpi-large">' . $this->get_large_kpi( 'uptime' ) . '</div>';
		$result .= '</div>';
		$result .= '</div>';
		return $result;
	}

	/**
	 * Get the main chart.
	 *
	 * @return string  The main chart ready to print.
	 * @since    1.0.0
	 */
	public function get_main_chart() {
		$help_ratio  = esc_html__( 'Hit ratio variation.', 'opcache-manager' );
		$help_hit    = esc_html__( 'Hit and miss distribution.', 'opcache-manager' );
		$help_memory = esc_html__( 'Memory distribution.', 'opcache-manager' );
		$help_uptime = esc_html__( 'Uptime distribution.', 'opcache-manager' );
		$detail      = '<span class="opcm-chart-button not-ready left" id="opcm-chart-button-ratio" data-position="left" data-tooltip="' . $help_ratio . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'award', 'none', '#73879C' ) . '" /></span>';
		$detail     .= '&nbsp;&nbsp;&nbsp;<span class="opcm-chart-button not-ready left" id="opcm-chart-button-hit" data-position="left" data-tooltip="' . $help_hit . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'hash', 'none', '#73879C' ) . '" /></span>';
		$detail     .= '&nbsp;&nbsp;&nbsp;<span class="opcm-chart-button not-ready left" id="opcm-chart-button-memory" data-position="left" data-tooltip="' . $help_memory . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'cpu', 'none', '#73879C' ) . '" /></span>';
		$detail     .= '&nbsp;&nbsp;&nbsp;<span class="opcm-chart-button not-ready left" id="opcm-chart-button-uptime" data-position="left" data-tooltip="' . $help_uptime . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'activity', 'none', '#73879C' ) . '" /></span>';
		$result      = '<div class="opcm-row">';
		$result     .= '<div class="opcm-box opcm-box-full-line">';
		$result     .= '<div class="opcm-module-title-bar"><span class="opcm-module-title">' . esc_html__( 'Metrics Variations', 'opcache-manager' ) . '<span class="opcm-module-more">' . $detail . '</span></span></div>';
		$result     .= '<div class="opcm-module-content" id="opcm-main-chart">' . $this->get_graph_placeholder( 274 ) . '</div>';
		$result     .= '</div>';
		$result     .= '</div>';
		$result     .= $this->get_refresh_script(
			[
				'query'   => 'main-chart',
				'queried' => 0,
			]
		);
		return $result;
	}

	/**
	 * Get the domains list.
	 *
	 * @return string  The table ready to print.
	 * @since    1.0.0
	 */
	public function get_events_list() {
		$result  = '<div class="opcm-box opcm-box-full-line">';
		$result .= '<div class="opcm-module-title-bar"><span class="opcm-module-title">' . esc_html__( 'Status Events', 'opcache-manager' ) . '</span></div>';
		$result .= '<div class="opcm-module-content" id="opcm-events">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'events',
				'queried' => 0,
			]
		);
		return $result;
	}

	/**
	 * Get the top domains box.
	 *
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	public function _D_get_top_domain_box() {
		$url     = $this->get_url( [ 'domain' ], [ 'type' => 'domains' ] );
		$detail  = '<a href="' . esc_url( $url ) . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'zoom-in', 'none', '#73879C' ) . '" /></a>';
		$help    = esc_html__( 'View the details of all domains.', 'opcache-manager' );
		$result  = '<div class="opcm-40-module">';
		$result .= '<div class="opcm-module-title-bar"><span class="opcm-module-title">' . esc_html__( 'Top Domains', 'opcache-manager' ) . '</span><span class="opcm-module-more left" data-position="left" data-tooltip="' . $help . '">' . $detail . '</span></div>';
		$result .= '<div class="opcm-module-content" id="opcm-top-domains">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'top-domains',
				'queried' => 5,
			]
		);
		return $result;
	}

	/**
	 * Get the top authority box.
	 *
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	public function _D_get_top_authority_box() {
		$url     = $this->get_url(
			[],
			[
				'type'   => 'authorities',
				'domain' => $this->domain,
			]
		);
		$detail  = '<a href="' . esc_url( $url ) . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'zoom-in', 'none', '#73879C' ) . '" /></a>';
		$help    = esc_html__( 'View the details of all subdomains.', 'opcache-manager' );
		$result  = '<div class="opcm-40-module">';
		$result .= '<div class="opcm-module-title-bar"><span class="opcm-module-title">' . esc_html__( 'Top Subdomains', 'opcache-manager' ) . '</span><span class="opcm-module-more left" data-position="left" data-tooltip="' . $help . '">' . $detail . '</span></div>';
		$result .= '<div class="opcm-module-content" id="opcm-top-authorities">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'top-authorities',
				'queried' => 5,
			]
		);
		return $result;
	}

	/**
	 * Get the top endpoint box.
	 *
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	public function _D_get_top_endpoint_box() {
		$url     = $this->get_url(
			[],
			[
				'type'   => 'endpoints',
				'domain' => $this->domain,
			]
		);
		$detail  = '<a href="' . esc_url( $url ) . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'zoom-in', 'none', '#73879C' ) . '" /></a>';
		$help    = esc_html__( 'View the details of all endpoints.', 'opcache-manager' );
		$result  = '<div class="opcm-40-module">';
		$result .= '<div class="opcm-module-title-bar"><span class="opcm-module-title">' . esc_html__( 'Top Endpoints', 'opcache-manager' ) . '</span><span class="opcm-module-more left" data-position="left" data-tooltip="' . $help . '">' . $detail . '</span></div>';
		$result .= '<div class="opcm-module-content" id="opcm-top-endpoints">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'top-endpoints',
				'queried' => 5,
			]
		);
		return $result;
	}

	/**
	 * Get the map box.
	 *
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	public function _D_get_codes_box() {
		switch ( $this->type ) {
			case 'domain':
				$url = $this->get_url(
					[],
					[
						'type'   => 'authorities',
						'domain' => $this->domain,
						'extra'  => 'codes',
					]
				);
				break;
			case 'authority':
				$url = $this->get_url(
					[],
					[
						'type'   => 'endpoints',
						'domain' => $this->domain,
						'extra'  => 'codes',
					]
				);
				break;
			default:
				$url = $this->get_url(
					[ 'domain' ],
					[
						'type'  => 'domains',
						'extra' => 'codes',
					]
				);
		}
		$detail  = '<a href="' . esc_url( $url ) . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'zoom-in', 'none', '#73879C' ) . '" /></a>';
		$help    = esc_html__( 'View the details of all codes.', 'opcache-manager' );
		$result  = '<div class="opcm-33-module opcm-33-left-module">';
		$result .= '<div class="opcm-module-title-bar"><span class="opcm-module-title">' . esc_html__( 'HTTP codes', 'opcache-manager' ) . '</span><span class="opcm-module-more left" data-position="left" data-tooltip="' . $help . '">' . $detail . '</span></div>';
		$result .= '<div class="opcm-module-content" id="opcm-code">' . $this->get_graph_placeholder( 90 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'code',
				'queried' => 4,
			]
		);
		return $result;
	}

	/**
	 * Get the map box.
	 *
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	public function _D_get_security_box() {
		switch ( $this->type ) {
			case 'domain':
				$url = $this->get_url(
					[],
					[
						'type'   => 'authorities',
						'domain' => $this->domain,
						'extra'  => 'schemes',
					]
				);
				break;
			case 'authority':
				$url = $this->get_url(
					[],
					[
						'type'   => 'endpoints',
						'domain' => $this->domain,
						'extra'  => 'schemes',
					]
				);
				break;
			default:
				$url = $this->get_url(
					[ 'domain' ],
					[
						'type'  => 'domains',
						'extra' => 'schemes',
					]
				);
		}
		$detail  = '<a href="' . esc_url( $url ) . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'zoom-in', 'none', '#73879C' ) . '" /></a>';
		$help    = esc_html__( 'View the details of protocols breakdown.', 'opcache-manager' );
		$result  = '<div class="opcm-33-module opcm-33-center-module">';
		$result .= '<div class="opcm-module-title-bar"><span class="opcm-module-title">' . esc_html__( 'Protocols', 'opcache-manager' ) . '</span><span class="opcm-module-more left" data-position="left" data-tooltip="' . $help . '">' . $detail . '</span></div>';
		$result .= '<div class="opcm-module-content" id="opcm-security">' . $this->get_graph_placeholder( 90 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'security',
				'queried' => 4,
			]
		);
		return $result;
	}

	/**
	 * Get the map box.
	 *
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	public function _D_get_method_box() {
		switch ( $this->type ) {
			case 'domain':
				$url = $this->get_url(
					[],
					[
						'type'   => 'authorities',
						'domain' => $this->domain,
						'extra'  => 'methods',
					]
				);
				break;
			case 'authority':
				$url = $this->get_url(
					[],
					[
						'type'   => 'endpoints',
						'domain' => $this->domain,
						'extra'  => 'methods',
					]
				);
				break;
			default:
				$url = $this->get_url(
					[ 'domain' ],
					[
						'type'  => 'domains',
						'extra' => 'methods',
					]
				);
		}
		$detail  = '<a href="' . esc_url( $url ) . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'zoom-in', 'none', '#73879C' ) . '" /></a>';
		$help    = esc_html__( 'View the details of all methods.', 'opcache-manager' );
		$result  = '<div class="opcm-33-module opcm-33-right-module">';
		$result .= '<div class="opcm-module-title-bar"><span class="opcm-module-title">' . esc_html__( 'Methods', 'opcache-manager' ) . '</span><span class="opcm-module-more left" data-position="left" data-tooltip="' . $help . '">' . $detail . '</span></div>';
		$result .= '<div class="opcm-module-content" id="opcm-method">' . $this->get_graph_placeholder( 90 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'method',
				'queried' => 4,
			]
		);
		return $result;
	}

	/**
	 * Get a large kpi box.
	 *
	 * @param   string $kpi     The kpi to render.
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	private function get_large_kpi( $kpi ) {
		switch ( $kpi ) {
			case 'ratio':
				$icon  = Feather\Icons::get_base64( 'award', 'none', '#73879C' );
				$title = esc_html_x( 'Hit Ratio', 'Noun - Cache hit ratio.', 'opcache-manager' );
				$help  = esc_html__( 'The ratio between hit and total calls.', 'opcache-manager' );
				break;
			case 'memory':
				$icon  = Feather\Icons::get_base64( 'cpu', 'none', '#73879C' );
				$title = esc_html_x( 'Free Memory', 'Noun - Memory free of allocation.', 'opcache-manager' );
				$help  = esc_html__( 'Ratio of free available memory.', 'opcache-manager' );
				break;
			case 'script':
				$icon  = Feather\Icons::get_base64( 'file-text', 'none', '#73879C' );
				$title = esc_html_x( 'Cached Files', 'Noun - Number of already cached files.', 'opcache-manager' );
				$help  = esc_html__( 'Number of compiled and cached files.', 'opcache-manager' );
				break;
			case 'key':
				$icon  = Feather\Icons::get_base64( 'key', 'none', '#73879C' );
				$title = esc_html_x( 'Keys Saturation', 'Noun - Ratio of the allocated keys to the total available keys slots.', 'opcache-manager' );
				$help  = esc_html__( 'Ratio of the allocated keys to the total available keys slots.', 'opcache-manager' );
				break;
			case 'buffer':
				$icon  = Feather\Icons::get_base64( 'database', 'none', '#73879C' );
				$title = esc_html_x( 'Buffer Saturation', 'Noun - Ratio of the used buffer to the total buffer size.', 'opcache-manager' );
				$help  = esc_html__( 'Ratio of the used buffer to the total buffer size.', 'opcache-manager' );
				break;
			case 'uptime':
				$icon  = Feather\Icons::get_base64( 'activity', 'none', '#73879C' );
				$title = esc_html_x( 'Availability', 'Noun - Ratio of time when OPcache is not disabled.', 'opcache-manager' );
				$help  = esc_html__( 'Time ratio with an operational OPcache.', 'opcache-manager' );
				break;
		}
		$top       = '<img style="width:12px;vertical-align:baseline;" src="' . $icon . '" />&nbsp;&nbsp;<span style="cursor:help;" class="opcm-kpi-large-top-text bottom" data-position="bottom" data-tooltip="' . $help . '">' . $title . '</span>';
		$indicator = '&nbsp;';
		$bottom    = '<span class="opcm-kpi-large-bottom-text">&nbsp;</span>';
		$result    = '<div class="opcm-kpi-large-top">' . $top . '</div>';
		$result   .= '<div class="opcm-kpi-large-middle"><div class="opcm-kpi-large-middle-left" id="kpi-main-' . $kpi . '">' . $this->get_value_placeholder() . '</div><div class="opcm-kpi-large-middle-right" id="kpi-index-' . $kpi . '">' . $indicator . '</div></div>';
		$result   .= '<div class="opcm-kpi-large-bottom" id="kpi-bottom-' . $kpi . '">' . $bottom . '</div>';
		$result   .= $this->get_refresh_script(
			[
				'query'   => 'kpi',
				'queried' => $kpi,
			]
		);
		return $result;
	}

	/**
	 * Get a placeholder for graph.
	 *
	 * @param   integer $height The height of the placeholder.
	 * @return string  The placeholder, ready to print.
	 * @since    1.0.0
	 */
	private function get_graph_placeholder( $height ) {
		return '<p style="text-align:center;line-height:' . $height . 'px;"><img style="width:40px;vertical-align:middle;" src="' . OPCM_ADMIN_URL . 'medias/bars.svg" /></p>';
	}

	/**
	 * Get a placeholder for value.
	 *
	 * @return string  The placeholder, ready to print.
	 * @since    1.0.0
	 */
	private function get_value_placeholder() {
		return '<img style="width:26px;vertical-align:middle;" src="' . OPCM_ADMIN_URL . 'medias/three-dots.svg" />';
	}

	/**
	 * Get refresh script.
	 *
	 * @param   array $args Optional. The args for the ajax call.
	 * @return string  The script, ready to print.
	 * @since    1.0.0
	 */
	private function get_refresh_script( $args = [] ) {
		$result  = '<script>';
		$result .= 'jQuery(document).ready( function($) {';
		$result .= ' var data = {';
		$result .= '  action:"opcm_get_stats",';
		$result .= '  nonce:"' . wp_create_nonce( 'ajax_opcm' ) . '",';
		foreach ( $args as $key => $val ) {
			$s = '  ' . $key . ':';
			if ( is_string( $val ) ) {
				$s .= '"' . $val . '"';
			} elseif ( is_numeric( $val ) ) {
				$s .= $val;
			} elseif ( is_bool( $val ) ) {
				$s .= $val ? 'true' : 'false';
			}
			$result .= $s . ',';
		}
		$result .= '  start:"' . $this->start . '",';
		$result .= '  end:"' . $this->end . '",';
		$result .= ' };';
		$result .= ' $.post(ajaxurl, data, function(response) {';
		$result .= ' var val = JSON.parse(response);';
		$result .= ' $.each(val, function(index, value) {$("#" + index).html(value);});';
		if ( array_key_exists( 'query', $args ) && 'main-chart' === $args['query'] ) {
			$result .= '$(".opcm-chart-button").removeClass("not-ready");';
			$result .= '$("#opcm-chart-button-ratio").addClass("active");';
		}
		$result .= ' });';
		$result .= '});';
		$result .= '</script>';
		return $result;
	}

	/**
	 * Get the url.
	 *
	 * @param   array $exclude Optional. The args to exclude.
	 * @param   array $replace Optional. The args to replace or add.
	 * @return string  The url.
	 * @since    1.0.0
	 */
	private function get_url( $exclude = [], $replace = [] ) {
		$params          = [];
		$params['start'] = $this->start;
		$params['end']   = $this->end;
		foreach ( $exclude as $arg ) {
			unset( $params[ $arg ] );
		}
		foreach ( $replace as $key => $arg ) {
			$params[ $key ] = $arg;
		}
		$url = admin_url( 'tools.php?page=opcm-viewer' );
		foreach ( $params as $key => $arg ) {
			if ( '' !== $arg ) {
				$url .= '&' . $key . '=' . $arg;
			}
		}
		return $url;
	}

	/**
	 * Get a date picker box.
	 *
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	private function get_date_box() {
		$result  = '<img style="width:13px;vertical-align:middle;" src="' . Feather\Icons::get_base64( 'calendar', 'none', '#5A738E' ) . '" />&nbsp;&nbsp;<span class="opcm-datepicker-value"></span>';
		$result .= '<script>';
		$result .= 'jQuery(function ($) {';
		$result .= ' moment.locale("' . L10n::get_display_locale() . '");';
		$result .= ' var start = moment("' . $this->start . '");';
		$result .= ' var end = moment("' . $this->end . '");';
		$result .= ' function changeDate(start, end) {';
		$result .= '  $("span.opcm-datepicker-value").html(start.format("ll") + " - " + end.format("ll"));';
		$result .= ' }';
		$result .= ' $(".opcm-datepicker").daterangepicker({';
		$result .= '  opens: "left",';
		$result .= '  startDate: start,';
		$result .= '  endDate: end,';
		$result .= '  minDate: moment("' . Schema::get_oldest_date() . '"),';
		$result .= '  maxDate: moment(),';
		$result .= '  showCustomRangeLabel: true,';
		$result .= '  alwaysShowCalendars: true,';
		$result .= '  locale: {customRangeLabel: "' . esc_html__( 'Custom Range', 'opcache-manager' ) . '",cancelLabel: "' . esc_html__( 'Cancel', 'opcache-manager' ) . '", applyLabel: "' . esc_html__( 'Apply', 'opcache-manager' ) . '"},';
		$result .= '  ranges: {';
		$result .= '    "' . esc_html__( 'Today', 'opcache-manager' ) . '": [moment(), moment()],';
		$result .= '    "' . esc_html__( 'Yesterday', 'opcache-manager' ) . '": [moment().subtract(1, "days"), moment().subtract(1, "days")],';
		$result .= '    "' . esc_html__( 'This Month', 'opcache-manager' ) . '": [moment().startOf("month"), moment().endOf("month")],';
		$result .= '    "' . esc_html__( 'Last Month', 'opcache-manager' ) . '": [moment().subtract(1, "month").startOf("month"), moment().subtract(1, "month").endOf("month")],';
		$result .= '  }';
		$result .= ' }, changeDate);';
		$result .= ' changeDate(start, end);';
		$result .= ' $(".opcm-datepicker").on("apply.daterangepicker", function(ev, picker) {';
		$result .= '  var url = "' . $this->get_url( [ 'start', 'end' ] ) . '" + "&start=" + picker.startDate.format("YYYY-MM-DD") + "&end=" + picker.endDate.format("YYYY-MM-DD");';
		$result .= '  $(location).attr("href", url);';
		$result .= ' });';
		$result .= '});';
		$result .= '</script>';
		return $result;
	}

}

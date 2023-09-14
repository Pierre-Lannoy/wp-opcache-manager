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
use OPcacheManager\System\Cache;
use OPcacheManager\System\Date;
use OPcacheManager\System\Conversion;
use OPcacheManager\System\L10n;
use OPcacheManager\System\OPcache;
use OPcacheManager\System\Timezone;
use OPcacheManager\System\UUID;
use OPcacheManager\System\Logger;
use OPcacheManager\Plugin\Feature\Capture;
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
		}
		return [];
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
					$name = esc_html__( 'Programmatic site invalidation and warm-up.', 'opcache-manager' );
					$op   = $row['status'];
					break;
				case 'warmup':
					$icon = '<img style="width:14px;vertical-align:text-bottom;" src="' . Feather\Icons::get_base64( 'mouse-pointer', 'none', '#73879C' ) . '" />';
					$name = esc_html__( 'Manual site warm-up.', 'opcache-manager' );
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
		$uuid       = UUID::generate_unique_id( 5 );
		$query      = Schema::get_time_series( $this->filter, ! $this->is_today, '', [], false );
		$data       = [];
		$series     = [];
		$items      = [ 'status', 'mem_total', 'mem_used', 'mem_wasted', 'key_total', 'key_used', 'buf_total', 'buf_used', 'hit', 'miss', 'strings', 'scripts' ];
		$maxhit     = 0;
		$maxstrings = 0;
		$maxscripts = 0;
		// Data normalization.
		if ( 0 !== count( $query ) ) {
			if ( 1 === $this->duration ) {
				$start  = new \DateTime( Date::get_mysql_utc_from_date( $this->start . ' 00:00:00', $this->timezone->getName() ), new \DateTimeZone( 'UTC' ) );
				$real   = new \DateTime( array_values( $query )[0]['timestamp'], new \DateTimeZone( 'UTC' ) );
				$offset = $this->timezone->getOffset( $real );
				$ts     = $start->getTimestamp();
				$record = [];
				foreach ( $items as $item ) {
					$record[ $item ] = 0;
				}
				while ( 300 + Capture::$delta < $real->getTimestamp() - $ts ) {
					$ts                    = $ts + 300;
					$data[ $ts + $offset ] = $record;
				}
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
				$datetime = new \DateTime( $this->start . ' 00:00:00', $this->timezone );
				$offset   = $this->timezone->getOffset( $datetime );
				$datetime = $datetime->getTimestamp() + $offset;
				$before   = [
					'x' => 'new Date(' . (string) ( $datetime ) . '000)',
					'y' => 'null',
				];
				$datetime = new \DateTime( $this->end . ' 23:59:59', $this->timezone );
				$offset   = $this->timezone->getOffset( $datetime );
				$datetime = $datetime->getTimestamp() + $offset;
				$after    = [
					'x' => 'new Date(' . (string) ( $datetime ) . '000)',
					'y' => 'null',
				];
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
							if ( 'status' === $item ) {
								$record[ $item ] = ( 'disabled' === $row[ $item ] ? 0 : 100 );
							} else {
								$record[ $item ] = $record[ $item ] + $row[ $item ];
							}
						}
					}
					$cpt = count( $rows );
					if ( 0 < $cpt ) {
						foreach ( $items as $item ) {
							$record[ $item ] = (int) round( $record[ $item ] / $cpt, 0 );
						}
					}
					$data[ strtotime( $timestamp ) ] = $record;
				}
				$before   = [
					'x' => 'new Date(' . (string) ( strtotime( $this->start ) - 86400 ) . '000)',
					'y' => 'null',
				];
				$after    = [
					'x' => 'new Date(' . (string) ( strtotime( $this->end ) + 86400 ) . '000)',
					'y' => 'null',
				];
			}
			// Series computation.
			foreach ( $data as $timestamp => $datum ) {
				$ts = 'new Date(' . (string) $timestamp . '000)';
				// Hit ratio.
				$val = 'null';
				if ( 0 !== (int) $datum['hit'] + (int) $datum['miss'] ) {
					$val = round( 100 * $datum['hit'] / ( $datum['hit'] + $datum['miss'] ), 3 );
				}
				$series['ratio'][] = [
					'x' => $ts,
					'y' => $val,
				];
				// Availablility.
				$series['availability'][] = [
					'x' => $ts,
					'y' => ( 'disabled' === $datum['status'] ? 0 : 100 ),
				];
				// Time series.
				foreach ( [ 'hit', 'miss', 'strings', 'scripts' ] as $item ) {
					$val               = (int) $datum[ $item ];
					$series[ $item ][] = [
						'x' => $ts,
						'y' => $val,
					];
					switch ( $item ) {
						case 'hit':
						case 'miss':
							if ( $maxhit < $val ) {
								$maxhit = $val;
							}
							break;
						case 'strings':
							if ( $maxstrings < $val ) {
								$maxstrings = $val;
							}
							break;
						case 'scripts':
							if ( $maxscripts < $val ) {
								$maxscripts = $val;
							}
							break;
					}
				}
				// Time series (free vs.used).
				foreach ( [ 'buf', 'key', 'mem' ] as $item ) {
					if ( 'key' === $item ) {
						$factor = 1024;
					} else {
						$factor = 1024 * 1024;
					}
					if ( 'mem' === $item ) {
						$series['memory'][0][] = [
							'x' => $ts,
							'y' => round( $datum['mem_used'] / $factor, 2 ),
						];
						$series['memory'][1][] = [
							'x' => $ts,
							'y' => round( ( $datum['mem_total'] - $datum['mem_used'] - $datum['mem_wasted'] ) / $factor, 2 ),
						];
						$series['memory'][2][] = [
							'x' => $ts,
							'y' => round( $datum['mem_wasted'] / $factor, 2 ),
						];
					} else {
						$series[ $item ][0][] = [
							'x' => $ts,
							'y' => round( $datum[ $item . '_used' ] / $factor, 2 ),
						];
						$series[ $item ][1][] = [
							'x' => $ts,
							'y' => round( ( $datum[ $item . '_total' ] - $datum[ $item . '_used' ] ) / $factor, 2 ),
						];
					}
				}
			}
			// Hit ratio.
			array_unshift( $series['ratio'], $before );
			$series['ratio'][] = $after;
			$json_ratio        = wp_json_encode(
				[
					'series' => [
						[
							'name' => esc_html_x( 'Hit Ratio', 'Noun - Cache hit ratio.', 'opcache-manager' ),
							'data' => $series['ratio'],
						],
					],
				]
			);
			$json_ratio        = str_replace( '"x":"new', '"x":new', $json_ratio );
			$json_ratio        = str_replace( ')","y"', '),"y"', $json_ratio );
			$json_ratio        = str_replace( '"null"', 'null', $json_ratio );

			// Availability.
			array_unshift( $series['availability'], $before );
			$series['availability'][] = $after;
			$json_availability        = wp_json_encode(
				[
					'series' => [
						[
							'name' => esc_html__( 'Availability', 'opcache-manager' ),
							'data' => $series['availability'],
						],
					],
				]
			);
			$json_availability        = str_replace( '"x":"new', '"x":new', $json_availability );
			$json_availability        = str_replace( ')","y"', '),"y"', $json_availability );
			$json_availability        = str_replace( '"null"', 'null', $json_availability );

			// Hit & miss distribution.
			array_unshift( $series['hit'], $before );
			$series['hit'][] = $after;
			array_unshift( $series['miss'], $before );
			$series['miss'][] = $after;
			$json_hit         = wp_json_encode(
				[
					'series' => [
						[
							'name' => esc_html__( 'Hit Count', 'opcache-manager' ),
							'data' => $series['hit'],
						],
						[
							'name' => esc_html__( 'Miss Count', 'opcache-manager' ),
							'data' => $series['miss'],
						],
					],
				]
			);
			$json_hit         = str_replace( '"x":"new', '"x":new', $json_hit );
			$json_hit         = str_replace( ')","y"', '),"y"', $json_hit );
			$json_hit         = str_replace( '"null"', 'null', $json_hit );

			// Scripts variation.
			array_unshift( $series['scripts'], $before );
			$series['scripts'][] = $after;
			$json_scripts        = wp_json_encode(
				[
					'series' => [
						[
							'name' => esc_html__( 'Files Count', 'opcache-manager' ),
							'data' => $series['scripts'],
						],
					],
				]
			);
			$json_scripts        = str_replace( '"x":"new', '"x":new', $json_scripts );
			$json_scripts        = str_replace( ')","y"', '),"y"', $json_scripts );
			$json_scripts        = str_replace( '"null"', 'null', $json_scripts );

			// Strings variation.
			array_unshift( $series['strings'], $before );
			$series['strings'][] = $after;
			$json_strings        = wp_json_encode(
				[
					'series' => [
						[
							'name' => esc_html__( 'Strings Count', 'opcache-manager' ),
							'data' => $series['strings'],
						],
					],
				]
			);
			$json_strings        = str_replace( '"x":"new', '"x":new', $json_strings );
			$json_strings        = str_replace( ')","y"', '),"y"', $json_strings );
			$json_strings        = str_replace( '"null"', 'null', $json_strings );

			// Memory.
			array_unshift( $series['memory'][0], $before );
			$series['memory'][0][] = $after;
			array_unshift( $series['memory'][1], $before );
			$series['memory'][1][] = $after;
			array_unshift( $series['memory'][2], $before );
			$series['memory'][2][] = $after;
			$json_memory = wp_json_encode(
				[
					'series' => [
						[
							'name' => esc_html__( 'Used Memory', 'opcache-manager' ),
							'data' => $series['memory'][0],
						],
						[
							'name' => esc_html__( 'Free Memory', 'opcache-manager' ),
							'data' => $series['memory'][1],
						],
						[
							'name' => esc_html__( 'Wasted Memory', 'opcache-manager' ),
							'data' => $series['memory'][2],
						],
					],
				]
			);
			$json_memory = str_replace( '"x":"new', '"x":new', $json_memory );
			$json_memory = str_replace( ')","y"', '),"y"', $json_memory );
			$json_memory = str_replace( '"null"', 'null', $json_memory );

			// Key.
			array_unshift( $series['key'][0], $before );
			$series['key'][0][] = $after;
			array_unshift( $series['key'][1], $before );
			$series['mkeyem'][1][] = $after;
			$json_key = wp_json_encode(
				[
					'series' => [
						[
							'name' => esc_html__( 'Used Key Slots', 'opcache-manager' ),
							'data' => $series['key'][0],
						],
						[
							'name' => esc_html__( 'Free Key Slots', 'opcache-manager' ),
							'data' => $series['key'][1],
						],
					],
				]
			);
			$json_key = str_replace( '"x":"new', '"x":new', $json_key );
			$json_key = str_replace( ')","y"', '),"y"', $json_key );
			$json_key = str_replace( '"null"', 'null', $json_key );

			// Buf.
			array_unshift( $series['buf'][0], $before );
			$series['buf'][0][] = $after;
			array_unshift( $series['buf'][1], $before );
			$series['buf'][1][] = $after;
			$json_buf = wp_json_encode(
				[
					'series' => [
						[
							'name' => esc_html__( 'Used Buffer', 'opcache-manager' ),
							'data' => $series['buf'][0],
						],
						[
							'name' => esc_html__( 'Free Buffer', 'opcache-manager' ),
							'data' => $series['buf'][1],
						],
					],
				]
			);
			$json_buf = str_replace( '"x":"new', '"x":new', $json_buf );
			$json_buf = str_replace( ')","y"', '),"y"', $json_buf );
			$json_buf = str_replace( '"null"', 'null', $json_buf );

			// Rendering.
			$ticks  = (int) ( 1 + ( $this->duration / 15 ) );
			if ( 1 < $this->duration ) {
				$style = 'opcm-multichart-xlarge-item';
				if ( 20 < $this->duration ) {
					$style = 'opcm-multichart-large-item';
				}
				if ( 40 < $this->duration ) {
					$style = 'opcm-multichart-medium-item';
				}
				if ( 60 < $this->duration ) {
					$style = 'opcm-multichart-small-item';
				}
				if ( 80 < $this->duration ) {
					$style = 'opcm-multichart-xsmall-item';
				}
			} else {
				$style = 'opcm-multichart-xxsmall-item';
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
				$result .= '  axisX: {showGrid: true, scaleMinSpace: 10, type: Chartist.FixedScaleAxis, divisor:' . ( $this->duration + 1 ) . ', labelInterpolationFnc: function skipLabels(value, index, labels) {return 0 === index % ' . $ticks . ' ? moment(value).format("DD") : null;}},';
			} else {
				$result .= '  axisX: {showGrid: true,labelOffset: {x: -8,y: 0},scaleMinSpace: 100, type: Chartist.FixedScaleAxis, divisor:8, labelInterpolationFnc: function (value) {var shift=0;if(moment(value).isDST()){shift=3600000};return moment(value-shift).format("HH:00");}},';
			}
			$result .= '  axisY: {type: Chartist.AutoScaleAxis, labelInterpolationFnc: function (value) {return value.toString() + " %";}},';
			$result .= ' };';
			$result .= ' new Chartist.Line("#opcm-chart-ratio", ratio_data' . $uuid . ', ratio_option' . $uuid . ');';
			$result .= '});';
			$result .= '</script>';
			$result .= '<div class="opcm-multichart-item" id="opcm-chart-uptime">';
			$result .= '</div>';
			$result .= '<script>';
			$result .= 'jQuery(function ($) {';
			$result .= ' var uptime_data' . $uuid . ' = ' . $json_availability . ';';
			$result .= ' var uptime_tooltip' . $uuid . ' = Chartist.plugins.tooltip({percentage: false, appendToBody: true});';
			$result .= ' var uptime_option' . $uuid . ' = {';
			$result .= '  height: 300,';
			$result .= '  fullWidth: true,';
			$result .= '  showArea: true,';
			$result .= '  showLine: true,';
			$result .= '  showPoint: false,';
			$result .= '  plugins: [uptime_tooltip' . $uuid . '],';
			if ( 1 < $this->duration ) {
				$result .= '  axisX: {showGrid: true, scaleMinSpace: 10, type: Chartist.FixedScaleAxis, divisor:' . ( $this->duration + 1 ) . ', labelInterpolationFnc: function skipLabels(value, index, labels) {return 0 === index % ' . $ticks . ' ? moment(value).format("DD") : null;}},';
			} else {
				$result .= '  axisX: {showGrid: true,labelOffset: {x: -8,y: 0},scaleMinSpace: 100, type: Chartist.FixedScaleAxis, divisor:8, labelInterpolationFnc: function (value) {var shift=0;if(moment(value).isDST()){shift=3600000};return moment(value-shift).format("HH:00");}},';
			}
			$result .= '  axisY: {type: Chartist.AutoScaleAxis, labelInterpolationFnc: function (value) {return value.toString() + " %";}},';
			$result .= ' };';
			$result .= ' new Chartist.Line("#opcm-chart-uptime", uptime_data' . $uuid . ', uptime_option' . $uuid . ');';
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
				$result .= '  axisX: {showGrid: true, scaleMinSpace: 10, type: Chartist.FixedScaleAxis, divisor:' . ( $this->duration + 1 ) . ', labelInterpolationFnc: function skipLabels(value, index, labels) {return 0 === index % ' . $ticks . ' ? moment(value).format("DD") : null;}},';
			} else {
				$result .= '  axisX: {showGrid: true,labelOffset: {x: -8,y: 0},scaleMinSpace: 100, type: Chartist.FixedScaleAxis, divisor:8, labelInterpolationFnc: function (value) {var shift=0;if(moment(value).isDST()){shift=3600000};return moment(value-shift).format("HH:00");}},';
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
			$result .= '<div class="opcm-multichart-item" id="opcm-chart-string">';
			$result .= '</div>';
			$result .= '<script>';
			$result .= 'jQuery(function ($) {';
			$result .= ' var string_data' . $uuid . ' = ' . $json_strings . ';';
			$result .= ' var string_tooltip' . $uuid . ' = Chartist.plugins.tooltip({percentage: false, appendToBody: true});';
			$result .= ' var string_option' . $uuid . ' = {';
			$result .= '  height: 300,';
			$result .= '  fullWidth: true,';
			$result .= '  showArea: true,';
			$result .= '  showLine: true,';
			$result .= '  showPoint: false,';
			$result .= '  plugins: [string_tooltip' . $uuid . '],';
			if ( 1 < $this->duration ) {
				$result .= '  axisX: {showGrid: true, scaleMinSpace: 10, type: Chartist.FixedScaleAxis, divisor:' . ( $this->duration + 1 ) . ', labelInterpolationFnc: function skipLabels(value, index, labels) {return 0 === index % ' . $ticks . ' ? moment(value).format("DD") : null;}},';
			} else {
				$result .= '  axisX: {showGrid: true,labelOffset: {x: -8,y: 0},scaleMinSpace: 100, type: Chartist.FixedScaleAxis, divisor:8, labelInterpolationFnc: function (value) {var shift=0;if(moment(value).isDST()){shift=3600000};return moment(value-shift).format("HH:00");}},';
			}
			if ( $maxstrings < 1000 ) {
				$result .= '  axisY: {type: Chartist.AutoScaleAxis, labelInterpolationFnc: function (value) {return value.toString();}},';
			} elseif ( $maxstrings < 1000000 ) {
				$result .= '  axisY: {type: Chartist.AutoScaleAxis, labelInterpolationFnc: function (value) {value = value / 1000; return value.toString() + " K";}},';
			} else {
				$result .= '  axisY: {type: Chartist.AutoScaleAxis, labelInterpolationFnc: function (value) {value = value / 1000000; return value.toString() + " M";}},';
			}
			$result .= ' };';
			$result .= ' new Chartist.Line("#opcm-chart-string", string_data' . $uuid . ', string_option' . $uuid . ');';
			$result .= '});';
			$result .= '</script>';
			$result .= '<div class="opcm-multichart-item" id="opcm-chart-file">';
			$result .= '</div>';
			$result .= '<script>';
			$result .= 'jQuery(function ($) {';
			$result .= ' var file_data' . $uuid . ' = ' . $json_scripts . ';';
			$result .= ' var file_tooltip' . $uuid . ' = Chartist.plugins.tooltip({percentage: false, appendToBody: true});';
			$result .= ' var file_option' . $uuid . ' = {';
			$result .= '  height: 300,';
			$result .= '  fullWidth: true,';
			$result .= '  showArea: true,';
			$result .= '  showLine: true,';
			$result .= '  showPoint: false,';
			$result .= '  plugins: [file_tooltip' . $uuid . '],';
			if ( 1 < $this->duration ) {
				$result .= '  axisX: {showGrid: true, scaleMinSpace: 10, type: Chartist.FixedScaleAxis, divisor:' . ( $this->duration + 1 ) . ', labelInterpolationFnc: function skipLabels(value, index, labels) {return 0 === index % ' . $ticks . ' ? moment(value).format("DD") : null;}},';
			} else {
				$result .= '  axisX: {showGrid: true,labelOffset: {x: -8,y: 0},scaleMinSpace: 100, type: Chartist.FixedScaleAxis, divisor:8, labelInterpolationFnc: function (value) {var shift=0;if(moment(value).isDST()){shift=3600000};return moment(value-shift).format("HH:00");}},';
			}
			if ( $maxscripts < 1000 ) {
				$result .= '  axisY: {type: Chartist.AutoScaleAxis, labelInterpolationFnc: function (value) {return value.toString();}},';
			} elseif ( $maxscripts < 1000000 ) {
				$result .= '  axisY: {type: Chartist.AutoScaleAxis, labelInterpolationFnc: function (value) {value = value / 1000; return value.toString() + " K";}},';
			} else {
				$result .= '  axisY: {type: Chartist.AutoScaleAxis, labelInterpolationFnc: function (value) {value = value / 1000000; return value.toString() + " M";}},';
			}
			$result .= ' };';
			$result .= ' new Chartist.Line("#opcm-chart-file", file_data' . $uuid . ', file_option' . $uuid . ');';
			$result .= '});';
			$result .= '</script>';
			$result .= '<div class="' . $style . '" id="opcm-chart-memory">';
			$result .= '</div>';
			$result .= '<script>';
			$result .= 'jQuery(function ($) {';
			$result .= ' var memory_data' . $uuid . ' = ' . $json_memory . ';';
			$result .= ' var memory_tooltip' . $uuid . ' = Chartist.plugins.tooltip({justvalue: true, appendToBody: true});';
			$result .= ' var memory_option' . $uuid . ' = {';
			$result .= '  height: 300,';
			$result .= '  stackBars: true,';
			$result .= '  stackMode: "accumulate",';
			$result .= '  seriesBarDistance: 1,';
			$result .= '  plugins: [memory_tooltip' . $uuid . '],';
			if ( 1 < $this->duration ) {
				$result .= '  axisX: {showGrid: false, scaleMinSpace: 10, type: Chartist.FixedScaleAxis, divisor:' . ( $this->duration + 1 ) . ', labelInterpolationFnc: function skipLabels(value, index, labels) {return 0 === index % ' . $ticks . ' ? moment(value).format("DD") : null;}},';
			} else {
				$result .= '  axisX: {showGrid: false,labelOffset: {x: -8,y: 0},type: Chartist.FixedScaleAxis, divisor:8, labelInterpolationFnc: function (value) {var shift=0;if(moment(value).isDST()){shift=3600000};return moment(value-shift).format("HH:00");}},';
			}
			$result .= '  axisY: {showGrid: true, labelInterpolationFnc: function (value) {return value.toString() + " ' . esc_html_x( 'MB', 'Abbreviation - Stands for "megabytes".', 'opcache-manager' ) . '";}},';
			$result .= ' };';
			$result .= ' new Chartist.Bar("#opcm-chart-memory", memory_data' . $uuid . ', memory_option' . $uuid . ');';
			$result .= '});';
			$result .= '</script>';
			$result .= '<div class="' . $style . '" id="opcm-chart-buffer">';
			$result .= '</div>';
			$result .= '<script>';
			$result .= 'jQuery(function ($) {';
			$result .= ' var buffer_data' . $uuid . ' = ' . $json_buf . ';';
			$result .= ' var buffer_tooltip' . $uuid . ' = Chartist.plugins.tooltip({justvalue: true, appendToBody: true});';
			$result .= ' var buffer_option' . $uuid . ' = {';
			$result .= '  height: 300,';
			$result .= '  stackBars: true,';
			$result .= '  stackMode: "accumulate",';
			$result .= '  seriesBarDistance: 1,';
			$result .= '  plugins: [buffer_tooltip' . $uuid . '],';
			if ( 1 < $this->duration ) {
				$result .= '  axisX: {showGrid: false, scaleMinSpace: 10, type: Chartist.FixedScaleAxis, divisor:' . ( $this->duration + 1 ) . ', labelInterpolationFnc: function skipLabels(value, index, labels) {return 0 === index % ' . $ticks . ' ? moment(value).format("DD") : null;}},';
			} else {
				$result .= '  axisX: {showGrid: false,labelOffset: {x: -8,y: 0},type: Chartist.FixedScaleAxis, divisor:8, labelInterpolationFnc: function (value) {var shift=0;if(moment(value).isDST()){shift=3600000};return moment(value-shift).format("HH:00");}},';
			}
			$result .= '  axisY: {showGrid: true, labelInterpolationFnc: function (value) {return value.toString() + " ' . esc_html_x( 'MB', 'Abbreviation - Stands for "megabytes".', 'opcache-manager' ) . '";}},';
			$result .= ' };';
			$result .= ' new Chartist.Bar("#opcm-chart-buffer", buffer_data' . $uuid . ', buffer_option' . $uuid . ');';
			$result .= '});';
			$result .= '</script>';
			$result .= '<div class="' . $style . '" id="opcm-chart-key">';
			$result .= '</div>';
			$result .= '<script>';
			$result .= 'jQuery(function ($) {';
			$result .= ' var key_data' . $uuid . ' = ' . $json_key . ';';
			$result .= ' var key_tooltip' . $uuid . ' = Chartist.plugins.tooltip({justvalue: true, appendToBody: true});';
			$result .= ' var key_option' . $uuid . ' = {';
			$result .= '  height: 300,';
			$result .= '  stackBars: true,';
			$result .= '  stackMode: "accumulate",';
			$result .= '  seriesBarDistance: 1,';
			$result .= '  plugins: [key_tooltip' . $uuid . '],';
			if ( 1 < $this->duration ) {
				$result .= '  axisX: {showGrid: false, scaleMinSpace: 10, type: Chartist.FixedScaleAxis, divisor:' . ( $this->duration + 1 ) . ', labelInterpolationFnc: function skipLabels(value, index, labels) {return 0 === index % ' . $ticks . ' ? moment(value).format("DD") : null;}},';
			} else {
				$result .= '  axisX: {showGrid: false,labelOffset: {x: -8,y: 0},type: Chartist.FixedScaleAxis, divisor:8, labelInterpolationFnc: function (value) {var shift=0;if(moment(value).isDST()){shift=3600000};return moment(value-shift).format("HH:00");}},';
			}
			$result .= '  axisY: {showGrid: true, labelInterpolationFnc: function (value) {return value.toString() + " ' . esc_html_x( 'K', 'Abbreviation - Stands for "thousand".', 'opcache-manager' ) . '";}},';
			$result .= ' };';
			$result .= ' new Chartist.Bar("#opcm-chart-key", key_data' . $uuid . ', key_option' . $uuid . ');';
			$result .= '});';
			$result .= '</script>';
			$result .= '<div class="opcm-multichart-item" id="opcm-chart-data">';
			$result .= '</div>';
		} else {
			$result  = '<div class="opcm-multichart-handler">';
			$result .= '<div class="opcm-multichart-item active" id="opcm-chart-ratio">';
			$result .= $this->get_graph_placeholder_nodata( 274 );
			$result .= '</div>';
			$result .= '<div class="opcm-multichart-item" id="opcm-chart-uptime">';
			$result .= $this->get_graph_placeholder_nodata( 274 );
			$result .= '</div>';
			$result .= '<div class="opcm-multichart-item" id="opcm-chart-hit">';
			$result .= $this->get_graph_placeholder_nodata( 274 );
			$result .= '</div>';
			$result .= '<div class="opcm-multichart-item" id="opcm-chart-string">';
			$result .= $this->get_graph_placeholder_nodata( 274 );
			$result .= '</div>';
			$result .= '<div class="opcm-multichart-item" id="opcm-chart-file">';
			$result .= $this->get_graph_placeholder_nodata( 274 );
			$result .= '</div>';
			$result .= '<div class="opcm-multichart-item" id="opcm-chart-memory">';
			$result .= $this->get_graph_placeholder_nodata( 274 );
			$result .= '</div>';
			$result .= '<div class="opcm-multichart-item" id="opcm-chart-buffer">';
			$result .= $this->get_graph_placeholder_nodata( 274 );
			$result .= '</div>';
			$result .= '<div class="opcm-multichart-item" id="opcm-chart-key">';
			$result .= $this->get_graph_placeholder_nodata( 274 );
			$result .= '</div>';
			$result .= '<div class="opcm-multichart-item" id="opcm-chart-data">';
			$result .= '</div>';
		}
		return [ 'opcm-main-chart' => $result ];
	}

	/**
	 * Query all kpis in statistics table.
	 *
	 * @param   array   $args   Optional. The needed args.
	 * @return array  The KPIs ready to send.
	 * @since    1.0.0
	 */
	public static function get_status_kpi_collection( $args = [] ) {
		$result['meta'] = [
			'plugin'  => OPCM_PRODUCT_NAME . ' ' . OPCM_VERSION,
			'opcache' => OPcache::name(),
			'period'  => date( 'Y-m-d' ),
		];
		$result['data'] = [];
		$kpi            = new static( date( 'Y-m-d' ), date( 'Y-m-d' ), false );
		foreach ( [ 'ratio', 'memory', 'key', 'buffer', 'uptime', 'script' ] as $query ) {
			$data = $kpi->query_kpi( $query, false );
			switch ( $query ) {
				case 'ratio':
					$val                   = Conversion::number_shorten( $data['kpi-bottom-ratio'], 1, true );
					$result['data']['hit'] = [
						'name'        => esc_html_x( 'Hits', 'Noun - Cache hit.', 'opcache-manager' ),
						'short'       => esc_html_x( 'Hits', 'Noun - Short (max 4 char) - Cache hit.', 'opcache-manager' ),
						'description' => esc_html__( 'Successful calls to the cache.', 'opcache-manager' ),
						'dimension'   => 'none',
						'ratio'       => [
							'raw'      => round( $data['kpi-main-ratio'] / 100, 6 ),
							'percent'  => round( $data['kpi-main-ratio'] ?? 0, 2 ),
							'permille' => round( $data['kpi-main-ratio'] * 10, 2 ),
						],
						'variation'   => [
							'raw'      => round( $data['kpi-index-ratio'] / 100, 6 ),
							'percent'  => round( $data['kpi-index-ratio'] ?? 0, 2 ),
							'permille' => round( $data['kpi-index-ratio'] * 10, 2 ),
						],
						'value'       => [
							'raw'   => $data['kpi-bottom-ratio'],
							'human' => $val['value'] . $val['abbreviation'],
						],
					];
					break;
				case 'memory':
					$val                      = Conversion::data_shorten( $data['kpi-bottom-memory'], 0, true );
					$result['data']['memory'] = [
						'name'        => esc_html_x( 'Total memory', 'Noun - Total memory available for allocation.', 'opcache-manager' ),
						'short'       => esc_html_x( 'Mem.', 'Noun - Short (max 4 char) - Total memory available for allocation.', 'opcache-manager' ),
						'description' => esc_html__( 'Total memory available for OPcache.', 'opcache-manager' ),
						'dimension'   => 'memory',
						'ratio'       => [
							'raw'      => round( 1.0 - $data['kpi-main-memory'] / 100, 6 ),
							'percent'  => round( 100.0 - $data['kpi-main-memory'], 2 ),
							'permille' => round( 1000.0 - $data['kpi-main-memory'] * 10, 2 ),
						],
						'variation'   => [
							'raw'      => round( $data['kpi-index-memory'] / 100, 6 ),
							'percent'  => round( $data['kpi-index-memory'] ?? 0, 2 ),
							'permille' => round( $data['kpi-index-memory'] * 10, 2 ),
						],
						'value'       => [
							'raw'   => $data['kpi-bottom-memory'],
							'human' => $val['value'] . $val['abbreviation'],
						],
					];
					break;
				case 'script':
					$val                      = Conversion::number_shorten( $data['kpi-main-script'], 1, true );
					$result['data']['script'] = [
						'name'        => esc_html_x( 'Scripts', 'Noun - Cached scripts.', 'opcache-manager' ),
						'short'       => esc_html_x( 'Scr.', 'Noun - Short (max 4 char) - Cached scripts.', 'opcache-manager' ),
						'description' => esc_html__( 'Scripts currently present in cache.', 'opcache-manager' ),
						'dimension'   => 'none',
						'ratio'       => null,
						'variation'   => [
							'raw'      => - round( $data['kpi-index-script'] / 100, 6 ),
							'percent'  => - round( $data['kpi-index-script'] ?? 0, 2 ),
							'permille' => - round( $data['kpi-index-script'] * 10, 2 ),
						],
						'value'       => [
							'raw'   => $data['kpi-main-script'],
							'human' => $val['value'] . $val['abbreviation'],
						],
					];
					break;
				case 'key':
					$val                   = Conversion::number_shorten( $data['kpi-bottom-key'], 0, true );
					$result['data']['key'] = [
						'name'        => esc_html_x( 'Keys', 'Noun - Allocated keys.', 'opcache-manager' ),
						'short'       => esc_html_x( 'Keys', 'Noun - Short (max 4 char) - Allocated keys.', 'opcache-manager' ),
						'description' => esc_html__( 'Keys allocated by OPcache.', 'opcache-manager' ),
						'dimension'   => 'none',
						'ratio'       => [
							'raw'      => round( $data['kpi-main-key'] / 100, 6 ),
							'percent'  => round( $data['kpi-main-key'] ?? 0, 2 ),
							'permille' => round( $data['kpi-main-key'] * 10, 2 ),
						],
						'variation'   => [
							'raw'      => round( $data['kpi-index-key'] / 100, 6 ),
							'percent'  => round( $data['kpi-index-key'] ?? 0, 2 ),
							'permille' => round( $data['kpi-index-key'] * 10, 2 ),
						],
						'value'       => [
							'raw'   => $data['kpi-bottom-key'],
							'human' => $val['value'] . $val['abbreviation'],
						],
					];
					break;
				case 'buffer':
					$val                      = Conversion::data_shorten( $data['kpi-bottom-buffer'], 0, true );
					$result['data']['buffer'] = [
						'name'        => esc_html_x( 'Buffer', 'Noun - Buffer.', 'opcache-manager' ),
						'short'       => esc_html_x( 'Buf.', 'Noun - Short (max 4 char) - Buffer.', 'opcache-manager' ),
						'description' => esc_html__( 'Buffer size.', 'opcache-manager' ),
						'dimension'   => 'memory',
						'ratio'       => [
							'raw'      => round( $data['kpi-main-buffer'] / 100, 6 ),
							'percent'  => round( $data['kpi-main-buffer'] ?? 0, 2 ),
							'permille' => round( $data['kpi-main-buffer'] * 10, 2 ),
						],
						'variation'   => [
							'raw'      => round( $data['kpi-index-buffer'] / 100, 6 ),
							'percent'  => round( $data['kpi-index-buffer'] ?? 0, 2 ),
							'permille' => round( $data['kpi-index-buffer'] * 10, 2 ),
						],
						'value'       => [
							'raw'   => $data['kpi-bottom-buffer'],
							'human' => $val['value'] . $val['abbreviation'],
						],
					];
					break;
				case 'uptime':
					$result['data']['uptime'] = [
						'name'        => esc_html_x( 'Availability', 'Noun - Extrapolated availability time over 24 hours.', 'opcache-manager' ),
						'short'       => esc_html_x( 'Avl.', 'Noun - Short (max 4 char) - Extrapolated availability time over 24 hours.', 'opcache-manager' ),
						'description' => esc_html__( 'Extrapolated availability time over 24 hours.', 'opcache-manager' ),
						'dimension'   => 'time',
						'ratio'       => [
							'raw'      => round( $data['kpi-main-uptime'] / 100, 6 ),
							'percent'  => round( $data['kpi-main-uptime'] ?? 0, 2 ),
							'permille' => round( $data['kpi-main-uptime'] * 10, 2 ),
						],
						'variation'   => [
							'raw'      => round( $data['kpi-index-uptime'] / 100, 6 ),
							'percent'  => round( $data['kpi-index-uptime'] ?? 0, 2 ),
							'permille' => round( $data['kpi-index-uptime'] * 10, 2 ),
						],
						'value'       => [
							'raw'   => $data['kpi-bottom-uptime'],
							'human' => implode( ', ', Date::get_age_array_from_seconds( $data['kpi-bottom-uptime'], true, true ) ),
						],
					];
					break;
			}
		}
		$result['assets'] = [];
		return $result;
	}

	/**
	 * Query statistics table.
	 *
	 * @param   mixed       $queried The query params.
	 * @param   boolean     $chart   Optional, return the chart if true, only the data if false;
	 * @return array  The result of the query, ready to encode.
	 * @since    1.0.0
	 */
	public function query_kpi( $queried, $chart = true ) {
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
				$disabled_data  = Schema::get_std_kpi( $this->filter, ! $this->is_today, 'status', [ 'disabled' ] );
				$disabled_pdata = Schema::get_std_kpi( $this->previous, true, 'status', [ 'disabled' ] );
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
				$result[ 'kpi-main-' . $queried ] = round( $current, $chart ? 1 : 4 );
			} else {
				if ( 0.0 !== $data_value ) {
					$result[ 'kpi-main-' . $queried ] = 100;
				} elseif ( 0.0 !== $base_value ) {
					$result[ 'kpi-main-' . $queried ] = 0;
				} else {
					$result[ 'kpi-main-' . $queried ] = null;
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
				$result[ 'kpi-index-' . $queried ] = round( 100 * ( $current - $previous ) / $previous, 4 );
			} else {
				$result[ 'kpi-index-' . $queried ] = null;
			}
			if ( ! $chart ) {
				$result[ 'kpi-bottom-' . $queried ] = null;
				switch ( $queried ) {
					case 'ratio':
						if ( is_array( $data ) && array_key_exists( 'sum_hit', $data ) ) {
							$result[ 'kpi-bottom-' . $queried ] = (int) $data['sum_hit'];
						}
						break;
					case 'memory':
					case 'buffer':
						$result[ 'kpi-bottom-' . $queried ] = (int) round( $base_value, 0 );
						/*break;

						if ( is_array( $data ) && array_key_exists( 'avg_frag_count', $data ) ) {
							$result[ 'kpi-bottom-' . $queried ] = (int) round( $data['avg_frag_count'], 0 );
						}*/
						break;
					case 'key':
						$result[ 'kpi-bottom-' . $queried ] = (int) round( $data_value, 0 );
						break;
					case 'uptime':
						if ( 0.0 !== $base_value ) {
							$result[ 'kpi-bottom-' . $queried ] = (int) round( $this->duration * DAY_IN_SECONDS * ( $data_value / $base_value ) );
						}
						break;
				}
				return $result;
			}
			if ( isset( $result[ 'kpi-main-' . $queried ] ) ) {
				$result[ 'kpi-main-' . $queried ] = $result[ 'kpi-main-' . $queried ] . '&nbsp;%';
			} else {
				$result[ 'kpi-main-' . $queried ] = '-';
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
			if ( ! $chart ) {
				if ( 0.0 !== $current && 0.0 !== $previous ) {
					$result[ 'kpi-index-' . $queried ] = round( 100 * ( $current - $previous ) / $previous, 4 );
				} else {
					$result[ 'kpi-index-' . $queried ] = null;
				}
				$result[ 'kpi-bottom-' . $queried ] = null;
				return $result;
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
		$help_file   = esc_html__( 'Files variation.', 'opcache-manager' );
		$help_key    = esc_html__( 'Keys distribution.', 'opcache-manager' );
		$help_string = esc_html__( 'Strings variation.', 'opcache-manager' );
		$help_buffer = esc_html__( 'Buffer distribution.', 'opcache-manager' );
		$help_uptime = esc_html__( 'Availability variation.', 'opcache-manager' );
		$detail      = '<span class="opcm-chart-button not-ready left" id="opcm-chart-button-ratio" data-position="left" data-tooltip="' . $help_ratio . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'award', 'none', '#73879C' ) . '" /></span>';
		$detail     .= '&nbsp;&nbsp;&nbsp;<span class="opcm-chart-button not-ready left" id="opcm-chart-button-hit" data-position="left" data-tooltip="' . $help_hit . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'hash', 'none', '#73879C' ) . '" /></span>';
		$detail     .= '&nbsp;&nbsp;&nbsp;<span class="opcm-chart-button not-ready left" id="opcm-chart-button-memory" data-position="left" data-tooltip="' . $help_memory . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'cpu', 'none', '#73879C' ) . '" /></span>';
		$detail     .= '&nbsp;&nbsp;&nbsp;<span class="opcm-chart-button not-ready left" id="opcm-chart-button-file" data-position="left" data-tooltip="' . $help_file . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'file-text', 'none', '#73879C' ) . '" /></span>';
		$detail     .= '&nbsp;&nbsp;&nbsp;<span class="opcm-chart-button not-ready left" id="opcm-chart-button-key" data-position="left" data-tooltip="' . $help_key . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'key', 'none', '#73879C' ) . '" /></span>';
		$detail     .= '&nbsp;&nbsp;&nbsp;<span class="opcm-chart-button not-ready left" id="opcm-chart-button-string" data-position="left" data-tooltip="' . $help_string . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'tag', 'none', '#73879C' ) . '" /></span>';
		$detail     .= '&nbsp;&nbsp;&nbsp;<span class="opcm-chart-button not-ready left" id="opcm-chart-button-buffer" data-position="left" data-tooltip="' . $help_buffer . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'database', 'none', '#73879C' ) . '" /></span>';
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
	 * Get a placeholder for graph with no data.
	 *
	 * @param   integer $height The height of the placeholder.
	 * @return string  The placeholder, ready to print.
	 * @since    1.0.0
	 */
	private function get_graph_placeholder_nodata( $height ) {
		return '<p style="color:#73879C;text-align:center;line-height:' . $height . 'px;">' . esc_html__( 'No Data', 'opcache-manager' ) . '</p>';
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
		$url = admin_url( 'admin.php?page=opcm-viewer' );
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
		$result .= '  $("span.opcm-datepicker-value").html(start.format("LL") + " - " + end.format("LL"));';
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

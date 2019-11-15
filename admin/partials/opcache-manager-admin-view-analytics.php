<?php
/**
 * Provide a admin-facing view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @package    Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

use OPcacheManager\System\Role;

wp_enqueue_script( 'opcm-moment-with-locale' );
wp_enqueue_script( 'opcm-daterangepicker' );
wp_enqueue_script( 'opcm-chartist' );
wp_enqueue_script( 'opcm-chartist-tooltip' );
wp_enqueue_script( OPCM_ASSETS_ID );
wp_enqueue_style( OPCM_ASSETS_ID );
wp_enqueue_style( 'opcm-daterangepicker' );
wp_enqueue_style( 'opcm-tooltip' );
wp_enqueue_style( 'opcm-chartist' );
wp_enqueue_style( 'opcm-chartist-tooltip' );


?>

<div class="wrap">
	<div class="opcm-dashboard">
		<div class="opcm-row">
			<?php echo $analytics->get_title_bar() ?>
		</div>
        <div class="opcm-row">
	        <?php echo $analytics->get_kpi_bar() ?>
        </div>
        <div class="opcm-row">
	        <?php echo $analytics->get_main_chart() ?>
        </div>
        <div class="opcm-row">
			<?php echo $analytics->get_events_list() ?>
        </div>
	</div>
</div>

<?php
/**
 * Provide a admin-facing tools for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @package    Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

use OPcacheManager\Plugin\Feature\Scripts;

$scripts = new Scripts();
$scripts->prepare_items();

wp_enqueue_script( OPCM_ASSETS_ID );
wp_enqueue_style( OPCM_ASSETS_ID );

?>

<div class="wrap">
	<h2><?php echo esc_html__( 'OPcache Management', 'opcache-manager'  );; ?></h2>
	<?php settings_errors(); ?>
	<?php $scripts->warning(); ?>
	<?php $scripts->views(); ?>
    <form id="opcm-tools" method="post" action="<?php echo $scripts->get_url(); ?>">
        <input type="hidden" name="page" value="opcm-tools" />
	    <?php $scripts->display(); ?>
    </form>
</div>

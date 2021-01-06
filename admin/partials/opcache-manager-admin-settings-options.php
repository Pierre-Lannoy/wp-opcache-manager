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

use OPcacheManager\System\OPcache;

$disabled = '';
if ( ! function_exists( 'opcache_get_status' ) || OPcache::is_restricted() ) {
	$disabled = ' disabled';
}

?>

<form action="
	<?php
		echo esc_url(
			add_query_arg(
				[
					'page'   => 'opcm-settings',
					'action' => 'do-save',
					'tab'    => 'misc',
				],
				admin_url( 'admin.php' )
			)
		);
		?>
	" method="POST">
	<?php do_settings_sections( 'opcm_plugin_features_section' ); ?>
	<?php do_settings_sections( 'opcm_plugin_options_section' ); ?>
	<?php wp_nonce_field( 'opcm-plugin-options' ); ?>
	<p><?php echo get_submit_button( esc_html__( 'Reset to Defaults', 'opcache-manager' ), 'secondary' . $disabled, 'reset-to-defaults', false ); ?>&nbsp;&nbsp;&nbsp;<?php echo get_submit_button( null, 'primary' . $disabled, 'submit', false ); ?></p>
</form>
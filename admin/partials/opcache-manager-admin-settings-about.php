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

use OPcacheManager\System\Environment;

wp_enqueue_style( OPCM_ASSETS_ID );
wp_enqueue_script( OPCM_ASSETS_ID );

$warning = '';
if ( Environment::is_plugin_in_dev_mode() ) {
	$icon     = '<img style="width:16px;vertical-align:text-bottom;" src="' . \Feather\Icons::get_base64( 'alert-triangle', 'none', '#FF8C00' ) . '" />&nbsp;';
	$warning .= '<p>' . $icon . sprintf( esc_html__( 'This version of %s is not production-ready. It is a development preview. Use it at your own risk!', 'opcache-manager' ), OPCM_PRODUCT_NAME ) . '</p>';
}
if ( Environment::is_plugin_in_rc_mode() ) {
	$icon     = '<img style="width:16px;vertical-align:text-bottom;" src="' . \Feather\Icons::get_base64( 'alert-triangle', 'none', '#FF8C00' ) . '" />&nbsp;';
	$warning .= '<p>' . $icon . sprintf( esc_html__( 'This version of %s is a release candidate. Although ready for production, this version is not officially supported in production environments.', 'opcache-manager' ), OPCM_PRODUCT_NAME ) . '</p>';
}
$intro      = sprintf( esc_html__( '%1$s is a free and open source plugin for WordPress. It integrates other free and open source works (as-is or modified) like: %2$s.', 'opcache-manager' ), '<em>' . OPCM_PRODUCT_NAME . '</em>', do_shortcode( '[opcm-libraries]' ) );
$trademarks = esc_html__( 'All brands, icons and graphic illustrations are registered trademarks of their respective owners.', 'opcache-manager' );
$icon       = '<img class="opcm-about-logo" style="opacity:0;" src="' . OPcacheManager\Plugin\Core::get_base64_logo() . '" />';

?>
<h2><?php echo esc_html( OPCM_PRODUCT_NAME . ' ' . OPCM_VERSION ); ?> / <a href="https://perfops.one">PerfOps One</a></h2>
<?php echo $icon; ?>
<?php echo $warning; ?>
<p><?php echo $intro; ?></p>
<h4><?php esc_html_e( 'Disclaimer', 'opcache-manager' ); ?></h4>
<p><em><?php echo esc_html( $trademarks ); ?></em></p>
<hr/>
<h2><?php esc_html_e( 'Changelog', 'opcache-manager' ); ?></h2>
<?php echo do_shortcode( '[opcm-changelog]' ); ?>

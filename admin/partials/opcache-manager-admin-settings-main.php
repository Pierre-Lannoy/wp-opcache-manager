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

// phpcs:ignore
$active_tab = ( isset( $_GET['tab'] ) ? $_GET['tab'] : 'misc' );
$url1       = esc_url(
	add_query_arg(
		[
			'page' => 'opcm-tools',
		],
		admin_url( 'tools.php' )
	)
);
$url2       = esc_url(
	add_query_arg(
		[
			'page' => 'opcm-viewer',
		],
		admin_url( 'tools.php' )
	)
);
$note       = sprintf(__('Note: <a href="%s">management tools</a> and <a href="%s">analytics reports</a> are available via the <strong>tools menu</strong>.', 'opcache-manager' ), $url1, $url2 );

?>

<div class="wrap">

	<h2><?php echo esc_html( sprintf( esc_html__( '%s Settings', 'opcache-manager' ), OPCM_PRODUCT_NAME ) ); ?></h2>
	<?php settings_errors(); ?>

	<h2 class="nav-tab-wrapper">
		<a href="
		<?php
		echo esc_url(
			add_query_arg(
				array(
					'page' => 'opcm-settings',
					'tab'  => 'misc',
				),
				admin_url( 'options-general.php' )
			)
		);
		?>
		" class="nav-tab <?php echo 'misc' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Options', 'opcache-manager' ); ?></a>
		<a href="
		<?php
		echo esc_url(
			add_query_arg(
				array(
					'page' => 'opcm-settings',
					'tab'  => 'about',
				),
				admin_url( 'options-general.php' )
			)
		);
		?>
		" class="nav-tab <?php echo 'about' === $active_tab ? 'nav-tab-active' : ''; ?>" style="float:right;"><?php esc_html_e( 'About', 'opcache-manager' ); ?></a>
	</h2>
    
	<?php if ( 'misc' === $active_tab ) { ?>
		<?php include __DIR__ . '/opcache-manager-admin-settings-options.php'; ?>
	<?php } ?>
	<?php if ( 'about' === $active_tab ) { ?>
		<?php include __DIR__ . '/opcache-manager-admin-settings-about.php'; ?>
	<?php } ?>

    <p>&nbsp;</p>
    <em><?php echo $note;?></em>
</div>

<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @link       https://wptelegram.pro
 * @since      1.0.0
 *
 * @package    WPTelegram\Pro
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) || ! WP_UNINSTALL_PLUGIN || dirname( WP_UNINSTALL_PLUGIN ) !== dirname( plugin_basename( __FILE__ ) ) ) {
	status_header( 404 );
	exit;
}

/**
 * Clean up data on uninstall.
 *
 * @return void
 */
function wptelegram_pro_uninstall() {

	$options = get_option( 'wptelegram_pro', '' );
	$options = json_decode( $options, true );

	if ( isset( $options['advanced']['clean_uninstall'] ) && ! $options['advanced']['clean_uninstall'] ) {
		return;
	}

	$uninstall_options = [
		'wptelegram_pro',
		'wptelegram_pro_ver',
	];

	$uninstall_options = (array) apply_filters( 'wptelegram_pro_uninstall_options', $uninstall_options );

	foreach ( $uninstall_options as $option ) {
		delete_option( $option );
	}

	$p2tg_instances = get_posts(
		[
			'post_type'   => 'wptgpro_p2tg',
			'numberposts' => -1,
		]
	);

	foreach ( $p2tg_instances as $p2tg_instance ) {
		wp_delete_post( $p2tg_instance->ID, true );
	}
}

wptelegram_pro_uninstall();

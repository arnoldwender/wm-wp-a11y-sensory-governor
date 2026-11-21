<?php
/**
 * WM Suite Accessibility Sensory Governor — Opt-In Uninstaller
 *
 * Triggered when the plugin is deleted via WordPress Admin.
 * Preserves stored options by default unless the site administrator explicitly
 * opted into data deletion (`wm_sensory_delete_data_on_uninstall` = '1').
 *
 * @package WMCyber\A11y\Governor
 */

declare(strict_types=1);

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$wm_sensory_opt_in = '1' === (string) get_option( 'wm_sensory_delete_data_on_uninstall', '0' )
	|| ( function_exists( 'get_site_option' ) && '1' === (string) get_site_option( 'wm_sensory_delete_data_on_uninstall', '0' ) );

if ( ! $wm_sensory_opt_in ) {
	return;
}

$wm_sensory_options = [
	'wm_sensory_focus_enabled',
	'wm_sensory_motion_enabled',
	'wm_sensory_ring_color',
	'wm_sensory_delete_data_on_uninstall',
];

foreach ( $wm_sensory_options as $wm_sensory_option ) {
	delete_option( $wm_sensory_option );
	if ( function_exists( 'delete_site_option' ) ) {
		delete_site_option( $wm_sensory_option );
	}
}

global $wpdb;
if ( isset( $wpdb ) && is_object( $wpdb ) && isset( $wpdb->options ) && method_exists( $wpdb, 'prepare' ) && method_exists( $wpdb, 'query' ) ) {
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			'_transient_wm_sensory_rl_%',
			'_transient_timeout_wm_sensory_rl_%'
		)
	);
}

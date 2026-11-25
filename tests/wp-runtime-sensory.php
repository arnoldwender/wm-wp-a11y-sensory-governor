<?php
/**
 * Runtime check of the contrast input, the settings save and the hub adapter inside a real WordPress.
 *
 *   wp eval-file tests/wp-runtime-sensory.php --user=1
 *
 * The standalone suite runs the same code against stubs; this file runs it through the real REST server, the
 * real options table and the real nonce. It posts each settings form once with the values already stored (so a
 * correct save changes nothing) and puts the three options back as it found them, including an absent one.
 * Nothing else is written. Exit 1 (WP_CLI::error) when a check fails.
 *
 * `wp eval-file` runs the file inside a method: state lives in $GLOBALS, and there is no declare() because
 * a declare after the docblock is fatal there.
 *
 * @package WMCyber\A11y\Governor
 */

defined( 'ABSPATH' ) || exit; // the local install serves the plugin folder over HTTP; this file is for wp eval-file only.

use WMCyber\A11y\Governor\Adapters\Sensory_Spoke_Adapter;
use WMCyber\A11y\Governor\Admin\AdminDashboard;
use WMCyber\A11y\Governor\Calculator\ContrastCalculator;
use WMCyber\A11y\Governor\Core\Plugin;
use WMCyber\A11y\Governor\Governor\FocusObscuredGovernor;

$GLOBALS['wm_sensory_rt'] = [ 'pass' => 0, 'fail' => 0 ];

function wm_sensory_rt_check( string $name, bool $ok, string $details = '' ): void {
	$GLOBALS['wm_sensory_rt'][ $ok ? 'pass' : 'fail' ]++;
	WP_CLI::log( ( $ok ? '[PASS] ' : '[FAIL] ' ) . $name . ( $ok || '' === $details ? '' : ': ' . $details ) );
}

function wm_sensory_rt_render( callable $fn ): string {
	ob_start();
	$fn();
	return (string) ob_get_clean();
}

function wm_sensory_rt_rest( string $fg, string $bg ): WP_REST_Response {
	$request = new WP_REST_Request( 'POST', '/wm-sensory/v1/calculate-contrast' );
	$request->set_param( 'foreground', $fg );
	$request->set_param( 'background', $bg );
	return rest_do_request( $request );
}

// save_settings() ends with wp_safe_redirect() + exit; the filter stops it before the exit so the check can go on.
// Priority 1 runs before WP-CLI's own wp_redirect handler, which would print a warning with a backtrace.
function wm_sensory_rt_post_form( array $post ): string {
	$_POST = $post + [ 'wm_sensory_save_nonce' => wp_create_nonce( 'wm_sensory_save_action' ) ];
	$stop  = static function (): void {
		throw new RuntimeException( 'redirect' );
	};
	add_filter( 'wp_redirect', $stop, 1 );
	try {
		AdminDashboard::instance()->save_settings();
		$outcome = 'no redirect';
	} catch ( RuntimeException $e ) {
		$outcome = $e->getMessage();
	} finally {
		remove_filter( 'wp_redirect', $stop, 1 );
		$_POST = [];
	}
	return $outcome;
}

if ( ! class_exists( Plugin::class ) ) {
	WP_CLI::error( 'wm-a11y-sensory-governor is not loaded.' );
}
if ( ! current_user_can( 'manage_options' ) ) {
	WP_CLI::error( 'Run with --user=<an administrator>.' );
}

// ---------------------------------------------------------------------------------------------------------------
// REST: an invalid colour is refused, a valid pair gets the verdict of the exact ratio.
// ---------------------------------------------------------------------------------------------------------------
$bad = wm_sensory_rt_rest( '#zzzzzz', '#ffffff' );
wm_sensory_rt_check( 'REST refuses "#zzzzzz" with 400', 400 === $bad->get_status() && false === ( $bad->get_data()['success'] ?? null ), $bad->get_status() . ' ' . wp_json_encode( $bad->get_data() ) );

$good = wm_sensory_rt_rest( '#0078d7', '#ffffff' );
$data = $good->get_data()['data'] ?? [];
wm_sensory_rt_check(
	'REST: #0078d7 on white is 4.49:1, large text only',
	200 === $good->get_status() && '4.49:1' === ( $data['ratio_string'] ?? '' ) && 'PASS_LARGE_ONLY' === ( $data['verdict'] ?? '' ) && false === ( $data['wcag_aa_normal'] ?? null ),
	wp_json_encode( $good->get_data() )
);

// ---------------------------------------------------------------------------------------------------------------
// WP-CLI: an invalid colour ends with an error.
// ---------------------------------------------------------------------------------------------------------------
$cli = WP_CLI::runcommand( 'a11y contrast "#zzzzzz" "#ffffff"', [ 'return' => 'all', 'exit_error' => false, 'launch' => true ] );
wm_sensory_rt_check( 'wp a11y contrast "#zzzzzz" exits 1 with the hex message', 1 === $cli->return_code && false !== strpos( $cli->stderr, 'hex codes' ), $cli->return_code . ' ' . trim( $cli->stderr . $cli->stdout ) );

// ---------------------------------------------------------------------------------------------------------------
// Calculator tab: the start result is computed, not a fixed number.
// ---------------------------------------------------------------------------------------------------------------
$expected_start = ( new ContrastCalculator() )->calculate_contrast( '#06b6d4', '#050811' )['ratio_string'];
$_GET['tab']    = 'calculator';
$calc_page      = wm_sensory_rt_render( static fn() => AdminDashboard::instance()->render_dashboard() );
unset( $_GET['tab'] );
wm_sensory_rt_check(
	'calculator tab opens with the computed ratio of its start colours',
	1 === preg_match( '#id="calc-ratio-val">' . preg_quote( $expected_start, '#' ) . '</div>#', $calc_page ) && false === strpos( $calc_page, '10.42' ),
	'expected ' . $expected_start
);

// ---------------------------------------------------------------------------------------------------------------
// Site head: the style block follows the options and uses the validated ring colour.
// ---------------------------------------------------------------------------------------------------------------
$focus_on  = get_option( 'wm_sensory_focus_enabled', '1' ) === '1';
$motion_on = get_option( 'wm_sensory_motion_enabled', '1' ) === '1';
$ring      = ( new FocusObscuredGovernor() )->get_ring_color();
$head      = wm_sensory_rt_render( static fn() => Plugin::instance()->inject_governor_styles() );
wm_sensory_rt_check(
	'site head prints the blocks of the governors that are on',
	$focus_on === ( false !== strpos( $head, 'scroll-margin-top' ) && false !== strpos( $head, '--wm-focus-ring-color: ' . $ring ) )
		&& $motion_on === ( false !== strpos( $head, 'prefers-reduced-motion' ) ),
	sprintf( 'focus %s, motion %s, ring %s', $focus_on ? 'on' : 'off', $motion_on ? 'on' : 'off', $ring )
);

// ---------------------------------------------------------------------------------------------------------------
// Settings: each form writes only its own fields. Posted with the stored values, so a correct save changes nothing.
// ---------------------------------------------------------------------------------------------------------------
$before = [
	'focus'  => get_option( 'wm_sensory_focus_enabled', null ),
	'motion' => get_option( 'wm_sensory_motion_enabled', null ),
	'ring'   => get_option( 'wm_sensory_ring_color', null ),
];
WP_CLI::log( 'options before: ' . wp_json_encode( $before ) );

$focus_post = [ 'wm_sensory_form' => 'focus', 'wm_sensory_ring_color' => $ring ];
if ( $focus_on ) {
	$focus_post['wm_sensory_focus_enabled'] = '1';
}
$outcome_focus = wm_sensory_rt_post_form( $focus_post );
$motion_after_focus_save = get_option( 'wm_sensory_motion_enabled', null );

$motion_post = [ 'wm_sensory_form' => 'motion' ];
if ( $motion_on ) {
	$motion_post['wm_sensory_motion_enabled'] = '1';
}
$outcome_motion = wm_sensory_rt_post_form( $motion_post );
$focus_after_motion_save = get_option( 'wm_sensory_focus_enabled', null );
$ring_after_motion_save  = get_option( 'wm_sensory_ring_color', null );

$outcome_none = wm_sensory_rt_post_form( [ 'wm_sensory_motion_enabled' => '1' ] );

// Put the options back as they were: a save creates an option that did not exist (the ring colour usually).
foreach ( [ 'focus' => 'wm_sensory_focus_enabled', 'motion' => 'wm_sensory_motion_enabled', 'ring' => 'wm_sensory_ring_color' ] as $key => $option ) {
	if ( null === $before[ $key ] ) {
		delete_option( $option );
	} else {
		update_option( $option, $before[ $key ] );
	}
}

wm_sensory_rt_check(
	'saving the focus tab leaves the motion governor as it was',
	'redirect' === $outcome_focus && ( $motion_on ? '1' : '0' ) === ( $motion_after_focus_save ?? ( $motion_on ? '1' : '0' ) ),
	$outcome_focus . ', motion ' . wp_json_encode( $motion_after_focus_save ) . ' (before ' . wp_json_encode( $before['motion'] ) . ')'
);
wm_sensory_rt_check(
	'saving the motion tab leaves the focus governor and the ring colour as they were',
	'redirect' === $outcome_motion && ( $focus_on ? '1' : '0' ) === $focus_after_motion_save && $ring === $ring_after_motion_save,
	$outcome_motion . ', focus ' . wp_json_encode( $focus_after_motion_save ) . ', ring ' . wp_json_encode( $ring_after_motion_save )
);
wm_sensory_rt_check( 'a post without form marker writes nothing', 'no redirect' === $outcome_none, $outcome_none );
wm_sensory_rt_check(
	'options after the check equal the options before',
	$before === [
		'focus'  => get_option( 'wm_sensory_focus_enabled', null ),
		'motion' => get_option( 'wm_sensory_motion_enabled', null ),
		'ring'   => get_option( 'wm_sensory_ring_color', null ),
	],
	'options now: ' . wp_json_encode( [ get_option( 'wm_sensory_focus_enabled', null ), get_option( 'wm_sensory_motion_enabled', null ), get_option( 'wm_sensory_ring_color', null ) ] )
);

// ---------------------------------------------------------------------------------------------------------------
// Hub adapter (only when the WM Suite Hub interface is loaded).
// ---------------------------------------------------------------------------------------------------------------
if ( ! class_exists( Sensory_Spoke_Adapter::class ) ) {
	WP_CLI::log( '[SKIP] hub adapter: WM Suite Hub interface not loaded' );
} else {
	$adapter = new Sensory_Spoke_Adapter();
	$tm      = $adapter->get_telemetry_metrics();
	$self    = $adapter->execute_action( 'self_test' );
	$invalid = $adapter->execute_action( 'calculate_contrast', [ 'fg' => '#zzzzzz', 'bg' => '#ffffff' ] );
	$props   = wp_json_encode( $adapter->get_sbom_data()['properties'] ?? [] );
	WP_CLI::log( 'hub telemetry ' . wp_json_encode( $tm ) );
	wm_sensory_rt_check(
		'hub telemetry: validated ring colour, 3:1 key, no compliance verdict',
		$ring === ( $tm['focus_ring_token'] ?? '' ) && isset( $tm['focus_ring_3_to_1_on_050811'] ) && ! isset( $tm['wcag_aa_compliant'] ),
		wp_json_encode( $tm )
	);
	wm_sensory_rt_check(
		'hub self_test passes and says so; invalid colour fails; SBOM without conformance claims',
		true === $self['success'] && 0 === strpos( $self['message'], 'Kontrastberechnung geprüft' ) && false === $invalid['success'] && ! preg_match( '/Compliant|BFSG|WCAG/i', $props ),
		wp_json_encode( [ $self, $invalid ] )
	);
}

WP_CLI::log( sprintf( '=== RUNTIME: %d passed, %d failed ===', $GLOBALS['wm_sensory_rt']['pass'], $GLOBALS['wm_sensory_rt']['fail'] ) );
if ( $GLOBALS['wm_sensory_rt']['fail'] > 0 ) {
	WP_CLI::error( 'runtime check failed' );
}

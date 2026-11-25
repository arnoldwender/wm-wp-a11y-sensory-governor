<?php
declare(strict_types=1);

namespace WMCyber\A11y\Governor\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WMCyber\A11y\Governor\Governor\FocusObscuredGovernor;
use WMCyber\A11y\Governor\Governor\VestibularMotionGovernor;
use WMCyber\A11y\Governor\Admin\AdminDashboard;
use WMCyber\A11y\Governor\API\RestController;
use WMCyber\A11y\Governor\CLI\SensoryCommand;
use WMCyber\A11y\Governor\Integrations\EcosystemBridge;

final class Plugin {
	private static ?Plugin $instance = null;

	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function boot(): void {
		add_action( 'init', function (): void {
			load_plugin_textdomain(
				'wm-a11y-sensory-governor',
				false,
				dirname( plugin_basename( WM_SENSORY_GOVERNOR_FILE ) ) . '/languages'
			);
		} );

		// Enqueue frontend dynamic token governor styles
		add_action( 'wp_head', [ $this, 'inject_governor_styles' ], 999 );

		// Block Bindings & GenerateBlocks ecosystem bridge
		( new EcosystemBridge() )->boot();

		// Admin Dashboard
		if ( is_admin() ) {
			AdminDashboard::instance()->boot();
		}

		// REST API
		add_action( 'rest_api_init', function (): void {
			( new RestController() )->register_routes();
		} );

		// WP-CLI
		if ( defined( 'WP_CLI' ) && \WP_CLI ) {
			\WP_CLI::add_command( 'a11y', SensoryCommand::class );
		}

		if ( function_exists( 'do_action' ) ) {
			do_action( 'wm_sensory_ready', $this );
		}
	}

	public function inject_governor_styles(): void {
		$focus_enabled  = get_option( 'wm_sensory_focus_enabled', '1' ) === '1';
		$motion_enabled = get_option( 'wm_sensory_motion_enabled', '1' ) === '1';

		if ( ! $focus_enabled && ! $motion_enabled ) {
			return;
		}

		$chunks = [];
		if ( $focus_enabled ) {
			$chunks[] = ( new FocusObscuredGovernor() )->get_focus_css();
		}
		if ( $motion_enabled ) {
			$chunks[] = ( new VestibularMotionGovernor() )->get_motion_css();
		}

		$css = implode( "\n", $chunks );
		if ( function_exists( 'apply_filters' ) ) {
			$css = (string) apply_filters( 'wm_sensory_tokens_css', $css, $focus_enabled, $motion_enabled );
		}

		if ( '' === trim( $css ) ) {
			return;
		}

		echo "<style id=\"wm-sensory-governor-tokens\">\n";
		// CSS, not markup: wp_strip_all_tags() is the escaping that fits a <style> block —
		// esc_html() would turn every > into an entity and break the selectors.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS, not markup; wp_strip_all_tags is the correct guard here and esc_html would break the selectors.
		echo wp_strip_all_tags( $css ) . "\n";
		echo "</style>\n";
	}
}

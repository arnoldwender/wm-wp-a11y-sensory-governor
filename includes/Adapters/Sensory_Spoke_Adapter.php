<?php
/**
 * WM Suite Accessibility Sensory Governor — Suite Hub Spoke Adapter
 *
 * Implements the WM Suite Hub contract (WM_Plugin_Module_Interface)
 * for the hub: telemetry with the governor switches and the ring colour, a contrast self-test and a contrast action.
 *
 * @package WMCyber\A11y\Governor
 */

declare(strict_types=1);

namespace WMCyber\A11y\Governor\Adapters;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! interface_exists( 'ArnoldWender\SuiteHub\Contracts\WM_Plugin_Module_Interface' ) ) {
	return;
}

use ArnoldWender\SuiteHub\Contracts\WM_Plugin_Module_Interface;
use WMCyber\A11y\Governor\Calculator\ContrastCalculator;
use WMCyber\A11y\Governor\Governor\FocusObscuredGovernor;

final class Sensory_Spoke_Adapter implements WM_Plugin_Module_Interface {

	public function get_module_slug(): string {
		return 'wm-a11y-sensory-governor';
	}

	public function get_name(): string {
		return 'WM Accessibility Sensory Governor';
	}

	public function get_version(): string {
		return defined( 'WM_SENSORY_GOVERNOR_VERSION' ) ? WM_SENSORY_GOVERNOR_VERSION : '1.2.2';
	}

	public function get_description(): string {
		return 'WCAG 2.2 Relative Luminance Calculator, Focus Not Obscured (2.4.11) & Vestibular Motion Governor.';
	}

	public function get_health_status(): string {
		return 'HEALTHY';
	}

	public function get_sbom_data(): array {
		return [
			'bom-ref'     => 'pkg:wordpress/wm-a11y-sensory-governor@' . $this->get_version(),
			'type'        => 'application',
			'name'        => $this->get_name(),
			'version'     => $this->get_version(),
			'description' => $this->get_description(),
			'scope'       => 'required',
			'hashes'      => [
				[
					'alg'     => 'SHA-256',
					'content' => hash_file( 'sha256', WM_SENSORY_GOVERNOR_FILE ),
				],
			],
			'licenses'    => [
				[
					'license' => [
						'id' => 'GPL-2.0-or-later',
					],
				],
			],
			// Features, not conformance: "WCAG 2.2 AA/AAA Compliant Tokens" and "BFSG 2025" stood here until 2026-09-15,
			// and nothing in the plugin tests a site against either.
			'properties'  => [
				[
					'name'  => 'wm:features:focus',
					'value' => 'focus-visible outline and scroll margins printed into the site head',
				],
				[
					'name'  => 'wm:features:motion',
					'value' => 'prefers-reduced-motion CSS printed into the site head',
				],
				[
					'name'  => 'wm:features:contrast',
					'value' => 'contrast ratio calculator (admin, REST, WP-CLI)',
				],
			],
		];
	}

	public function get_dependencies(): array {
		return [];
	}

	public function get_settings_url(): ?string {
		return admin_url( 'admin.php?page=wm-sensory-governor' );
	}

	public function get_telemetry_metrics(): array {
		$focus_enabled  = get_option( 'wm_sensory_focus_enabled', '1' ) === '1';
		$motion_enabled = get_option( 'wm_sensory_motion_enabled', '1' ) === '1';
		// The colour the stylesheet uses, not the raw option: an invalid stored value falls back to the default there.
		$ring_color     = ( new FocusObscuredGovernor() )->get_ring_color();
		$contrast       = ( new ContrastCalculator() )->calculate_contrast( $ring_color, '#050811' );

		return [
			'focus_obscured_governor'      => $focus_enabled ? 'ENFORCED' : 'DISABLED',
			'vestibular_motion_clamp'      => $motion_enabled ? 'ACTIVE' : 'DISABLED',
			'focus_ring_token'             => $ring_color,
			'focus_ring_dark_contrast'     => $contrast['ratio_string'],
			// 3:1 is the threshold for a focus indicator (non-text contrast) against the dark admin background only; it
			// says nothing about the site's own background. Was "wcag_aa_compliant" with the 4.5:1 text threshold.
			'focus_ring_3_to_1_on_050811'  => $contrast['ui_components'] ? 'YES' : 'NO',
		];
	}

	public function execute_action( string $action, array $params = [] ): array {
		$calc = new ContrastCalculator();

		switch ( $action ) {
			case 'self_test':
				$result = $calc->calculate_contrast( '#ffffff', '#000000' );
				$pass   = ( $result['ratio'] >= 21.0 );
				// The message said "erfolgreich" whatever $pass was (until 2026-09-15).
				return [
					'success' => $pass,
					'message' => $pass
						? sprintf( 'Kontrastberechnung geprüft: Ratio %s (erwartet 21:1 für Schwarz/Weiß).', $result['ratio_string'] )
						: sprintf( 'Kontrastberechnung fehlerhaft: Ratio %s statt 21:1 für Schwarz/Weiß.', $result['ratio_string'] ),
				];

			case 'calculate_contrast':
				$fg = $params['fg'] ?? '#ffffff';
				$bg = $params['bg'] ?? '#050811';
				try {
					$result = $calc->calculate_contrast( (string) $fg, (string) $bg );
				} catch ( \InvalidArgumentException $e ) {
					return [ 'success' => false, 'message' => $e->getMessage() ];
				}
				return [
					'success' => true,
					'message' => sprintf( 'Kontrast zwischen %s und %s ist %s (Verdict: %s).', esc_html( $fg ), esc_html( $bg ), $result['ratio_string'], $result['verdict'] ),
				];

			default:
				return [
					'success' => false,
					'message' => sprintf( 'Unbekannte Spoke-Aktion "%s".', esc_html( $action ) ),
				];
		}
	}
}

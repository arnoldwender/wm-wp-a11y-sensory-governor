<?php
/**
 * Mutations for tests/run-mutations.sh: each entry undoes one correction with literal replacements
 * [ file, search, replace ]; tests/test-suite.php must turn red for every one of them.
 *
 * Single-quoted strings only: a double-quoted search string would interpolate "$" and never match.
 *
 * @package WMCyber\A11y\Governor
 */

return [
	[
		'any string accepted as a colour again',
		[
			[ 'includes/Calculator/ContrastCalculator.php', '$fg = self::normalize_hex( $foreground_hex );', '$fg = self::normalize_hex( $foreground_hex ) ?? \'#000000\';' ],
			[ 'includes/Calculator/ContrastCalculator.php', '$bg = self::normalize_hex( $background_hex );', '$bg = self::normalize_hex( $background_hex ) ?? \'#000000\';' ],
		],
	],
	[
		'verdicts from the rounded ratio again',
		[
			[ 'includes/Calculator/ContrastCalculator.php', '\'wcag_aa_normal\'  => $ratio >= 4.5,', '\'wcag_aa_normal\'  => round( $ratio, 2 ) >= 4.5,' ],
		],
	],
	[
		'the shown ratio rounded up again',
		[
			[ 'includes/Calculator/ContrastCalculator.php', '$shown = floor( $ratio * 100 + 1e-9 ) / 100;', '$shown = round( $ratio, 2 );' ],
		],
	],
	[
		'saving the focus tab switches the motion governor off again',
		[
			[ 'includes/Admin/AdminDashboard.php', 'if ( \'focus\' === $form ) {', 'if ( \'focus\' === $form ) { update_option( \'wm_sensory_motion_enabled\', isset( $_POST[\'wm_sensory_motion_enabled\'] ) ? \'1\' : \'0\' );' ],
		],
	],
	[
		'a post without form marker writes the options again',
		[
			[ 'includes/Admin/AdminDashboard.php', '} else {' . "\n\t\t\t" . 'return;' . "\n\t\t" . '}', '} else { update_option( \'wm_sensory_focus_enabled\', \'0\' ); }' ],
		],
	],
	[
		'the focus form loses its marker',
		[
			[ 'includes/Admin/AdminDashboard.php', '<input type="hidden" name="wm_sensory_form" value="focus" />', '' ],
		],
	],
	[
		'the calculator opens with a fixed ratio again',
		[
			[ 'includes/Admin/AdminDashboard.php', 'id="calc-ratio-val"><?php echo esc_html( $calc_start[\'ratio_string\'] ); ?></div>', 'id="calc-ratio-val">10.42:1</div>' ],
		],
	],
	[
		'telemetry reports the raw ring option again',
		[
			[ 'includes/Adapters/Sensory_Spoke_Adapter.php', '$ring_color     = ( new FocusObscuredGovernor() )->get_ring_color();', '$ring_color     = (string) get_option( \'wm_sensory_ring_color\', \'#06b6d4\' );' ],
		],
	],
	[
		'self_test says erfolgreich whatever the result',
		[
			[ 'includes/Adapters/Sensory_Spoke_Adapter.php', ': sprintf( \'Kontrastberechnung fehlerhaft: Ratio %s statt 21:1 für Schwarz/Weiß.\', $result[\'ratio_string\'] ),', ': sprintf( \'Kontrastberechnung geprüft: Ratio %s (erwartet 21:1 für Schwarz/Weiß).\', $result[\'ratio_string\'] ),' ],
		],
	],
	[
		'the SBOM declares WCAG and BFSG conformance again',
		[
			[ 'includes/Adapters/Sensory_Spoke_Adapter.php', '\'name\'  => \'wm:features:focus\',', '\'name\'  => \'wm:accessibility:BFSG\',' ],
		],
	],
	[
		'REST answers an invalid colour with a verdict again',
		[
			[ 'includes/API/RestController.php', 'return new WP_REST_Response( [ \'success\' => false, \'error\' => $e->getMessage() ], 400 );', 'return new WP_REST_Response( [ \'success\' => true, \'data\' => [ \'ratio_string\' => \'21:1\' ] ], 200 );' ],
		],
	],
	[
		'uninstall deletes options without opt-in again',
		[
			[ 'uninstall.php', 'if ( ! $wm_sensory_opt_in ) {', 'if ( false && ! $wm_sensory_opt_in ) {' ],
		],
	],
	[
		'REST rate limiter stops enforcing the request threshold',
		[
			[ 'includes/API/RestController.php', 'if ( $hits >= $limit ) {', 'if ( false && $hits >= $limit ) {' ],
		],
	],
	[
		'saving the lifecycle form resets the focus governor',
		[
			[ 'includes/Admin/AdminDashboard.php', '} elseif ( \'lifecycle\' === $form ) {', '} elseif ( \'lifecycle\' === $form ) { update_option( \'wm_sensory_focus_enabled\', \'0\' );' ],
		],
	],
	[
		'Block Bindings and GenerateBlocks bridge stops resolving ring_color',
		[
			[ 'includes/Integrations/EcosystemBridge.php', 'case \'ring_color\':', 'case \'ring_color_broken\':' ],
		],
	],
];

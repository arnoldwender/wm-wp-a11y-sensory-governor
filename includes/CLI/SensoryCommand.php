<?php
declare(strict_types=1);

namespace WMCyber\A11y\Governor\CLI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WMCyber\A11y\Governor\Calculator\ContrastCalculator;
use WP_CLI;

final class SensoryCommand {
	/**
	 * Calculate mathematical Relative Luminance contrast between two colors under WCAG 2.2 AA/AAA.
	 *
	 * ## OPTIONS
	 *
	 * <foreground>
	 * : Foreground hex code (e.g. #ffffff).
	 *
	 * <background>
	 * : Background hex code (e.g. #050811).
	 *
	 * ## EXAMPLES
	 *
	 *     wp a11y contrast #ffffff #050811
	 *     wp a11y contrast #94a3b8 #090d16
	 *
	 * @when after_wp_load
	 */
	public function contrast( array $args, array $assoc_args ): void {
		$fg = $args[0] ?? '#ffffff';
		$bg = $args[1] ?? '#050811';

		try {
			$res = ( new ContrastCalculator() )->calculate_contrast( $fg, $bg );
		} catch ( \InvalidArgumentException $e ) {
			WP_CLI::error( $e->getMessage() );
			return;
		}

		WP_CLI::line( WP_CLI::colorize( "%C=== WM A11y Relative Luminance Contrast Calculator ===%n" ) );
		WP_CLI::line( "Foreground: {$fg}" );
		WP_CLI::line( "Background: {$bg}" );
		WP_CLI::line( "Contrast Ratio: " . WP_CLI::colorize( "%G" . $res['ratio_string'] . "%n" ) );
		WP_CLI::line( "• WCAG 2.2 AA Normal Text (≥4.5:1): " . ( $res['wcag_aa_normal'] ? WP_CLI::colorize( "%GPASS%n" ) : WP_CLI::colorize( "%RFAIL%n" ) ) );
		WP_CLI::line( "• WCAG 2.2 AA Large Text (≥3.0:1):  " . ( $res['wcag_aa_large'] ? WP_CLI::colorize( "%GPASS%n" ) : WP_CLI::colorize( "%RFAIL%n" ) ) );
		WP_CLI::line( "• WCAG 2.2 AAA Normal (≥7.0:1):     " . ( $res['wcag_aaa_normal'] ? WP_CLI::colorize( "%GPASS%n" ) : WP_CLI::colorize( "%YWARN%n" ) ) );
		WP_CLI::line( "• Focus Ring / UI (≥3.0:1):         " . ( $res['ui_components'] ? WP_CLI::colorize( "%GPASS%n" ) : WP_CLI::colorize( "%RFAIL%n" ) ) );
	}
}

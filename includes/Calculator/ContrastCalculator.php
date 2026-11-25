<?php
declare(strict_types=1);

namespace WMCyber\A11y\Governor\Calculator;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ContrastCalculator {
	/**
	 * A colour as #rrggbb, or null when the value is not a 3- or 6-digit hex code.
	 *
	 * Until 2026-09-15 anything was accepted: "#zzzzzz" parsed with hexdec() to black and scored 21:1 PASS_AA
	 * against white, a wrong length silently became black, and "red" returned 1.47:1.
	 */
	public static function normalize_hex( string $value ): ?string {
		$value = strtolower( trim( $value ) );
		if ( 1 !== preg_match( '/^#?([0-9a-f]{3}|[0-9a-f]{6})$/', $value, $m ) ) {
			return null;
		}
		$hex = $m[1];
		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		return '#' . $hex;
	}

	/**
	 * Compute Relative Luminance and Contrast Ratio between foreground and background hex colors.
	 *
	 * The verdicts use the exact ratio. The ratio shown is truncated to two decimals, so it never exceeds the real
	 * one: #0078d7 on #ffffff is 4.4989:1, which was rounded to 4.5:1 and passed AA until 2026-09-15.
	 *
	 * @throws \InvalidArgumentException When a colour is not a 3- or 6-digit hex code.
	 */
	public function calculate_contrast( string $foreground_hex, string $background_hex ): array {
		$fg = self::normalize_hex( $foreground_hex );
		$bg = self::normalize_hex( $background_hex );
		if ( null === $fg || null === $bg ) {
			throw new \InvalidArgumentException( 'Colours must be hex codes such as #ffffff or #fff.' );
		}

		$fg_rgb = $this->hex_to_rgb( $fg );
		$bg_rgb = $this->hex_to_rgb( $bg );

		$fg_lum = $this->relative_luminance( $fg_rgb['r'], $fg_rgb['g'], $fg_rgb['b'] );
		$bg_lum = $this->relative_luminance( $bg_rgb['r'], $bg_rgb['g'], $bg_rgb['b'] );

		$l1 = max( $fg_lum, $bg_lum );
		$l2 = min( $fg_lum, $bg_lum );

		$ratio = ( $l1 + 0.05 ) / ( $l2 + 0.05 );
		// The small epsilon keeps float noise (8.2399999…) from cutting a digit the real ratio has.
		$shown = floor( $ratio * 100 + 1e-9 ) / 100;

		$result = [
			'ratio'           => $shown,
			'ratio_exact'     => $ratio,
			'ratio_string'    => "{$shown}:1",
			'wcag_aa_normal'  => $ratio >= 4.5,
			'wcag_aa_large'   => $ratio >= 3.0,
			'wcag_aaa_normal' => $ratio >= 7.0,
			'wcag_aaa_large'  => $ratio >= 4.5,
			'ui_components'   => $ratio >= 3.0,
			'verdict'         => $ratio >= 4.5 ? 'PASS_AA' : ( $ratio >= 3.0 ? 'PASS_LARGE_ONLY' : 'FAIL' ),
		];

		if ( function_exists( 'apply_filters' ) ) {
			$filtered = apply_filters( 'wm_sensory_contrast_result', $result, $fg, $bg );
			if ( is_array( $filtered ) ) {
				$result = $filtered;
			}
		}

		return $result;
	}

	/**
	 * @param string $hex A value normalize_hex() returned (#rrggbb).
	 * @return array{r: int, g: int, b: int}
	 */
	private function hex_to_rgb( string $hex ): array {
		return [
			'r' => (int) hexdec( substr( $hex, 1, 2 ) ),
			'g' => (int) hexdec( substr( $hex, 3, 2 ) ),
			'b' => (int) hexdec( substr( $hex, 5, 2 ) ),
		];
	}

	private function relative_luminance( float $r, float $g, float $b ): float {
		$rs = $r / 255.0;
		$gs = $g / 255.0;
		$bs = $b / 255.0;

		$r_lin = ( $rs <= 0.04045 ) ? ( $rs / 12.92 ) : pow( ( $rs + 0.055 ) / 1.055, 2.4 );
		$g_lin = ( $gs <= 0.04045 ) ? ( $gs / 12.92 ) : pow( ( $gs + 0.055 ) / 1.055, 2.4 );
		$b_lin = ( $bs <= 0.04045 ) ? ( $bs / 12.92 ) : pow( ( $bs + 0.055 ) / 1.055, 2.4 );

		return 0.2126 * $r_lin + 0.7152 * $g_lin + 0.0722 * $b_lin;
	}
}

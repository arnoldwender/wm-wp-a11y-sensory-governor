<?php
declare(strict_types=1);

namespace WMCyber\A11y\Governor\Governor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class FocusObscuredGovernor {
	public const DEFAULT_RING_COLOR = '#06b6d4';

	/**
	 * The focus-ring colour the settings page stores. Until 2026-09-14 the option was saved and
	 * the generated CSS kept #06b6d4 regardless; the value is validated here so nothing but a
	 * six-digit hex colour ever reaches the stylesheet.
	 */
	public function get_ring_color(): string {
		$ring = (string) get_option( 'wm_sensory_ring_color', self::DEFAULT_RING_COLOR );
		return preg_match( '/^#[0-9a-f]{6}$/i', $ring ) ? strtolower( $ring ) : self::DEFAULT_RING_COLOR;
	}

	/**
	 * Return the dynamic CSS rules enforcing WCAG 2.2 2.4.11 Focus Not Obscured (Minimum).
	 */
	public function get_focus_css(): string {
		$ring = $this->get_ring_color();
		// The glow around the ring is the ring colour at 35 % alpha.
		[ $r, $g, $b ] = sscanf( $ring, '#%02x%02x%02x' );

		// One array entry per CSS line: Plugin Check rejects heredoc syntax in distributed code.
		return implode(
			"\n",
			[
				'/* WM A11y Sensory Governor — WCAG 2.2 2.4.11 Focus Not Obscured & High-Visibility Focus Ring */',
				':root {',
				'  --wm-focus-offset: 96px;',
				'  --wm-focus-ring-color: ' . $ring . ';',
				'  --wm-focus-ring-width: 3px;',
				'  --wm-focus-ring-offset: 2px;',
				'}',
				'',
				':focus-visible,',
				'a:focus-visible,',
				'button:focus-visible,',
				'input:focus-visible,',
				'textarea:focus-visible,',
				'select:focus-visible,',
				'summary:focus-visible,',
				'[tabindex]:focus-visible {',
				'  scroll-margin-top: var(--wm-focus-offset, 96px) !important;',
				'  scroll-margin-bottom: 48px !important;',
				'  outline: var(--wm-focus-ring-width) solid var(--wm-focus-ring-color) !important;',
				'  outline-offset: var(--wm-focus-ring-offset) !important;',
				'  box-shadow: 0 0 0 4px rgba(' . $r . ', ' . $g . ', ' . $b . ', 0.35) !important;',
				'  transition: outline-offset 0.1s ease-in-out !important;',
				'}',
			]
		);
	}
}

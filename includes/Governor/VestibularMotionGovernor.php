<?php
declare(strict_types=1);

namespace WMCyber\A11y\Governor\Governor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class VestibularMotionGovernor {
	/**
	 * Return dynamic CSS rules suppressing hazardous vestibular motions when reduced motion is preferred.
	 */
	public function get_motion_css(): string {
		// One array entry per CSS line: Plugin Check rejects heredoc syntax in distributed code.
		return implode(
			"\n",
			[
				'/* WM A11y Sensory Governor — WCAG 2.2 2.3.3 Animation from Interactions & Vestibular Safety */',
				'@media (prefers-reduced-motion: reduce) {',
				'  *,',
				'  *::before,',
				'  *::after {',
				'    animation-duration: 0.001ms !important;',
				'    animation-iteration-count: 1 !important;',
				'    transition-duration: 0.001ms !important;',
				'    scroll-behavior: auto !important;',
				'  }',
				'',
				'  .wm-parallax,',
				'  .hero-animation,',
				'  .marquee-track,',
				'  .splide__track {',
				'    animation: none !important;',
				'    transform: none !important;',
				'  }',
				'}',
			]
		);
	}
}

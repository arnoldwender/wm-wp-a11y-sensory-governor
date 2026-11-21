<?php
declare(strict_types=1);

namespace WMCyber\A11y\Governor\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WMCyber\A11y\Governor\Calculator\ContrastCalculator;
use WMCyber\A11y\Governor\Governor\FocusObscuredGovernor;

/**
 * WordPress 6.5+ Block Bindings (`wm-sensory/tokens`) and GenerateBlocks 2.x
 * Dynamic Tag (`{{wm_sensory_token}}`) integration bridge.
 */
final class EcosystemBridge {
	public function boot(): void {
		add_action( 'init', [ $this, 'register_block_bindings' ], 20 );
		add_action( 'init', [ $this, 'register_generateblocks_tag' ], 20 );
		add_filter( 'generateblocks_dynamic_tag_output', [ $this, 'filter_generateblocks_tag_output' ], 10, 3 );
	}

	public function register_block_bindings(): void {
		if ( ! function_exists( 'register_block_bindings_source' ) ) {
			return;
		}

		register_block_bindings_source(
			'wm-sensory/tokens',
			[
				'label'              => __( 'WM Sensory Governor Tokens', 'wm-a11y-sensory-governor' ),
				'get_value_callback' => [ $this, 'resolve_block_binding' ],
				'uses_context'       => [],
			]
		);
	}

	public function register_generateblocks_tag(): void {
		if ( ! class_exists( 'GenerateBlocks_Register_Dynamic_Tag' ) ) {
			return;
		}

		new \GenerateBlocks_Register_Dynamic_Tag(
			[
				'title'       => __( 'WM Sensory Token', 'wm-a11y-sensory-governor' ),
				'tag'         => 'wm_sensory_token',
				'type'        => 'wm-a11y-sensory-governor',
				'supports'    => [],
				'description' => __( 'Outputs the active focus-ring hex colour, contrast ratio, or governor state.', 'wm-a11y-sensory-governor' ),
				'options'     => [
					'key' => [
						'type'    => 'select',
						'label'   => __( 'Token Key', 'wm-a11y-sensory-governor' ),
						'default' => 'ring_color',
						'options' => [
							[
								'value' => 'ring_color',
								'label' => __( 'Focus Ring Colour', 'wm-a11y-sensory-governor' ),
							],
							[
								'value' => 'ring_contrast',
								'label' => __( 'Focus Ring Dark Contrast', 'wm-a11y-sensory-governor' ),
							],
							[
								'value' => 'focus_status',
								'label' => __( 'Focus Governor Status', 'wm-a11y-sensory-governor' ),
							],
							[
								'value' => 'motion_status',
								'label' => __( 'Motion Governor Status', 'wm-a11y-sensory-governor' ),
							],
						],
					],
				],
				'return'      => [ $this, 'render_generateblocks_tag' ],
			]
		);
	}

	public function resolve_token_value( string $key ): ?string {
		switch ( $key ) {
			case 'ring_color':
				return ( new FocusObscuredGovernor() )->get_ring_color();

			case 'ring_contrast':
				$ring = ( new FocusObscuredGovernor() )->get_ring_color();
				return ( new ContrastCalculator() )->calculate_contrast( $ring, '#050811' )['ratio_string'];

			case 'focus_status':
				return get_option( 'wm_sensory_focus_enabled', '1' ) === '1'
					? __( 'Active', 'wm-a11y-sensory-governor' )
					: __( 'Disabled', 'wm-a11y-sensory-governor' );

			case 'motion_status':
				return get_option( 'wm_sensory_motion_enabled', '1' ) === '1'
					? __( 'Active', 'wm-a11y-sensory-governor' )
					: __( 'Disabled', 'wm-a11y-sensory-governor' );

			default:
				return null;
		}
	}

	/**
	 * Callback for WP 6.5+ Block Bindings source `wm-sensory/tokens`.
	 *
	 * @param array<string, mixed> $source_args    Binding source arguments (`key`).
	 * @param mixed                $block_instance Block instance.
	 */
	public function resolve_block_binding( array $source_args, $block_instance = null, string $attribute_name = '' ): ?string {
		$raw_key = $source_args['key'] ?? 'ring_color';
		$key     = is_scalar( $raw_key ) ? sanitize_key( (string) $raw_key ) : 'ring_color';
		return $this->resolve_token_value( $key );
	}

	/**
	 * Callback for GenerateBlocks 2.x Dynamic Tag `{{wm_sensory_token}}`.
	 *
	 * @param array<string, mixed> $options Tag options.
	 * @param array<string, mixed> $block   Block array.
	 * @param mixed                $instance Block instance.
	 */
	public function render_generateblocks_tag( array $options = [], array $block = [], $instance = null ): string {
		$raw_key = $options['key'] ?? 'ring_color';
		$key     = is_scalar( $raw_key ) ? sanitize_key( (string) $raw_key ) : 'ring_color';
		$value   = $this->resolve_token_value( $key );
		return null !== $value ? esc_html( $value ) : '';
	}

	/**
	 * Filter fallback for `generateblocks_dynamic_tag_output`.
	 *
	 * @param mixed                $output  Current output.
	 * @param string               $tag     Dynamic tag name.
	 * @param array<string, mixed> $options Tag options.
	 * @return mixed
	 */
	public function filter_generateblocks_tag_output( $output, string $tag = '', array $options = [] ) {
		if ( 'wm_sensory_token' !== $tag ) {
			return $output;
		}
		return $this->render_generateblocks_tag( $options );
	}
}

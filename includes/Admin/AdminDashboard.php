<?php
declare(strict_types=1);

namespace WMCyber\A11y\Governor\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WMCyber\A11y\Governor\Governor\FocusObscuredGovernor;
use WMCyber\A11y\Governor\Calculator\ContrastCalculator;

final class AdminDashboard {
	private static ?AdminDashboard $instance = null;

	public static function instance(): AdminDashboard {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function boot(): void {
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_init', [ $this, 'save_settings' ] );
		add_action( 'wp_dashboard_setup', [ $this, 'register_dashboard_widget' ] );
	}

	public function register_settings(): void {
		if ( ! function_exists( 'register_setting' ) ) {
			return;
		}

		$bool_flag = static fn( $val ): string => ( '1' === (string) $val || true === $val ) ? '1' : '0';

		register_setting(
			'wm_sensory_governor_settings',
			'wm_sensory_focus_enabled',
			[
				'type'              => 'string',
				'sanitize_callback' => $bool_flag,
				'default'           => '1',
			]
		);
		register_setting(
			'wm_sensory_governor_settings',
			'wm_sensory_motion_enabled',
			[
				'type'              => 'string',
				'sanitize_callback' => $bool_flag,
				'default'           => '1',
			]
		);
		register_setting(
			'wm_sensory_governor_settings',
			'wm_sensory_ring_color',
			[
				'type'              => 'string',
				'sanitize_callback' => static function ( $val ): string {
					$clean = is_scalar( $val ) ? sanitize_hex_color( (string) $val ) : null;
					return $clean ? strtolower( $clean ) : FocusObscuredGovernor::DEFAULT_RING_COLOR;
				},
				'default'           => FocusObscuredGovernor::DEFAULT_RING_COLOR,
			]
		);
		register_setting(
			'wm_sensory_governor_settings',
			'wm_sensory_delete_data_on_uninstall',
			[
				'type'              => 'string',
				'sanitize_callback' => $bool_flag,
				'default'           => '0',
			]
		);
	}

	public function register_dashboard_widget(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_add_dashboard_widget(
			'wm_sensory_governor_widget',
			__( 'WM Sensory Governor — Fokus & Bewegung', 'wm-a11y-sensory-governor' ),
			[ $this, 'render_dashboard_widget' ]
		);
	}

	/**
	 * Dashboard widget: the state of the two governors. Until 2026-09-14 it also showed an
	 * "Audio Guard — 0 Autoplay" tile for a guard the plugin does not have.
	 */
	public function render_dashboard_widget(): void {
		$focus_enabled  = get_option( 'wm_sensory_focus_enabled', '1' ) === '1';
		$motion_enabled = get_option( 'wm_sensory_motion_enabled', '1' ) === '1';
		$any_on         = $focus_enabled || $motion_enabled;
		?>
		<div class="wm-dash-widget-box" data-wm-theme="<?php echo esc_attr( function_exists( 'wm_get_suite_theme' ) ? wm_get_suite_theme() : 'cyber-events' ); ?>">
			<div class="wm-dash-banner <?php echo $any_on ? 'is-valid' : ''; ?>">
				<span class="wm-dash-banner-icon"><span class="dashicons dashicons-visibility" aria-hidden="true"></span></span>
				<div>
					<div class="wm-dash-banner-title">
						<?php echo $any_on ? esc_html__( 'Sensorischer Schutz aktiv', 'wm-a11y-sensory-governor' ) : esc_html__( 'Sensorischer Schutz aus', 'wm-a11y-sensory-governor' ); ?>
					</div>
					<div class="wm-dash-banner-sub"><?php esc_html_e( 'Fokus-Ring und Reduced-Motion-CSS im Frontend (WCAG 2.2 SC 2.4.11 und 2.3.3)', 'wm-a11y-sensory-governor' ); ?></div>
				</div>
			</div>

			<div class="wm-dash-kpi-grid">
				<div class="wm-dash-kpi-card">
					<span class="wm-dash-kpi-val <?php echo $motion_enabled ? 'text-emerald' : ''; ?>"><?php echo $motion_enabled ? esc_html__( 'Aktiv', 'wm-a11y-sensory-governor' ) : esc_html__( 'Aus', 'wm-a11y-sensory-governor' ); ?></span>
					<span class="wm-dash-kpi-lbl"><?php esc_html_e( 'Motion Guard', 'wm-a11y-sensory-governor' ); ?></span>
				</div>
				<div class="wm-dash-kpi-card">
					<span class="wm-dash-kpi-val <?php echo $focus_enabled ? 'text-emerald' : ''; ?>"><?php echo $focus_enabled ? esc_html__( 'Aktiv', 'wm-a11y-sensory-governor' ) : esc_html__( 'Aus', 'wm-a11y-sensory-governor' ); ?></span>
					<span class="wm-dash-kpi-lbl"><?php esc_html_e( 'Fokus-Ring', 'wm-a11y-sensory-governor' ); ?></span>
				</div>
			</div>

			<div class="wm-dash-actions">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wm-sensory-governor' ) ); ?>" class="wm-dash-btn">
					<span><?php esc_html_e( 'Governor konfigurieren', 'wm-a11y-sensory-governor' ); ?></span> <span class="dashicons dashicons-arrow-right-alt" aria-hidden="true"></span>
				</a>
			</div>
		</div>
		<?php
	}

	/**
	 * Family menu icon: a monochrome hub-and-spoke mark, filled shapes only so the WordPress
	 * svg-painter can recolour it with the admin colour scheme. Placeholder until the family
	 * icon is decided (hackaton plan D12).
	 */
	public static function family_menu_icon(): string {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><circle cx="10" cy="10" r="2.6" fill="black"/><circle cx="10" cy="3" r="1.8" fill="black"/><circle cx="16.1" cy="13.5" r="1.8" fill="black"/><circle cx="3.9" cy="13.5" r="1.8" fill="black"/><rect x="9.3" y="4.5" width="1.4" height="3.4" fill="black"/><rect x="9.3" y="12.1" width="1.4" height="3.4" fill="black" transform="rotate(-60 10 10)"/><rect x="9.3" y="12.1" width="1.4" height="3.4" fill="black" transform="rotate(60 10 10)"/></svg>';
		return 'data:image/svg+xml;base64,' . base64_encode( $svg );
	}

	public function register_menu(): void {
		add_menu_page(
			__( 'WM Sensory Governor', 'wm-a11y-sensory-governor' ),
			__( 'Sensory Governor', 'wm-a11y-sensory-governor' ),
			'manage_options',
			'wm-sensory-governor',
			[ $this, 'render_dashboard' ],
			self::family_menu_icon(),
			83
		);
	}

	/**
	 * The two family sheets (byte-identical copies of the hub's canonical files, pinned by
	 * tests/test-ui-family.php). Same handles in every WM plugin, so WordPress loads one copy
	 * however many are active; versioned by file time so a browser never keeps a stale sheet.
	 *
	 * Loaded on the plugin's own screen — "toplevel_page_wm-sensory-governor" standalone, and the
	 * hub keeps that hook after it moves the menu under "WM Suite" — and on the dashboard,
	 * where the widget renders (until 2026-09-14 the widget had no sheet at all).
	 */
	public function enqueue_assets( string $hook ): void {
		if ( ! str_contains( $hook, 'wm-sensory-governor' ) && 'index.php' !== $hook ) {
			return;
		}

		$root = dirname( __DIR__, 2 ) . '/assets/css/';
		wp_enqueue_style(
			'wm-admin-tokens',
			WM_SENSORY_GOVERNOR_URL . 'assets/css/wm-admin-tokens.css',
			[],
			(string) ( @filemtime( $root . 'wm-admin-tokens.css' ) ?: WM_SENSORY_GOVERNOR_VERSION )
		);
		wp_enqueue_style(
			'wm-admin-ui',
			WM_SENSORY_GOVERNOR_URL . 'assets/css/wm-admin-ui.css',
			[ 'wm-admin-tokens' ],
			(string) ( @filemtime( $root . 'wm-admin-ui.css' ) ?: WM_SENSORY_GOVERNOR_VERSION )
		);
	}

	public function save_settings(): void {
		$nonce = isset( $_POST['wm_sensory_save_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['wm_sensory_save_nonce'] ) ) : '';
		if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'wm_sensory_save_action' ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Each tab has its own form. Until 2026-09-15 every save wrote all three options: saving the focus tab switched
		// the motion governor off (its checkbox was not in that form), and saving the motion tab switched the focus
		// governor off and reset the ring colour. Only the fields of the posted form are written now.
		$form = isset( $_POST['wm_sensory_form'] ) ? sanitize_key( wp_unslash( $_POST['wm_sensory_form'] ) ) : '';
		if ( 'focus' === $form ) {
			update_option( 'wm_sensory_focus_enabled', isset( $_POST['wm_sensory_focus_enabled'] ) ? '1' : '0' );
			$ring = isset( $_POST['wm_sensory_ring_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['wm_sensory_ring_color'] ) ) : null;
			update_option( 'wm_sensory_ring_color', $ring ?: FocusObscuredGovernor::DEFAULT_RING_COLOR );
		} elseif ( 'motion' === $form ) {
			update_option( 'wm_sensory_motion_enabled', isset( $_POST['wm_sensory_motion_enabled'] ) ? '1' : '0' );
		} elseif ( 'lifecycle' === $form ) {
			update_option( 'wm_sensory_delete_data_on_uninstall', isset( $_POST['wm_sensory_delete_data_on_uninstall'] ) ? '1' : '0' );
		} else {
			return;
		}

		wp_safe_redirect( add_query_arg( [ 'page' => 'wm-sensory-governor', 'updated' => 'true' ], admin_url( 'admin.php' ) ) );
		exit;
	}

	public function render_dashboard(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only tab routing, nothing is changed; the value is checked against the tab list below.
		$active_tab     = sanitize_key( wp_unslash( $_GET['tab'] ?? 'telemetry' ) );
		$focus_enabled       = get_option( 'wm_sensory_focus_enabled', '1' ) === '1';
		$motion_enabled      = get_option( 'wm_sensory_motion_enabled', '1' ) === '1';
		$delete_on_uninstall = get_option( 'wm_sensory_delete_data_on_uninstall', '0' ) === '1';
		$ring_color          = ( new FocusObscuredGovernor() )->get_ring_color();
		// The calculator opens with the real result for its two start colours; it showed a fixed 10.42:1 with four
		// PASS badges until 2026-09-15, while the pair is 8.24:1.
		$calc_fg        = FocusObscuredGovernor::DEFAULT_RING_COLOR;
		$calc_bg        = '#050811';
		$calc_start     = ( new ContrastCalculator() )->calculate_contrast( $calc_fg, $calc_bg );
		$badge          = static fn( bool $pass ): string => $pass ? 'wm-badge-green' : 'wm-badge-red';
		$verdict        = static fn( bool $pass ): string => $pass ? __( 'PASS', 'wm-a11y-sensory-governor' ) : __( 'FAIL', 'wm-a11y-sensory-governor' );

		$wm_hub_active = defined( 'WM_SUITE_HUB_VERSION' );

		$tabs = [
			'telemetry'  => [ 'label' => __( 'Sensory Telemetry', 'wm-a11y-sensory-governor' ), 'icon' => 'dashicons-chart-bar' ],
			'calculator' => [ 'label' => __( 'Live Contrast Calculator', 'wm-a11y-sensory-governor' ), 'icon' => 'dashicons-calculator' ],
			'focus'      => [ 'label' => __( 'Focus Not Obscured', 'wm-a11y-sensory-governor' ), 'icon' => 'dashicons-marker' ],
			'motion'     => [ 'label' => __( 'Vestibular Motion Governor', 'wm-a11y-sensory-governor' ), 'icon' => 'dashicons-image-rotate' ],
			'statutory'  => [ 'label' => __( 'Law & WP-CLI', 'wm-a11y-sensory-governor' ), 'icon' => 'dashicons-book-alt' ],
		];
		if ( ! isset( $tabs[ $active_tab ] ) ) {
			$active_tab = 'telemetry';
		}
		?>
		<div class="wrap wm-admin">
			<!-- Header -->
			<header class="wm-admin-header">
				<div class="wm-header-brand">
					<div class="wm-header-icon">
						<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="10"/><path d="M12 2a7 7 0 0 0 0 14v6"/><path d="M12 8a3 3 0 1 0 0 6v-6"/></svg>
					</div>
					<div>
						<h1><?php esc_html_e( 'WM Accessibility Sensory Governor &amp; Contrast Tokenizer', 'wm-a11y-sensory-governor' ); ?></h1>
						<p class="wm-subtitle"><?php esc_html_e( 'Focus-ring &amp; reduced-motion CSS &bull; WCAG contrast calculator', 'wm-a11y-sensory-governor' ); ?></p>
					</div>
				</div>
				<div class="wm-header-badges">
					<span class="wm-badge-family" title="<?php esc_attr_e( 'Part of the WM Suite plugin family', 'wm-a11y-sensory-governor' ); ?>">
						<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="3"/><circle cx="12" cy="4" r="2"/><circle cx="19" cy="16" r="2"/><circle cx="5" cy="16" r="2"/><path d="M12 7v2M13.9 13.2l3.4 2M10.1 13.2l-3.4 2"/></svg>
						<?php esc_html_e( 'WM Suite', 'wm-a11y-sensory-governor' ); ?>
						<span class="wm-badge-family-state"><?php echo $wm_hub_active ? esc_html__( 'Hub', 'wm-a11y-sensory-governor' ) : esc_html__( 'Standalone', 'wm-a11y-sensory-governor' ); ?></span>
					</span>
					<span class="wm-badge wm-badge-pulse"><?php esc_html_e( 'WCAG CONTRAST MATHS', 'wm-a11y-sensory-governor' ); ?></span>
					<span class="wm-badge wm-badge-ghost"><?php esc_html_e( 'SC 2.4.11 FOCUS CSS', 'wm-a11y-sensory-governor' ); ?></span>
					<span class="wm-badge wm-badge-cyan"><?php esc_html_e( 'SC 2.3.3 REDUCED MOTION', 'wm-a11y-sensory-governor' ); ?></span>
				</div>
			</header>

			<!-- Quick Plain Language Guide -->
			<div class="wm-quick-guide">
				<div class="wm-quick-guide-title">
					<span><span class="dashicons dashicons-lightbulb" aria-hidden="true"></span> <?php esc_html_e( 'Was macht der Sensory Governor und wofür brauchen Sie ihn?', 'wm-a11y-sensory-governor' ); ?></span>
				</div>
				<p class="wm-quick-guide-desc">
					<?php esc_html_e( 'Das Plugin schreibt zwei CSS-Blöcke in den Kopf jeder Seite: einen sichtbaren Fokus-Ring mit Abstand zu fixierten Kopfzeilen (WCAG 2.2 SC 2.4.11) und eine Bremse für Animationen, sobald Besucher in ihrem System „Bewegung reduzieren“ eingestellt haben (SC 2.3.3). Dazu rechnet es Farbkontraste nach der WCAG-Formel aus – hier im Backend, per REST und per WP-CLI. Die Farben der Website ändert es nicht.', 'wm-a11y-sensory-governor' ); ?>
				</p>
				<div class="wm-quick-guide-actions">
					<strong><?php esc_html_e( 'Schnellzugriff:', 'wm-a11y-sensory-governor' ); ?></strong>
					<a href="?page=wm-sensory-governor&tab=calculator" class="wm-preset-pill-btn">
						<span class="dashicons dashicons-calculator" aria-hidden="true"></span> <?php esc_html_e( 'Farbkontrast live berechnen', 'wm-a11y-sensory-governor' ); ?>
					</a>
					<a href="?page=wm-sensory-governor&tab=motion" class="wm-preset-pill-btn">
						<span class="dashicons dashicons-image-rotate" aria-hidden="true"></span> <?php esc_html_e( 'Bewegungsreduktion prüfen', 'wm-a11y-sensory-governor' ); ?>
					</a>
					<a href="?page=wm-sensory-governor&tab=statutory" class="wm-preset-pill-btn">
						<span class="dashicons dashicons-book-alt" aria-hidden="true"></span> <?php esc_html_e( 'Rechtsgrundlagen & WP-CLI', 'wm-a11y-sensory-governor' ); ?>
					</a>
				</div>
			</div>

			<!-- Tabs -->
			<nav class="wm-nav-tabs" aria-label="<?php esc_attr_e( 'Module Tabs', 'wm-a11y-sensory-governor' ); ?>">
				<?php foreach ( $tabs as $slug => $tab ) : ?>
				<a href="<?php echo esc_url( add_query_arg( [ 'page' => 'wm-sensory-governor', 'tab' => $slug ], admin_url( 'admin.php' ) ) ); ?>" class="wm-tab-link <?php echo $active_tab === $slug ? 'active' : ''; ?>"<?php echo $active_tab === $slug ? ' aria-current="page"' : ''; ?>>
					<span class="dashicons <?php echo esc_attr( $tab['icon'] ); ?>" aria-hidden="true"></span> <?php echo esc_html( $tab['label'] ); ?>
				</a>
				<?php endforeach; ?>
			</nav>

			<!-- Tab 1: Telemetry -->
			<?php if ( $active_tab === 'telemetry' ) : ?>
			<div class="wm-tab-content">
				<div class="wm-grid-4">
					<div class="wm-stat-card">
						<span class="wm-stat-label"><?php esc_html_e( 'Focus Obscured Guard', 'wm-a11y-sensory-governor' ); ?></span>
						<span class="wm-stat-value text-cyan"><?php echo $focus_enabled ? esc_html__( 'Active', 'wm-a11y-sensory-governor' ) : esc_html__( 'Disabled', 'wm-a11y-sensory-governor' ); ?></span>
						<span class="wm-stat-delta"><?php esc_html_e( 'WCAG 2.2 2.4.11', 'wm-a11y-sensory-governor' ); ?></span>
					</div>
					<div class="wm-stat-card">
						<span class="wm-stat-label"><?php esc_html_e( 'Motion Governor', 'wm-a11y-sensory-governor' ); ?></span>
						<span class="wm-stat-value text-emerald"><?php echo $motion_enabled ? esc_html__( 'Active', 'wm-a11y-sensory-governor' ) : esc_html__( 'Disabled', 'wm-a11y-sensory-governor' ); ?></span>
						<span class="wm-stat-delta"><?php esc_html_e( 'Prefers-Reduced-Motion', 'wm-a11y-sensory-governor' ); ?></span>
					</div>
					<div class="wm-stat-card">
						<span class="wm-stat-label"><?php esc_html_e( 'Target Contrast Ratio', 'wm-a11y-sensory-governor' ); ?></span>
						<span class="wm-stat-value text-amber"><?php esc_html_e( '&ge; 4.5:1', 'wm-a11y-sensory-governor' ); ?></span>
						<span class="wm-stat-delta"><?php esc_html_e( 'WCAG 2.2 AA (Normal Text)', 'wm-a11y-sensory-governor' ); ?></span>
					</div>
					<div class="wm-stat-card">
						<span class="wm-stat-label"><?php esc_html_e( 'UI &amp; Focus Ring Ratio', 'wm-a11y-sensory-governor' ); ?></span>
						<span class="wm-stat-value text-purple"><?php esc_html_e( '&ge; 3.0:1', 'wm-a11y-sensory-governor' ); ?></span>
						<span class="wm-stat-delta"><?php esc_html_e( 'WCAG 1.4.11 Non-Text', 'wm-a11y-sensory-governor' ); ?></span>
					</div>
				</div>

				<div class="wm-card" style="margin-top: 24px;">
					<h2><?php esc_html_e( 'What the two governors do', 'wm-a11y-sensory-governor' ); ?></h2>
					<p style="color: var(--wm-text-secondary); line-height: 1.6;">
						<?php esc_html_e( 'While the focus governor is on, every element that receives keyboard focus gets a visible outline in the configured ring colour and a scroll margin, so sticky headers and footers do not cover it. While the motion governor is on, animations and transitions collapse to 0.001 ms whenever the visitor has enabled Reduced Motion in the operating system. Both are CSS printed into the site head; the plugin does not audit or change the theme itself.', 'wm-a11y-sensory-governor' ); ?>
					</p>
				</div>

				<div class="wm-card" style="margin-top: 24px;">
					<h2><?php esc_html_e( 'Data Lifecycle &amp; Uninstall', 'wm-a11y-sensory-governor' ); ?></h2>
					<form method="post" action="">
						<?php wp_nonce_field( 'wm_sensory_save_action', 'wm_sensory_save_nonce' ); ?>
						<input type="hidden" name="wm_sensory_form" value="lifecycle" />

						<div class="wm-form-group">
							<label class="wm-toggle-label">
								<input type="checkbox" name="wm_sensory_delete_data_on_uninstall" value="1" <?php checked( $delete_on_uninstall ); ?> />
								<span><strong><?php esc_html_e( 'Delete plugin options on uninstall', 'wm-a11y-sensory-governor' ); ?></strong> <?php esc_html_e( '&mdash; Stored settings are preserved by default when the plugin is deleted. Enable this option only if you want all plugin options and rate-limit transients removed upon uninstallation.', 'wm-a11y-sensory-governor' ); ?></span>
							</label>
						</div>

						<button type="submit" class="wm-btn wm-btn-primary" style="margin-top: 16px;">
							<?php esc_html_e( 'Save Lifecycle Preference', 'wm-a11y-sensory-governor' ); ?>
						</button>
					</form>
				</div>
			</div>

			<!-- Tab 2: Calculator -->
			<?php elseif ( $active_tab === 'calculator' ) : ?>
			<div class="wm-tab-content">
				<div class="wm-card">
					<h2><?php esc_html_e( 'Mathematical Relative Luminance Contrast Calculator', 'wm-a11y-sensory-governor' ); ?></h2>
					<p style="color: var(--wm-text-secondary);"><?php esc_html_e( 'Calculates the exact WCAG Relative Luminance formula:', 'wm-a11y-sensory-governor' ); ?> <code><?php esc_html_e( '(L1 + 0.05) / (L2 + 0.05)', 'wm-a11y-sensory-governor' ); ?></code>.</p>

					<div class="wm-grid-2" style="margin-top: 20px;">
						<div class="wm-box">
							<div class="wm-form-group">
								<label for="calc-fg-text" style="color: var(--wm-text-secondary); display: block; margin-bottom: 4px;"><?php esc_html_e( 'Foreground Color (Text / Icon):', 'wm-a11y-sensory-governor' ); ?></label>
								<div style="display: flex; gap: 8px;">
									<input type="color" id="calc-fg-picker" value="<?php echo esc_attr( $calc_fg ); ?>" aria-label="<?php esc_attr_e( 'Foreground colour picker', 'wm-a11y-sensory-governor' ); ?>" oninput="document.getElementById('calc-fg-text').value = this.value; runContrastCalc();" style="width: 44px; height: 38px; border: 0; border-radius: 4px; cursor: pointer;" />
									<input type="text" id="calc-fg-text" class="wm-input" value="<?php echo esc_attr( $calc_fg ); ?>" oninput="document.getElementById('calc-fg-picker').value = this.value; runContrastCalc();" style="flex: 1;" />
								</div>
							</div>

							<div class="wm-form-group">
								<label for="calc-bg-text" style="color: var(--wm-text-secondary); display: block; margin-bottom: 4px;"><?php esc_html_e( 'Background Color:', 'wm-a11y-sensory-governor' ); ?></label>
								<div style="display: flex; gap: 8px;">
									<input type="color" id="calc-bg-picker" value="<?php echo esc_attr( $calc_bg ); ?>" aria-label="<?php esc_attr_e( 'Background colour picker', 'wm-a11y-sensory-governor' ); ?>" oninput="document.getElementById('calc-bg-text').value = this.value; runContrastCalc();" style="width: 44px; height: 38px; border: 0; border-radius: 4px; cursor: pointer;" />
									<input type="text" id="calc-bg-text" class="wm-input" value="<?php echo esc_attr( $calc_bg ); ?>" oninput="document.getElementById('calc-bg-picker').value = this.value; runContrastCalc();" style="flex: 1;" />
								</div>
							</div>

							<div id="calc-preview-box" style="margin-top: 16px; padding: 24px; border-radius: 8px; text-align: center; background: <?php echo esc_attr( $calc_bg ); ?>; color: <?php echo esc_attr( $calc_fg ); ?>; border: 1px solid var(--wm-border);">
								<p style="font-size: 18px; font-weight: bold; margin: 0;"><?php esc_html_e( 'Sample Text Preview (Normal 16px)', 'wm-a11y-sensory-governor' ); ?></p>
								<p style="font-size: 13px; margin: 6px 0 0 0;"><?php esc_html_e( 'The quick brown fox jumps over the lazy dog.', 'wm-a11y-sensory-governor' ); ?></p>
							</div>
						</div>

						<div class="wm-box" id="calc-results-box" aria-live="polite">
							<h3><?php esc_html_e( 'Evaluation Results', 'wm-a11y-sensory-governor' ); ?></h3>
							<div class="wm-stat-value" style="font-size: 42px; margin: 12px 0;" id="calc-ratio-val"><?php echo esc_html( $calc_start['ratio_string'] ); ?></div>

							<div style="font-size: 13px; line-height: 1.8; color: var(--wm-text-secondary);" id="calc-checks-list">
								<div>&bull; <?php esc_html_e( 'WCAG 2.2 AA Normal Text (≥4.5:1):', 'wm-a11y-sensory-governor' ); ?> <span class="<?php echo esc_attr( $badge( $calc_start['wcag_aa_normal'] ) ); ?>" id="res-aa-norm"><?php echo esc_html( $verdict( $calc_start['wcag_aa_normal'] ) ); ?></span></div>
								<div>&bull; <?php esc_html_e( 'WCAG 2.2 AA Large Text (≥3.0:1):', 'wm-a11y-sensory-governor' ); ?> <span class="<?php echo esc_attr( $badge( $calc_start['wcag_aa_large'] ) ); ?>" id="res-aa-lg"><?php echo esc_html( $verdict( $calc_start['wcag_aa_large'] ) ); ?></span></div>
								<div>&bull; <?php esc_html_e( 'WCAG 2.2 AAA Normal (≥7.0:1):', 'wm-a11y-sensory-governor' ); ?> <span class="<?php echo esc_attr( $badge( $calc_start['wcag_aaa_normal'] ) ); ?>" id="res-aaa-norm"><?php echo esc_html( $verdict( $calc_start['wcag_aaa_normal'] ) ); ?></span></div>
								<div>&bull; <?php esc_html_e( 'UI Components &amp; Focus Rings (≥3.0:1):', 'wm-a11y-sensory-governor' ); ?> <span class="<?php echo esc_attr( $badge( $calc_start['ui_components'] ) ); ?>" id="res-ui"><?php echo esc_html( $verdict( $calc_start['ui_components'] ) ); ?></span></div>
							</div>
						</div>
					</div>
				</div>

				<script>
				function runContrastCalc() {
					const fg = document.getElementById('calc-fg-text').value;
					const bg = document.getElementById('calc-bg-text').value;

					const preview = document.getElementById('calc-preview-box');
					preview.style.color = fg;
					preview.style.backgroundColor = bg;

					fetch('<?php echo esc_url( rest_url( 'wm-sensory/v1/calculate-contrast' ) ); ?>', {
						method: 'POST',
						headers: {
							'Content-Type': 'application/json',
							'X-WP-Nonce': <?php echo wp_json_encode( function_exists( 'wp_create_nonce' ) ? wp_create_nonce( 'wp_rest' ) : '' ); ?>
						},
						body: JSON.stringify({ foreground: fg, background: bg })
					})
					.then(r => r.json())
					.then(data => {
						if (!data.success || !data.data) {
							// Invalid input answers 400 with a message; the old script kept the previous result on screen.
							document.getElementById('calc-ratio-val').innerText = '—';
							['res-aa-norm', 'res-aa-lg', 'res-aaa-norm', 'res-ui'].forEach(id => {
								const el = document.getElementById(id);
								el.className = 'wm-badge-red';
								el.innerText = <?php echo wp_json_encode( __( 'no hex colour', 'wm-a11y-sensory-governor' ) ); ?>;
							});
							return;
						}
						{
							const d = data.data;
							document.getElementById('calc-ratio-val').innerText = d.ratio_string;

							// Verdict colours come from the family badge classes, never from inline hex.
							const setBadge = (id, pass) => {
								const el = document.getElementById(id);
								el.className = pass ? 'wm-badge-green' : 'wm-badge-red';
								el.innerText = pass ? 'PASS' : 'FAIL';
							};

							setBadge('res-aa-norm', d.wcag_aa_normal);
							setBadge('res-aa-lg', d.wcag_aa_large);
							setBadge('res-aaa-norm', d.wcag_aaa_normal);
							setBadge('res-ui', d.ui_components);
						}
					})
					.catch(() => {
						document.getElementById('calc-ratio-val').innerText = <?php echo wp_json_encode( __( 'Calculation failed', 'wm-a11y-sensory-governor' ) ); ?>;
					});
				}
				</script>
			</div>

			<!-- Tab 3: Focus Not Obscured -->
			<?php elseif ( $active_tab === 'focus' ) : ?>
			<div class="wm-tab-content">
				<div class="wm-card">
					<h2><?php esc_html_e( 'Focus Not Obscured Settings (WCAG 2.2 2.4.11)', 'wm-a11y-sensory-governor' ); ?></h2>
					<form method="post" action="">
						<?php wp_nonce_field( 'wm_sensory_save_action', 'wm_sensory_save_nonce' ); ?>
							<input type="hidden" name="wm_sensory_form" value="focus" />

						<div class="wm-form-group">
							<label class="wm-toggle-label">
								<input type="checkbox" name="wm_sensory_focus_enabled" value="1" <?php checked( $focus_enabled ); ?> />
								<span><strong><?php esc_html_e( 'Enable 2.4.11 Focus Obscured Auto-Governor', 'wm-a11y-sensory-governor' ); ?></strong> <?php esc_html_e( '&mdash; Automatically adds', 'wm-a11y-sensory-governor' ); ?> <code><?php esc_html_e( 'scroll-margin-top', 'wm-a11y-sensory-governor' ); ?></code> <?php esc_html_e( 'to focused elements so sticky headers do not cover active input fields or buttons.', 'wm-a11y-sensory-governor' ); ?></span>
							</label>
						</div>

						<div class="wm-form-group" style="margin-top: 16px;">
							<label for="wm-sensory-ring-color" style="color: var(--wm-text-secondary); display: block; margin-bottom: 4px;"><?php esc_html_e( 'High-Visibility Focus Ring Color:', 'wm-a11y-sensory-governor' ); ?></label>
							<div style="display: flex; gap: 8px;">
								<input type="color" id="wm-sensory-ring-color" name="wm_sensory_ring_color" value="<?php echo esc_attr( $ring_color ); ?>" style="width: 44px; height: 38px; border: 0; border-radius: 4px; cursor: pointer;" />
								<output for="wm-sensory-ring-color" class="wm-input" style="width: 140px;"><?php echo esc_html( $ring_color ); ?></output>
							</div>
						</div>

						<button type="submit" class="wm-btn wm-btn-primary" style="margin-top: 16px;">
							<?php esc_html_e( 'Save Focus Governor Rules', 'wm-a11y-sensory-governor' ); ?>
						</button>
					</form>
				</div>
			</div>

			<!-- Tab 4: Motion Governor -->
			<?php elseif ( $active_tab === 'motion' ) : ?>
			<div class="wm-tab-content">
				<div class="wm-card">
					<h2><?php esc_html_e( 'Vestibular Motion Governor Settings', 'wm-a11y-sensory-governor' ); ?></h2>
					<form method="post" action="">
						<?php wp_nonce_field( 'wm_sensory_save_action', 'wm_sensory_save_nonce' ); ?>
							<input type="hidden" name="wm_sensory_form" value="motion" />

						<div class="wm-form-group">
							<label class="wm-toggle-label">
								<input type="checkbox" name="wm_sensory_motion_enabled" value="1" <?php checked( $motion_enabled ); ?> />
								<span><strong><?php esc_html_e( 'Enable Vestibular Motion Reducer', 'wm-a11y-sensory-governor' ); ?></strong> <?php esc_html_e( '&mdash; Injects universal CSS rules clamping animation and transition durations to', 'wm-a11y-sensory-governor' ); ?> <code><?php esc_html_e( '0.001ms', 'wm-a11y-sensory-governor' ); ?></code> <?php esc_html_e( 'whenever the user has enabled Reduced Motion in OS settings.', 'wm-a11y-sensory-governor' ); ?></span>
							</label>
						</div>

						<button type="submit" class="wm-btn wm-btn-primary" style="margin-top: 16px;">
							<?php esc_html_e( 'Save Motion Rules', 'wm-a11y-sensory-governor' ); ?>
						</button>
					</form>
				</div>
			</div>

			<!-- Tab 5: Law & WP-CLI -->
			<?php elseif ( $active_tab === 'statutory' ) : ?>
			<div class="wm-tab-content">
				<div class="wm-card">
					<h2><?php esc_html_e( 'WCAG 2.2 Statutory References &amp; Terminal Automation', 'wm-a11y-sensory-governor' ); ?></h2>
					<div class="wm-grid-2" style="margin-top: 16px;">
						<div class="wm-box">
							<h3><?php esc_html_e( 'WCAG 2.2 Success Criterion 2.4.11 (Focus Not Obscured)', 'wm-a11y-sensory-governor' ); ?></h3>
							<p style="color: var(--wm-text-secondary); font-size: 13px; line-height: 1.6;">
								<?php esc_html_e( 'Requires that when a user interface component receives keyboard focus, the component is not entirely hidden due to author-created content (such as sticky navigation bars or modal banners).', 'wm-a11y-sensory-governor' ); ?>
							</p>
						</div>
						<div class="wm-box">
							<h3><?php esc_html_e( 'WCAG 2.2 Success Criterion 1.4.3 &amp; 1.4.11 (Contrast Minimum)', 'wm-a11y-sensory-governor' ); ?></h3>
							<p style="color: var(--wm-text-secondary); font-size: 13px; line-height: 1.6;">
								<?php esc_html_e( 'Requires at least 4.5:1 contrast for normal text and 3:1 for large text and visual information required to identify user interface components and states.', 'wm-a11y-sensory-governor' ); ?>
							</p>
						</div>
					</div>

					<h2 style="margin-top: 24px;"><?php esc_html_e( 'WP-CLI command', 'wm-a11y-sensory-governor' ); ?></h2>
					<pre class="wm-code-block"># Compute the contrast ratio between two colours
wp a11y contrast #ffffff #050811

# Test custom brand accent colours
wp a11y contrast #06b6d4 #090d16</pre>
				</div>
			</div>
			<?php endif; ?>
		</div>
		<?php
	}
}

<?php
/**
 * Plugin Name:       WM Accessibility Sensory Governor & Contrast Tokenizer
 * Plugin URI:        https://arnoldwender.com/wordpress-plugins/wm-a11y-sensory-governor/
 * Description:       Prints WCAG 2.2 focus-ring (SC 2.4.11 Focus Not Obscured) and reduced-motion (SC 2.3.3) CSS into the site head and calculates colour-contrast ratios in the admin, over REST and on WP-CLI.
 * Version:           1.2.2
 * Requires at least: 6.4
 * Tested up to:      7.1
 * Requires PHP:      8.2
 * Author:            Arnold Wender
 * Author URI:        https://arnoldwender.com
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wm-a11y-sensory-governor
 * Domain Path:       /languages
 */

declare(strict_types=1);

namespace WMCyber\A11y\Governor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WM_SENSORY_GOVERNOR_VERSION', '1.2.2' );
define( 'WM_SENSORY_GOVERNOR_FILE', __FILE__ );
define( 'WM_SENSORY_GOVERNOR_PATH', plugin_dir_path( __FILE__ ) );
define( 'WM_SENSORY_GOVERNOR_URL', plugin_dir_url( __FILE__ ) );

// Autoloader
spl_autoload_register( function ( string $class ): void {
	$prefix   = 'WMCyber\\A11y\\Governor\\';
	$base_dir = WM_SENSORY_GOVERNOR_PATH . 'includes/';

	$len = strlen( $prefix );
	if ( strncmp( $prefix, $class, $len ) !== 0 ) {
		return;
	}

	$relative_class = substr( $class, $len );
	$file           = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

	if ( file_exists( $file ) ) {
		require_once $file;
	}
} );

// Register as Spoke in WM Suite Hub
add_filter( 'wm_register_suite_module', function ( array $modules ): array {
	if ( ! class_exists( 'WMCyber\A11y\Governor\Adapters\Sensory_Spoke_Adapter' ) && file_exists( WM_SENSORY_GOVERNOR_PATH . 'includes/Adapters/Sensory_Spoke_Adapter.php' ) ) {
		require_once WM_SENSORY_GOVERNOR_PATH . 'includes/Adapters/Sensory_Spoke_Adapter.php';
	}
	if ( class_exists( 'WMCyber\A11y\Governor\Adapters\Sensory_Spoke_Adapter' ) ) {
		$modules[] = new Adapters\Sensory_Spoke_Adapter();
	}
	return $modules;
} );

// Bootstrap
add_action( 'plugins_loaded', function (): void {
	Core\Plugin::instance()->boot();
} );


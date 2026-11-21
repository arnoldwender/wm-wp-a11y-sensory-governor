<?php
/**
 * Standalone Automated Test Runner & Unit Suite for wm-a11y-sensory-governor.
 *
 * Runs without external WordPress/MySQL dependency by providing a deterministic
 * mock environment for options, hooks, textdomain, and Spoke interfaces.
 *
 * @package WMCyber\A11y\Governor
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Setup mock WordPress constants
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__) . '/');
}
if (!defined('WM_SENSORY_GOVERNOR_VERSION')) {
    define('WM_SENSORY_GOVERNOR_VERSION', '1.2.2');
}
if (!defined('WM_SENSORY_GOVERNOR_FILE')) {
    define('WM_SENSORY_GOVERNOR_FILE', dirname(__DIR__) . '/wm-a11y-sensory-governor.php');
}
if (!defined('WM_SENSORY_GOVERNOR_PATH')) {
    define('WM_SENSORY_GOVERNOR_PATH', dirname(__DIR__) . '/');
}
if (!defined('WM_SENSORY_GOVERNOR_URL')) {
    define('WM_SENSORY_GOVERNOR_URL', 'https://demo.example.com/wp-content/plugins/wm-a11y-sensory-governor/');
}

// Global Mocks & In-Memory Store
global $mock_options, $mock_filters, $mock_actions, $mock_styles;
$mock_options = [];
$mock_filters = [];
$mock_actions = [];
$mock_styles  = [];

// Mock WP core functions
if (!function_exists('add_action')) {
    function add_action(string $tag, callable $callback, int $priority = 10, int $accepted_args = 1): void {
        global $mock_actions;
        $mock_actions[$tag][] = $callback;
    }
}
if (!function_exists('add_filter')) {
    function add_filter(string $tag, callable $callback, int $priority = 10, int $accepted_args = 1): void {
        global $mock_filters;
        $mock_filters[$tag][] = $callback;
    }
}
if (!function_exists('get_option')) {
    function get_option(string $option, $default = false) {
        global $mock_options;
        return $mock_options[$option] ?? $default;
    }
}
if (!function_exists('update_option')) {
    function update_option(string $option, $value, $autoload = null): bool {
        global $mock_options;
        $mock_options[$option] = $value;
        return true;
    }
}
if (!function_exists('admin_url')) {
    function admin_url(string $path = ''): string {
        return 'https://demo.example.com/wp-admin/' . ltrim($path, '/');
    }
}
if (!function_exists('esc_html')) {
    function esc_html($text) {
        return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('esc_attr')) {
    function esc_attr($text) {
        return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('esc_url')) {
    function esc_url($url) {
        return (string)$url;
    }
}
if (!function_exists('__')) {
    function __(string $text, string $domain = 'default'): string {
        return $text;
    }
}
if (!function_exists('esc_html__')) {
    function esc_html__(string $text, string $domain = 'default'): string {
        return esc_html($text);
    }
}
if (!function_exists('esc_html_e')) {
    function esc_html_e(string $text, string $domain = 'default'): void {
        echo esc_html($text);
    }
}
if (!function_exists('wp_enqueue_style')) {
    function wp_enqueue_style(string $handle, string $src = '', array $deps = [], $ver = false): void {
        global $mock_styles;
        $mock_styles[] = $handle;
    }
}

// Mock Suite Hub Interface
if (!interface_exists('ArnoldWender\SuiteHub\Contracts\WM_Plugin_Module_Interface')) {
    eval('namespace ArnoldWender\SuiteHub\Contracts {
        interface WM_Plugin_Module_Interface {
            public function get_module_slug(): string;
            public function get_name(): string;
            public function get_version(): string;
            public function get_description(): string;
            public function get_health_status(): string;
            public function get_sbom_data(): array;
            public function get_dependencies(): array;
            public function get_settings_url(): ?string;
            public function get_telemetry_metrics(): array;
            public function execute_action(string $action, array $params = []): array;
        }
    }');
}

// Require Plugin Core Files
require_once ABSPATH . 'includes/Calculator/ContrastCalculator.php';
require_once ABSPATH . 'includes/Governor/VestibularMotionGovernor.php';
require_once ABSPATH . 'includes/Governor/FocusObscuredGovernor.php';
require_once ABSPATH . 'includes/Adapters/Sensory_Spoke_Adapter.php';
require_once ABSPATH . 'includes/Admin/AdminDashboard.php';

use WMCyber\A11y\Governor\Calculator\ContrastCalculator;
use WMCyber\A11y\Governor\Governor\VestibularMotionGovernor;
use WMCyber\A11y\Governor\Governor\FocusObscuredGovernor;
use WMCyber\A11y\Governor\Adapters\Sensory_Spoke_Adapter;
use WMCyber\A11y\Governor\Admin\AdminDashboard;

// Test Execution Harness
$passed_count = 0;
$failed_count = 0;

function assert_test(string $name, bool $condition, string $failure_details = ''): void {
    global $passed_count, $failed_count;
    if ($condition) {
        echo "  [PASS] {$name}\n";
        $passed_count++;
    } else {
        echo "  [FAIL] {$name}: {$failure_details}\n";
        $failed_count++;
    }
}

echo "==============================================================================\n";
echo "  WM ACCESSIBILITY SENSORY GOVERNOR — ZERO-DEPENDENCY UNIT TEST SUITE (WCAG 2.2)\n";
echo "==============================================================================\n\n";

// ------------------------------------------------------------
// Test 1: Relative Luminance & WCAG Contrast Mathematics
// ------------------------------------------------------------
echo "[TEST GROUP 1] WCAG 2.2 Relative Luminance & Contrast Mathematics\n";
$calc = new ContrastCalculator();

// Black vs White (must be 21:1)
$bw_res = $calc->calculate_contrast('#ffffff', '#000000');
assert_test(
    'Pure White (#ffffff) on Pure Black (#000000) yields mathematically exact 21:1 ratio',
    $bw_res['ratio'] === 21.0 && $bw_res['wcag_aa_normal'] && $bw_res['wcag_aaa_normal'],
    "Expected 21:1, got: {$bw_res['ratio_string']}"
);

// Identical colors (must be 1:1)
$same_res = $calc->calculate_contrast('#336699', '#336699');
assert_test(
    'Identical colors yield 1:1 ratio and fail all WCAG thresholds',
    $same_res['ratio'] === 1.0 && !$same_res['wcag_aa_normal'] && $same_res['verdict'] === 'FAIL',
    "Expected 1:1 FAIL, got: {$same_res['ratio_string']} ({$same_res['verdict']})"
);

// High contrast Cyan on Dark Canvas
$cyan_on_dark = $calc->calculate_contrast('#06b6d4', '#050811');
assert_test(
    'Cyan Focus Ring (#06b6d4) on Dark Background (#050811) passes WCAG AA and Large AAA thresholds',
    $cyan_on_dark['ratio'] >= 8.0 && $cyan_on_dark['wcag_aa_normal'] && $cyan_on_dark['wcag_aaa_large'],
    "Expected >= 8:1, got: {$cyan_on_dark['ratio_string']}"
);

// 3-digit shorthand parsing
$shorthand_res = $calc->calculate_contrast('#fff', '#000');
assert_test(
    'Calculator correctly handles 3-digit hex shorthand (#fff, #000)',
    $shorthand_res['ratio'] === 21.0,
    "Expected 21:1, got: {$shorthand_res['ratio_string']}"
);

// ------------------------------------------------------------
// Test 2: Vestibular Motion Governor CSS Generation
// ------------------------------------------------------------
echo "\n[TEST GROUP 2] Vestibular Motion Governor & prefers-reduced-motion\n";
$motion_gov = new VestibularMotionGovernor();
$motion_css = $motion_gov->get_motion_css();

assert_test(
    'Vestibular motion CSS clamps animation durations to 0.001ms under prefers-reduced-motion',
    str_contains($motion_css, '@media (prefers-reduced-motion: reduce)') && str_contains($motion_css, 'animation-duration: 0.001ms !important;'),
    "CSS snippet: " . substr($motion_css, 0, 150)
);

assert_test(
    'Vestibular motion CSS suppresses parallax, hero animations, and marquees',
    str_contains($motion_css, '.wm-parallax') && str_contains($motion_css, '.hero-animation') && str_contains($motion_css, '.marquee-track'),
    "CSS missing target selectors"
);

// ------------------------------------------------------------
// Test 3: Focus Obscured Governor (WCAG 2.2 2.4.11)
// ------------------------------------------------------------
echo "\n[TEST GROUP 3] Focus Obscured Governor (WCAG 2.2 2.4.11)\n";
$focus_gov = new FocusObscuredGovernor();
$focus_css = $focus_gov->get_focus_css();

assert_test(
    'Focus Obscured CSS defines scroll-margin-top offset to protect focused elements from sticky headers',
    str_contains($focus_css, 'scroll-margin-top: var(--wm-focus-offset, 96px) !important;') && str_contains($focus_css, ':focus-visible'),
    "CSS snippet: " . substr($focus_css, 0, 150)
);

assert_test(
    'Focus Obscured CSS injects high-visibility outline and glowing box-shadow for keyboard navigation',
    str_contains($focus_css, 'outline: var(--wm-focus-ring-width) solid var(--wm-focus-ring-color) !important;') && str_contains($focus_css, 'box-shadow:'),
    "CSS missing outline or shadow"
);

// The ring colour option must reach the stylesheet (until 2026-09-14 it was stored and ignored).
assert_test(
    'Without a stored option the focus CSS carries the default ring colour and its 35% glow',
    str_contains($focus_css, '--wm-focus-ring-color: #06b6d4;') && str_contains($focus_css, 'rgba(6, 182, 212, 0.35)'),
    "CSS snippet: " . substr($focus_css, 0, 300)
);

update_option('wm_sensory_ring_color', '#FF0000');
$red_css = (new FocusObscuredGovernor())->get_focus_css();
assert_test(
    'A stored ring colour (#FF0000) becomes the CSS ring colour and the glow follows it',
    str_contains($red_css, '--wm-focus-ring-color: #ff0000;') && str_contains($red_css, 'rgba(255, 0, 0, 0.35)') && !str_contains($red_css, '#06b6d4'),
    "CSS snippet: " . substr($red_css, 0, 300)
);

update_option('wm_sensory_ring_color', 'red; } body { display: none');
$bad_css = (new FocusObscuredGovernor())->get_focus_css();
assert_test(
    'A value that is not a six-digit hex colour never reaches the stylesheet (default is used)',
    str_contains($bad_css, '--wm-focus-ring-color: #06b6d4;') && !str_contains($bad_css, 'display: none'),
    "CSS snippet: " . substr($bad_css, 0, 300)
);
$mock_options = [];

// ------------------------------------------------------------
// Test 4: Suite Hub Spoke Adapter Interface & CycloneDX SBOM
// ------------------------------------------------------------
echo "\n[TEST GROUP 4] Suite Hub Spoke Adapter Interface & Telemetry\n";
$adapter = new Sensory_Spoke_Adapter();

assert_test(
    'Spoke Adapter returns exact module slug wm-a11y-sensory-governor',
    $adapter->get_module_slug() === 'wm-a11y-sensory-governor',
    "Slug: " . $adapter->get_module_slug()
);

assert_test(
    'Spoke Adapter reports HEALTHY status',
    $adapter->get_health_status() === 'HEALTHY',
    "Status: " . $adapter->get_health_status()
);

$sbom = $adapter->get_sbom_data();
assert_test(
    'Spoke Adapter returns an SBOM component with bom-ref, file hash and feature properties (no WCAG or BFSG conformance claims)',
    $sbom['bom-ref'] === 'pkg:wordpress/wm-a11y-sensory-governor@' . WM_SENSORY_GOVERNOR_VERSION &&!empty($sbom['hashes'][0]['content']) && !preg_match('/Compliant|BFSG|wm:accessibility/', (string) json_encode($sbom)),
    "SBOM: " . var_export($sbom, true)
);

$metrics = $adapter->get_telemetry_metrics();
assert_test(
    'Spoke Adapter computes real-time contrast and enforcement metrics for Suite Hub',
    isset($metrics['focus_obscured_governor'], $metrics['vestibular_motion_clamp'], $metrics['focus_ring_dark_contrast']),
    "Metrics: " . var_export($metrics, true)
);

$action_res = $adapter->execute_action('self_test');
assert_test(
    'Spoke Adapter executes self_test action and verifies the contrast calculation',
    $action_res['success'] === true && str_contains($action_res['message'], 'Kontrastberechnung geprüft'),
    "Action Response: " . var_export($action_res, true)
);

// ------------------------------------------------------------
// Test 5: Admin surface — family sheets and an honest widget
// ------------------------------------------------------------
echo "\n[TEST GROUP 5] Admin surface (family sheets, dashboard widget)\n";
$dash = AdminDashboard::instance();

foreach (['toplevel_page_wm-sensory-governor', 'wm-suite_page_wm-sensory-governor', 'index.php'] as $hook) {
    $mock_styles = [];
    $dash->enqueue_assets($hook);
    assert_test(
        "Family sheets are enqueued on '{$hook}' (tokens first, then components)",
        $mock_styles === ['wm-admin-tokens', 'wm-admin-ui'],
        'Enqueued: ' . implode(', ', $mock_styles)
    );
}
$mock_styles = [];
$dash->enqueue_assets('edit.php');
assert_test(
    'Nothing is enqueued on an unrelated admin screen (edit.php)',
    $mock_styles === [],
    'Enqueued: ' . implode(', ', $mock_styles)
);

ob_start();
$dash->render_dashboard_widget();
$widget = (string) ob_get_clean();
assert_test(
    'Dashboard widget reports the two governors only (no "Audio Guard" the plugin does not have)',
    str_contains($widget, 'Motion Guard') && str_contains($widget, 'Fokus-Ring') && !str_contains($widget, 'Audio Guard') && !str_contains($widget, 'Autoplay'),
    'Widget HTML: ' . substr($widget, 0, 400)
);
assert_test(
    'Dashboard widget carries no inline colour (family classes only)',
    !preg_match('/style="[^"]*#[0-9a-fA-F]{3,6}/', $widget),
    'Widget HTML: ' . substr($widget, 0, 400)
);

$admin_source = (string) file_get_contents(ABSPATH . 'includes/Admin/AdminDashboard.php');
assert_test(
    'Admin screen uses dashicons, not emoji (family rule)',
    preg_match('/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]/u', $admin_source) === 0 && str_contains($admin_source, 'dashicons-calculator'),
    'Emoji found in AdminDashboard.php'
);

// ------------------------------------------------------------
// Test 6: input validation, exact verdicts, per-tab saving, calculator start values (2026-09-15)
// ------------------------------------------------------------
echo "\n[TEST GROUP 6] Colour input, verdicts, settings tabs, calculator, adapter\n";
$GLOBALS['a11y_warnings'] = [];
set_error_handler(static function (int $no, string $msg, string $file, int $line): bool {
    $GLOBALS['a11y_warnings'][] = basename($file) . ':' . $line . ' ' . $msg;
    return true;
});

$invalid = [];
foreach (['#zzzzzz', 'red', '#12345', '', '#ffff'] as $bad) {
    try {
        $calc->calculate_contrast($bad, '#ffffff');
        $invalid[] = $bad;
    } catch (\InvalidArgumentException $e) {
        // expected
    }
}
assert_test('Values that are not 3- or 6-digit hex codes are refused (#zzzzzz scored 21:1 PASS_AA against white)', [] === $invalid, 'accepted: ' . implode(', ', $invalid));

$edge = $calc->calculate_contrast('#0078d7', '#ffffff');
assert_test(
    'Verdicts use the exact ratio: #0078d7 on white (4.4989:1) fails AA and is shown as 4.49:1 (was 4.5:1 PASS)',
    false === $edge['wcag_aa_normal'] && '4.49:1' === $edge['ratio_string'] && 'PASS_LARGE_ONLY' === $edge['verdict'] && $edge['ratio_exact'] < 4.5,
    var_export($edge, true)
);

foreach ([
    'wp_verify_nonce' => fn($n, $a) => 'nonce' === $n,
    'current_user_can' => fn($c) => $GLOBALS['mock_can_manage'] ?? true,
    'sanitize_text_field' => fn($v) => trim((string) $v),
    'sanitize_key' => fn($v) => strtolower((string) preg_replace('/[^a-zA-Z0-9_\-]/', '', (string) $v)),
    'wp_unslash' => fn($v) => $v,
    'sanitize_hex_color' => fn($v) => preg_match('/^#([0-9a-f]{3}){1,2}$/i', (string) $v) ? (string) $v : null,
    'add_query_arg' => fn($args, $url) => $url . '?' . http_build_query($args),
    'wp_safe_redirect' => function ($url) { throw new \RuntimeException('redirect'); },
    'esc_attr_e' => function ($t, $d = 'default') { echo esc_attr($t); },
    'esc_attr__' => fn($t, $d = 'default') => esc_attr($t),
    'wp_nonce_field' => function (...$a) { echo '<input type="hidden" name="wm_sensory_save_nonce" value="nonce" />'; },
    'wp_create_nonce' => fn($a = '') => 'nonce',
    'checked' => function ($a, $b = true, $echo = true) { $r = ((bool) $a === (bool) $b) ? ' checked="checked"' : ''; if ($echo) { echo $r; } return $r; },
    'rest_url' => fn($p = '') => 'https://demo.example.com/wp-json/' . $p,
    'wp_json_encode' => fn($v) => json_encode($v),
    'apply_filters' => function (string $tag, $value, ...$args) {
        global $mock_filters;
        foreach ($mock_filters[$tag] ?? [] as $cb) {
            $value = $cb($value, ...$args);
        }
        return $value;
    },
    'do_action' => function (string $tag, ...$args): void {
        global $mock_actions;
        $GLOBALS['mock_fired_actions'][$tag] = ($GLOBALS['mock_fired_actions'][$tag] ?? 0) + 1;
        foreach ($mock_actions[$tag] ?? [] as $cb) {
            $cb(...$args);
        }
    },
    'register_setting' => function (string $group, string $option, array $args = []): void {
        $GLOBALS['mock_registered_settings'][$option] = ['group' => $group, 'args' => $args];
    },
    'delete_option' => function (string $option): bool {
        global $mock_options;
        unset($mock_options[$option]);
        return true;
    },
    'get_site_option' => function (string $option, $default = false) {
        global $mock_options;
        return $mock_options[$option] ?? $default;
    },
    'delete_site_option' => function (string $option): bool {
        global $mock_options;
        unset($mock_options[$option]);
        return true;
    },
    'get_transient' => function (string $key) {
        return $GLOBALS['mock_transients'][$key] ?? false;
    },
    'set_transient' => function (string $key, $value, int $exp = 0): bool {
        $GLOBALS['mock_transients'][$key] = $value;
        return true;
    },
    'wp_salt' => fn(string $scheme = 'auth') => 'wm-unit-test-auth-salt',
    'wp_strip_all_tags' => fn(string $str) => strip_tags($str),
    'plugin_basename' => fn(string $file) => basename(dirname($file)) . '/' . basename($file),
    'load_plugin_textdomain' => fn(...$a) => true,
    'is_admin' => fn() => true,
    'register_block_bindings_source' => function (string $name, array $props): void {
        $GLOBALS['mock_block_bindings'][$name] = $props;
    },
] as $fn_name => $impl) {
    if (!function_exists($fn_name)) {
        $GLOBALS['mock_impl'][$fn_name] = $impl;
        eval("function {$fn_name}(...\$a) { return (\$GLOBALS['mock_impl']['{$fn_name}'])(...\$a); }");
    }
}
$save = static function (array $post): void {
    $_POST = $post + ['wm_sensory_save_nonce' => 'nonce'];
    try {
        AdminDashboard::instance()->save_settings();
    } catch (\RuntimeException $e) {
        // wp_safe_redirect() is followed by exit in the plugin; the stub throws instead.
    }
    $_POST = [];
};
$mock_options = ['wm_sensory_focus_enabled' => '1', 'wm_sensory_motion_enabled' => '1', 'wm_sensory_ring_color' => '#ff0000'];
$save(['wm_sensory_form' => 'focus', 'wm_sensory_focus_enabled' => '1', 'wm_sensory_ring_color' => '#00ff00']);
$after_focus = $mock_options;
$save(['wm_sensory_form' => 'motion']);
$after_motion = $mock_options;
assert_test(
    'Saving the focus tab keeps the motion governor on; saving the motion tab (off) keeps focus on and the ring colour (both were overwritten)',
    '1' === $after_focus['wm_sensory_motion_enabled'] && '#00ff00' === $after_focus['wm_sensory_ring_color']
        && '0' === $after_motion['wm_sensory_motion_enabled'] && '1' === $after_motion['wm_sensory_focus_enabled'] && '#00ff00' === $after_motion['wm_sensory_ring_color'],
    'after focus ' . json_encode($after_focus) . ' / after motion ' . json_encode($after_motion)
);
$before_unknown = $mock_options;
$save(['wm_sensory_focus_enabled' => '1']);
assert_test('A post without a form marker changes nothing', $before_unknown === $mock_options, json_encode($mock_options));

$_GET = ['tab' => 'calculator'];
ob_start();
AdminDashboard::instance()->render_dashboard();
$calc_html = (string) ob_get_clean();
$_GET = ['tab' => 'focus'];
ob_start();
AdminDashboard::instance()->render_dashboard();
$focus_html = (string) ob_get_clean();
$_GET = ['tab' => 'motion'];
ob_start();
AdminDashboard::instance()->render_dashboard();
$motion_html = (string) ob_get_clean();
$_GET = [];
$start = $calc->calculate_contrast('#06b6d4', '#050811')['ratio_string'];
assert_test(
    'The calculator opens with the computed ratio of its start colours (showed a fixed 10.42:1)',
    str_contains($calc_html, 'id="calc-ratio-val">' . $start . '</div>') && !str_contains($calc_html, '10.42:1') && str_contains($calc_html, '.catch('),
    'start ' . $start
);
assert_test(
    'Each settings form posts its own marker',
    str_contains($focus_html, 'name="wm_sensory_form" value="focus"') && !str_contains($focus_html, 'value="motion"')
        && str_contains($motion_html, 'name="wm_sensory_form" value="motion"') && !str_contains($motion_html, 'value="focus"'),
    'markers missing'
);

$mock_options = ['wm_sensory_ring_color' => 'red; } body { display: none'];
try {
    $tm = $adapter->get_telemetry_metrics();
} catch (\Throwable $t) {
    $tm = ['error' => get_class($t) . ': ' . $t->getMessage(), 'focus_ring_token' => null];
}
assert_test(
    'Telemetry reports the ring colour the stylesheet uses (the raw invalid option before) and no wcag_aa_compliant verdict',
    '#06b6d4' === $tm['focus_ring_token'] && !array_key_exists('wcag_aa_compliant', $tm) && 'YES' === ($tm['focus_ring_3_to_1_on_050811'] ?? null),
    var_export($tm, true)
);
$mock_options = [];
$bad_action = $adapter->execute_action('calculate_contrast', ['fg' => '#zzzzzz', 'bg' => '#fff']);
assert_test('The adapter contrast action refuses an invalid colour', false === $bad_action['success'], var_export($bad_action, true));
assert_test(
    'The self-test message depends on the result ("erfolgreich" whatever happened before)',
    (bool) preg_match('/\$pass\s*\?\s*sprintf\(\s*\'Kontrastberechnung geprüft.*?:\s*sprintf\(\s*\'Kontrastberechnung fehlerhaft/s', (string) file_get_contents(ABSPATH . 'includes/Adapters/Sensory_Spoke_Adapter.php')),
    'self_test message is not tied to $pass'
);

if (!class_exists('WP_REST_Request')) {
    eval('class WP_REST_Request { public function __construct(private array $p = []) {} public function get_param($k) { return $this->p[$k] ?? null; } }');
}
if (!class_exists('WP_REST_Response')) {
    eval('class WP_REST_Response { public function __construct(public $data = null, public int $status = 200) {} public function get_status(): int { return $this->status; } public function get_data() { return $this->data; } }');
}
if (!class_exists('WP_REST_Server')) {
    eval('class WP_REST_Server { const CREATABLE = "POST"; }');
}
require_once ABSPATH . 'includes/API/RestController.php';
$rest_bad = (new \WMCyber\A11y\Governor\API\RestController())->handle_calculate_contrast(new WP_REST_Request(['foreground' => '#zzzzzz', 'background' => '#ffffff']));
$rest_ok = (new \WMCyber\A11y\Governor\API\RestController())->handle_calculate_contrast(new WP_REST_Request(['foreground' => '#fff', 'background' => '#000']));
assert_test(
    'REST answers 400 for an invalid colour and 200 with the result for a valid pair',
    400 === $rest_bad->get_status() && false === ($rest_bad->get_data()['success'] ?? null) && 200 === $rest_ok->get_status() && '21:1' === $rest_ok->get_data()['data']['ratio_string'],
    var_export($rest_bad->get_data(), true)
);

restore_error_handler();
assert_test('No PHP warning or deprecation in this group (hexdec on invalid input raised "Deprecated: Invalid characters")', [] === $GLOBALS['a11y_warnings'], implode(' | ', array_slice($GLOBALS['a11y_warnings'], 0, 5)));

// ------------------------------------------------------------
// Test 7: REST Rate-Limiting, Opt-In Uninstall, Hooks & Ecosystem Bridges (v1.1.1)
// ------------------------------------------------------------
echo "\n[TEST GROUP 7] REST Rate Limiting, Opt-In Uninstall, Developer Hooks & Ecosystem Bridges\n";

if (!class_exists('WP_Error')) {
    eval('class WP_Error {
        public function __construct(private string $code = "", private string $message = "", private $data = null) {}
        public function get_error_code(): string { return $this->code; }
        public function get_error_message(): string { return $this->message; }
        public function get_error_data() { return $this->data; }
    }');
}
if (!class_exists('GenerateBlocks_Register_Dynamic_Tag')) {
    eval('class GenerateBlocks_Register_Dynamic_Tag {
        public static array $registered = [];
        public function __construct(array $config) {
            self::$registered[$config["tag"] ?? ""] = $config;
        }
    }');
}

require_once ABSPATH . 'includes/Integrations/EcosystemBridge.php';
require_once ABSPATH . 'includes/Core/Plugin.php';

// 7.1 REST HMAC-SHA256 rate limiting & admin bypass
$GLOBALS['mock_transients'] = [];
$GLOBALS['mock_can_manage'] = false;
$_SERVER['REMOTE_ADDR']     = '203.0.113.42';
$mock_filters['wm_sensory_rest_rate_limit'] = [ static fn( int $limit ): int => 2 ];

$rest_ctrl = new \WMCyber\A11y\Governor\API\RestController();
$req_rl    = new WP_REST_Request(['foreground' => '#fff', 'background' => '#000']);
$rl_1      = $rest_ctrl->check_public_contrast_access($req_rl);
$rl_2      = $rest_ctrl->check_public_contrast_access($req_rl);
$rl_3      = $rest_ctrl->check_public_contrast_access($req_rl);

$stored_transient_keys = array_keys($GLOBALS['mock_transients']);
assert_test(
    'Unauthenticated REST caller is rate-limited at threshold with 429 WP_Error and hashed transient key (no raw IP)',
    true === $rl_1
        && true === $rl_2
        && ($rl_3 instanceof \WP_Error)
        && 'wm_sensory_rate_limited' === $rl_3->get_error_code()
        && 429 === ($rl_3->get_error_data()['status'] ?? 0)
        && count($stored_transient_keys) === 1
        && str_starts_with($stored_transient_keys[0], 'wm_sensory_rl_')
        && !str_contains($stored_transient_keys[0], '203.0.113.42'),
    var_export([$rl_1, $rl_2, $rl_3, $stored_transient_keys], true)
);

$GLOBALS['mock_can_manage'] = true;
$rl_admin = $rest_ctrl->check_public_contrast_access($req_rl);
assert_test(
    'Administrator (manage_options) bypasses public REST rate limit even when transient bucket is full',
    true === $rl_admin,
    var_export($rl_admin, true)
);
unset($mock_filters['wm_sensory_rest_rate_limit'], $_SERVER['REMOTE_ADDR']);
$GLOBALS['mock_transients'] = [];

// 7.2 Settings registration, lifecycle form isolation & opt-in uninstall (D-UNINSTALL)
$GLOBALS['mock_registered_settings'] = [];
AdminDashboard::instance()->register_settings();
assert_test(
    'AdminDashboard registers all four options including wm_sensory_delete_data_on_uninstall (default 0)',
    isset(
        $GLOBALS['mock_registered_settings']['wm_sensory_focus_enabled'],
        $GLOBALS['mock_registered_settings']['wm_sensory_motion_enabled'],
        $GLOBALS['mock_registered_settings']['wm_sensory_ring_color'],
        $GLOBALS['mock_registered_settings']['wm_sensory_delete_data_on_uninstall']
    ) && '0' === ($GLOBALS['mock_registered_settings']['wm_sensory_delete_data_on_uninstall']['args']['default'] ?? null),
    var_export($GLOBALS['mock_registered_settings'], true)
);

$mock_options = [
    'wm_sensory_focus_enabled'  => '1',
    'wm_sensory_motion_enabled' => '1',
    'wm_sensory_ring_color'     => '#123456',
];
$save(['wm_sensory_form' => 'lifecycle', 'wm_sensory_delete_data_on_uninstall' => '1']);
assert_test(
    'Saving lifecycle form writes wm_sensory_delete_data_on_uninstall without altering focus or motion options',
    '1' === ($mock_options['wm_sensory_delete_data_on_uninstall'] ?? null)
        && '1' === ($mock_options['wm_sensory_focus_enabled'] ?? null)
        && '1' === ($mock_options['wm_sensory_motion_enabled'] ?? null)
        && '#123456' === ($mock_options['wm_sensory_ring_color'] ?? null),
    json_encode($mock_options)
);

if (!defined('WP_UNINSTALL_PLUGIN')) {
    define('WP_UNINSTALL_PLUGIN', true);
}
$mock_options = [
    'wm_sensory_focus_enabled'            => '1',
    'wm_sensory_motion_enabled'           => '1',
    'wm_sensory_ring_color'               => '#abcdef',
    'wm_sensory_delete_data_on_uninstall' => '0',
];
require ABSPATH . 'uninstall.php';
assert_test(
    'uninstall.php preserves plugin options by default when wm_sensory_delete_data_on_uninstall is 0',
    '#abcdef' === ($mock_options['wm_sensory_ring_color'] ?? null) && '1' === ($mock_options['wm_sensory_focus_enabled'] ?? null),
    json_encode($mock_options)
);

$mock_options['wm_sensory_delete_data_on_uninstall'] = '1';
require ABSPATH . 'uninstall.php';
assert_test(
    'uninstall.php deletes all four plugin options when wm_sensory_delete_data_on_uninstall is explicitly 1',
    [] === $mock_options,
    json_encode($mock_options)
);

// 7.3 Developer hooks (wm_sensory_ready, wm_sensory_tokens_css, wm_sensory_contrast_result)
$GLOBALS['mock_fired_actions'] = [];
\WMCyber\A11y\Governor\Core\Plugin::instance()->boot();
assert_test(
    'Plugin::boot() fires wm_sensory_ready action',
    1 === ($GLOBALS['mock_fired_actions']['wm_sensory_ready'] ?? 0),
    json_encode($GLOBALS['mock_fired_actions'])
);

$mock_options = ['wm_sensory_focus_enabled' => '1', 'wm_sensory_motion_enabled' => '0'];
$mock_filters['wm_sensory_tokens_css'] = [
    static fn( string $css ): string => $css . "\n:root { --wm-hook-test: 2px; }",
];
ob_start();
\WMCyber\A11y\Governor\Core\Plugin::instance()->inject_governor_styles();
$filtered_head = (string) ob_get_clean();
unset($mock_filters['wm_sensory_tokens_css']);
assert_test(
    'wm_sensory_tokens_css filter customises printed frontend CSS inside <style id="wm-sensory-governor-tokens">',
    str_contains($filtered_head, '--wm-hook-test: 2px;') && str_contains($filtered_head, '<style id="wm-sensory-governor-tokens">'),
    substr($filtered_head, 0, 300)
);

$mock_filters['wm_sensory_contrast_result'] = [
    static function ( array $res ): array {
        $res['filtered_marker'] = 'yes';
        return $res;
    },
];
$filtered_contrast = $calc->calculate_contrast('#ffffff', '#000000');
unset($mock_filters['wm_sensory_contrast_result']);
assert_test(
    'wm_sensory_contrast_result filter customises contrast evaluation array',
    'yes' === ($filtered_contrast['filtered_marker'] ?? null) && 21.0 === $filtered_contrast['ratio'],
    var_export($filtered_contrast, true)
);

// 7.4 Block Bindings (wm-sensory/tokens) & GenerateBlocks 2.x Dynamic Tag ({{wm_sensory_token}})
$mock_options = ['wm_sensory_focus_enabled' => '1', 'wm_sensory_motion_enabled' => '0', 'wm_sensory_ring_color' => '#06b6d4'];
$GLOBALS['mock_block_bindings'] = [];
\GenerateBlocks_Register_Dynamic_Tag::$registered = [];
$bridge = new \WMCyber\A11y\Governor\Integrations\EcosystemBridge();
$bridge->register_block_bindings();
$bridge->register_generateblocks_tag();

$bb_cb = $GLOBALS['mock_block_bindings']['wm-sensory/tokens']['get_value_callback'] ?? null;
assert_test(
    'WP 6.5+ Block Bindings source wm-sensory/tokens registers and resolves token keys accurately',
    is_callable($bb_cb)
        && '#06b6d4' === $bb_cb(['key' => 'ring_color'])
        && '8.24:1' === $bb_cb(['key' => 'ring_contrast'])
        && 'Active' === $bb_cb(['key' => 'focus_status'])
        && 'Disabled' === $bb_cb(['key' => 'motion_status'])
        && null === $bb_cb(['key' => 'nonexistent']),
    var_export($GLOBALS['mock_block_bindings'], true)
);

assert_test(
    'GenerateBlocks 2.x Dynamic Tag wm_sensory_token registers and renders escaped token values',
    isset(\GenerateBlocks_Register_Dynamic_Tag::$registered['wm_sensory_token'])
        && '#06b6d4' === $bridge->filter_generateblocks_tag_output('', 'wm_sensory_token', ['key' => 'ring_color'])
        && 'untouched' === $bridge->filter_generateblocks_tag_output('untouched', 'other_tag', []),
    var_export(\GenerateBlocks_Register_Dynamic_Tag::$registered, true)
);
$mock_options = [];

// ------------------------------------------------------------
// Summary & Exit Code
// ------------------------------------------------------------
echo "\n==============================================================================\n";
echo "  TEST SUITE RESULTS: {$passed_count} PASSED | {$failed_count} FAILED\n";
echo "==============================================================================\n\n";

exit($failed_count === 0 ? 0 : 1);

<?php
/**
 * Plugin Name: GateSpark - Smart Revolut Gateway
 * Plugin URI: https://gatespark.eu
 * Description: Modern Revolut payment gateway with built-in analytics. Better than the official plugin with dashboard widgets, reports, and a clean 2025 UI.
 * Version: 1.0.0
 * Author: GateSpark
 * Author URI: https://gatespark.eu
 * Text Domain: gatespark-revolut
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * WC tested up to: 9.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('GATESPARK_VERSION', '1.0.0');
define('GATESPARK_PLUGIN_FILE', __FILE__);
define('GATESPARK_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GATESPARK_PLUGIN_URL', plugin_dir_url(__FILE__));
define('GATESPARK_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Check if WooCommerce is active
 */
function gatespark_check_woocommerce() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'gatespark_woocommerce_missing_notice');
        return false;
    }
    return true;
}

/**
 * WooCommerce missing notice
 */
function gatespark_woocommerce_missing_notice() {
    ?>
    <div class="notice notice-error">
        <p><?php esc_html_e('GateSpark requires WooCommerce to be installed and activated.', 'gatespark-revolut'); ?></p>
    </div>
    <?php
}

/**
 * Load plugin text domain for translations
 */
function gatespark_load_textdomain() {
    load_plugin_textdomain(
        'gatespark-revolut',
        false,
        dirname(GATESPARK_PLUGIN_BASENAME) . '/languages'
    );
}
add_action('plugins_loaded', 'gatespark_load_textdomain');

/**
 * Initialize the plugin
 */
function gatespark_init() {
    if (!gatespark_check_woocommerce()) {
        return;
    }

    // Load plugin classes
    require_once GATESPARK_PLUGIN_DIR . 'includes/class-gatespark-stats.php';
    require_once GATESPARK_PLUGIN_DIR . 'includes/class-gatespark-api.php';
    require_once GATESPARK_PLUGIN_DIR . 'includes/class-gatespark-gateway.php';
    require_once GATESPARK_PLUGIN_DIR . 'includes/class-gatespark-admin.php';
    require_once GATESPARK_PLUGIN_DIR . 'includes/class-gatespark-dashboard.php';
    require_once GATESPARK_PLUGIN_DIR . 'includes/class-gatespark-reports.php';
    require_once GATESPARK_PLUGIN_DIR . 'includes/class-gatespark-webhooks.php';

    // Initialize admin interface
    if (is_admin()) {
        new GateSpark_Admin();
        new GateSpark_Dashboard();
    }

    // Initialize reports
    new GateSpark_Reports();

    // Initialize webhooks
    new GateSpark_Webhooks();
}
add_action('plugins_loaded', 'gatespark_init', 11);

/**
 * Add the gateway to WooCommerce
 */
function gatespark_add_gateway($gateways) {
    $gateways[] = 'GateSpark_Gateway';
    return $gateways;
}
add_filter('woocommerce_payment_gateways', 'gatespark_add_gateway');

/**
 * Add settings link on plugin page
 */
function gatespark_plugin_action_links($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=gatespark_revolut') . '">' . __('Settings', 'gatespark-revolut') . '</a>';
    $reports_link = '<a href="' . admin_url('admin.php?page=gatespark-reports') . '">' . __('Reports', 'gatespark-revolut') . '</a>';
    $pro_link = '<a href="https://gatespark.eu/pro" target="_blank" style="color:#10b981;font-weight:bold;">' . __('Upgrade to PRO', 'gatespark-revolut') . '</a>';
    array_unshift($links, $settings_link, $reports_link, $pro_link);
    return $links;
}
add_filter('plugin_action_links_' . GATESPARK_PLUGIN_BASENAME, 'gatespark_plugin_action_links');

/**
 * Plugin activation
 */
function gatespark_activate() {
    // Ensure Stats class is loaded
    require_once GATESPARK_PLUGIN_DIR . 'includes/class-gatespark-stats.php';
    
    // Create database tables for stats
    GateSpark_Stats::create_tables();
    
    // Schedule daily stats update
    if (!wp_next_scheduled('gatespark_update_daily_stats')) {
        wp_schedule_event(time(), 'daily', 'gatespark_update_daily_stats');
    }
    
    // Set activation redirect flag
    set_transient('gatespark_activation_redirect', true, 30);
    
    // Flush rewrite rules for REST API
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'gatespark_activate');

/**
 * Plugin deactivation
 */
function gatespark_deactivate() {
    // Remove scheduled events
    wp_clear_scheduled_hook('gatespark_update_daily_stats');
}
register_deactivation_hook(__FILE__, 'gatespark_deactivate');

/**
 * Activation redirect to onboarding
 */
function gatespark_activation_redirect() {
    if (get_transient('gatespark_activation_redirect')) {
        delete_transient('gatespark_activation_redirect');
        if (!isset($_GET['activate-multi'])) {
            wp_safe_redirect(admin_url('admin.php?page=gatespark-onboarding'));
            exit;
        }
    }
}
add_action('admin_init', 'gatespark_activation_redirect');

/**
 * Declare HPOS compatibility
 */
add_action('before_woocommerce_init', function() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

/**
 * Register REST API routes
 */
add_action('rest_api_init', function() {
    register_rest_route('gatespark/v1', '/webhook', array(
        'methods' => 'POST',
        'callback' => 'gatespark_handle_rest_webhook',
        'permission_callback' => '__return_true', // Revolut webhook doesn't use WP auth
    ));
});

/**
 * Handle REST API webhook
 */
function gatespark_handle_rest_webhook($request) {
    require_once GATESPARK_PLUGIN_DIR . 'includes/class-gatespark-webhooks.php';
    $webhook_handler = new GateSpark_Webhooks();
    return $webhook_handler->handle_rest_webhook($request);
}

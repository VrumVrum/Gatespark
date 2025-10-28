<?php
/**
 * GateSpark Admin Class
 * Handles admin UI enhancements and onboarding
 */

if (!defined('ABSPATH')) {
    exit;
}

class GateSpark_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // AJAX handlers
        add_action('wp_ajax_gatespark_test_connection', array($this, 'ajax_test_connection'));
        
        // Add custom admin pages
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Customize payment gateway settings page
        add_action('woocommerce_settings_checkout', array($this, 'maybe_add_custom_ui'), 1);
        
        // Admin notices
        add_action('admin_notices', array($this, 'admin_notices'));
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only on WooCommerce settings page or our custom pages
        if (strpos($hook, 'wc-settings') === false && strpos($hook, 'gatespark') === false) {
            return;
        }
        
        wp_enqueue_style(
            'gatespark-admin',
            GATESPARK_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            GATESPARK_VERSION
        );
        
        wp_enqueue_script(
            'gatespark-admin',
            GATESPARK_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            GATESPARK_VERSION,
            true
        );
        
        wp_localize_script('gatespark-admin', 'gatesparkAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gatespark_admin'),
            'strings' => array(
                'testing' => __('Testing connection...', 'gatespark-revolut'),
                'success' => __('Connection successful!', 'gatespark-revolut'),
                'error' => __('Connection failed', 'gatespark-revolut')
            )
        ));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Reports page
        add_submenu_page(
            'woocommerce',
            __('GateSpark Reports', 'gatespark-revolut'),
            __('GateSpark Reports', 'gatespark-revolut'),
            'manage_woocommerce',
            'gatespark-reports',
            array($this, 'render_reports_page')
        );
        
        // Onboarding page (hidden from menu)
        add_submenu_page(
            null, // No parent = hidden
            __('GateSpark Onboarding', 'gatespark-revolut'),
            __('GateSpark Onboarding', 'gatespark-revolut'),
            'manage_woocommerce',
            'gatespark-onboarding',
            array($this, 'render_onboarding_page')
        );
    }
    
    /**
     * Render reports page
     */
    public function render_reports_page() {
        $reports = new GateSpark_Reports();
        $reports->render_page();
    


echo '<div style="margin-top:40px; padding-top:10px; border-top:1px solid #ddd; font-size:12px; color:#666;">
    <p><strong>Legal Notice:</strong> This plugin is an independent software product developed by third parties and is 
    <strong>not affiliated with, endorsed by, or sponsored by Revolut</strong>. 
    “Revolut” and related trademarks are the property of Revolut Ltd.</p>
</div>';
}
    
    /**
     * Render onboarding page
     */
    public function render_onboarding_page() {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have permission to access this page.', 'gatespark-revolut'));
        

}
        
        // Handle form submission
        if (isset($_POST['gatespark_onboarding_submit'])) {
            check_admin_referer('gatespark_onboarding', 'gatespark_onboarding_nonce');
            
            $this->process_onboarding();
            wp_safe_redirect(admin_url('admin.php?page=wc-settings&tab=checkout&section=gatespark_revolut&onboarding=complete'));
            exit;
        }
        
        // Handle skip
        if (isset($_GET['skip']) && $_GET['skip'] === '1') {
            check_admin_referer('gatespark_skip_onboarding', 'nonce');
            wp_safe_redirect(admin_url('admin.php?page=wc-settings&tab=checkout&section=gatespark_revolut'));
            exit;
        }
        
        $this->render_onboarding_wizard();
    
echo '<div style="margin-top:40px; padding-top:10px; border-top:1px solid #ddd; font-size:12px; color:#666;">
    <p><strong>Legal Notice:</strong> This plugin is an independent software product developed by third parties and is 
    <strong>not affiliated with, endorsed by, or sponsored by Revolut</strong>. 
    “Revolut” and related trademarks are the property of Revolut Ltd.</p>
</div>';
}
    
    /**
     * Render onboarding wizard
     */
    private function render_onboarding_wizard() {
        ?>
        <div class="wrap gatespark-onboarding">
            <style>
                .gatespark-onboarding {
                    max-width: 800px;
                    margin: 50px auto;
                }
                .gatespark-onboarding-card {
                    background: #fff;
                    border-radius: 16px;
                    padding: 48px;
                    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
                }
                .gatespark-onboarding h1 {
                    text-align: center;
                    font-size: 32px;
                    margin-bottom: 16px;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    -webkit-background-clip: text;
                    -webkit-text-fill-color: transparent;
                }
                .gatespark-onboarding .subtitle {
                    text-align: center;
                    color: #6b7280;
                    margin-bottom: 40px;
                    font-size: 16px;
                }
                .onboarding-step {
                    margin-bottom: 32px;
                    padding: 24px;
                    background: #f9fafb;
                    border-radius: 12px;
                }
                .onboarding-step h3 {
                    margin-top: 0;
                    color: #111827;
                    display: flex;
                    align-items: center;
                    gap: 12px;
                }
                .onboarding-step .step-number {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    width: 32px;
                    height: 32px;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: #fff;
                    border-radius: 50%;
                    font-weight: bold;
                    font-size: 16px;
                }
                .onboarding-buttons {
                    display: flex;
                    gap: 16px;
                    justify-content: center;
                    margin-top: 40px;
                }
                .button-hero {
                    padding: 14px 32px !important;
                    font-size: 16px !important;
                    height: auto !important;
                }
            </style>
            
            <div class="gatespark-onboarding-card">
                <h1>🚀 <?php _e('Welcome to GateSpark!', 'gatespark-revolut'); ?></h1>
                <p class="subtitle">
                    <?php _e('Let\'s get you set up in 3 easy steps', 'gatespark-revolut'); ?>
                </p>
                
                <form method="post" action="">
                    <?php wp_nonce_field('gatespark_onboarding', 'gatespark_onboarding_nonce'); ?>
                    
                    <div class="onboarding-step">
                        <h3>
                            <span class="step-number">1</span>
                            <?php _e('Choose Your Mode', 'gatespark-revolut'); ?>
                        </h3>
                        <p><?php _e('Start with sandbox mode for testing, or use live mode for real transactions.', 'gatespark-revolut'); ?></p>
                        <label>
                            <input type="radio" name="sandbox_mode" value="yes" checked>
                            <?php _e('Sandbox Mode (Testing)', 'gatespark-revolut'); ?>
                        </label><br>
                        <label>
                            <input type="radio" name="sandbox_mode" value="no">
                            <?php _e('Live Mode (Production)', 'gatespark-revolut'); ?>
                        </label>
                    </div>
                    
                    <div class="onboarding-step">
                        <h3>
                            <span class="step-number">2</span>
                            <?php _e('Enter Your API Key', 'gatespark-revolut'); ?>
                        </h3>
                        <p>
                            <?php 
                            printf(
                                __('Get your API key from %sRevolut Business Dashboard%s → Settings → Developer API', 'gatespark-revolut'),
                                '<a href="https://business.revolut.com" target="_blank">',
                                '</a>'
                            ); 
                            ?>
                        </p>
                        <input type="password" name="api_key" class="regular-text" placeholder="<?php esc_attr_e('sk_sandbox_xxxxxxxxxxxxxxxxxxxxx', 'gatespark-revolut'); ?>" required>
                    </div>
                    
                    <div class="onboarding-step">
                        <h3>
                            <span class="step-number">3</span>
                            <?php _e('Enable Payment Method', 'gatespark-revolut'); ?>
                        </h3>
                        <p><?php _e('Make GateSpark available as a payment option for your customers.', 'gatespark-revolut'); ?></p>
                        <label>
                            <input type="checkbox" name="enabled" value="yes" checked>
                            <?php _e('Enable Revolut payments on my store', 'gatespark-revolut'); ?>
                        </label>
                    </div>
                    
                    <div class="onboarding-buttons">
                        <button type="submit" name="gatespark_onboarding_submit" class="button button-primary button-hero">
                            <?php _e('Complete Setup →', 'gatespark-revolut'); ?>
                        </button>
                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=gatespark-onboarding&skip=1'), 'gatespark_skip_onboarding', 'nonce'); ?>" class="button button-hero">
                            <?php _e('Skip for Now', 'gatespark-revolut'); ?>
                        </a>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }
    
    /**
     * Process onboarding form
     */
    private function process_onboarding() {
        $gateway = new GateSpark_Gateway();
        
        // Sanitize inputs
        $sandbox_mode = isset($_POST['sandbox_mode']) ? sanitize_text_field($_POST['sandbox_mode']) : 'yes';
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        $enabled = isset($_POST['enabled']) ? 'yes' : 'no';
        
        // Update settings
        $settings = get_option('woocommerce_gatespark_revolut_settings', array());
        
        $settings['sandbox_mode'] = $sandbox_mode;
        $settings['enabled'] = $enabled;
        
        if ($sandbox_mode === 'yes') {
            $settings['sandbox_api_key'] = $api_key;
        } else {
            $settings['live_api_key'] = $api_key;
        }
        
        update_option('woocommerce_gatespark_revolut_settings', $settings);
    }
    
    /**
     * Add custom UI to settings page
     */
    public function maybe_add_custom_ui() {
        global $current_section;
        
        if ($current_section !== 'gatespark_revolut') {
            return;
        }
        
        // Add welcome banner and test connection buttons via JavaScript
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Add test connection buttons after API key fields
            var sandboxField = $('#woocommerce_gatespark_revolut_sandbox_api_key').closest('tr');
            var liveField = $('#woocommerce_gatespark_revolut_live_api_key').closest('tr');
            
            if (sandboxField.length) {
                sandboxField.after(
                    '<tr valign="top" class="gatespark-test-row">' +
                    '<th scope="row" class="titledesc"></th>' +
                    '<td class="forminp">' +
                    '<button type="button" class="button button-secondary gatespark-test-connection" data-mode="sandbox">' +
                    '<span class="dashicons dashicons-yes"></span> ' +
                    '<?php echo esc_js(__('Test Sandbox Connection', 'gatespark-revolut')); ?>' +
                    '</button>' +
                    '<span class="gatespark-test-result"></span>' +
                    '</td>' +
                    '</tr>'
                );
            }
            
            if (liveField.length) {
                liveField.after(
                    '<tr valign="top" class="gatespark-test-row">' +
                    '<th scope="row" class="titledesc"></th>' +
                    '<td class="forminp">' +
                    '<button type="button" class="button button-secondary gatespark-test-connection" data-mode="live">' +
                    '<span class="dashicons dashicons-yes"></span> ' +
                    '<?php echo esc_js(__('Test Live Connection', 'gatespark-revolut')); ?>' +
                    '</button>' +
                    '<span class="gatespark-test-result"></span>' +
                    '</td>' +
                    '</tr>'
                );
            }
            
            // Add welcome banner
            $('.wc-settings-sub-title').first().after(
                '<div class="gatespark-welcome-banner">' +
                '<div class="banner-icon">🚀</div>' +
                '<div class="banner-content">' +
                '<h2><?php echo esc_js(__('Welcome to GateSpark!', 'gatespark-revolut')); ?></h2>' +
                '<p><?php echo esc_js(__('The smart Revolut gateway with built-in analytics. Get instant insights into your payment performance with our beautiful reports dashboard.', 'gatespark-revolut')); ?></p>' +
                '<p><strong><a href="<?php echo esc_url(admin_url('admin.php?page=gatespark-reports')); ?>" class="banner-link">' +
                '<?php echo esc_js(__('View Your Reports Dashboard →', 'gatespark-revolut')); ?></a></strong></p>' +
                '</div>' +
                '</div>'
            );
            
            // Add features box
            $('form.wc-payment-gateway-method-settings').after(
                '<div class="gatespark-features-box">' +
                '<h3><?php echo esc_js(__('✨ What Makes GateSpark Different?', 'gatespark-revolut')); ?></h3>' +
                '<div class="features-grid">' +
                '<div class="feature-item">' +
                '<div class="feature-icon">📊</div>' +
                '<strong><?php echo esc_js(__('Built-in Analytics', 'gatespark-revolut')); ?></strong>' +
                '<span><?php echo esc_js(__('See revenue, success rates, and trends at a glance', 'gatespark-revolut')); ?></span>' +
                '</div>' +
                '<div class="feature-item">' +
                '<div class="feature-icon">🎨</div>' +
                '<strong><?php echo esc_js(__('Modern UI', 'gatespark-revolut')); ?></strong>' +
                '<span><?php echo esc_js(__('Clean, organized interface built for 2025', 'gatespark-revolut')); ?></span>' +
                '</div>' +
                '<div class="feature-item">' +
                '<div class="feature-icon">⚡</div>' +
                '<strong><?php echo esc_js(__('Test Connection', 'gatespark-revolut')); ?></strong>' +
                '<span><?php echo esc_js(__('One-click API verification right here', 'gatespark-revolut')); ?></span>' +
                '</div>' +
                '<div class="feature-item">' +
                '<div class="feature-icon">💎</div>' +
                '<strong><?php echo esc_js(__('Want More?', 'gatespark-revolut')); ?></strong>' +
                '<span><a href="https://gatespark.eu/pro" target="_blank"><?php echo esc_js(__('Upgrade to PRO', 'gatespark-revolut')); ?></a> <?php echo esc_js(__('for Apple Pay, Google Pay & advanced features', 'gatespark-revolut')); ?></span>' +
                '</div>' +
                '</div>' +
                '</div>'
            );
        });
        </script>
        <?php
    }
    
    /**
     * Admin notices
     */
    public function admin_notices() {
        // Onboarding complete notice
        if (isset($_GET['onboarding']) && $_GET['onboarding'] === 'complete') {
            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <strong><?php _e('🎉 GateSpark Setup Complete!', 'gatespark-revolut'); ?></strong>
                    <?php 
                    printf(
                        __('Your Revolut gateway is now active. %sView your reports dashboard%s to see your payment analytics.', 'gatespark-revolut'),
                        '<a href="' . admin_url('admin.php?page=gatespark-reports') . '">',
                        '</a>'
                    );
                    ?>
                </p>
            </div>
            <?php
        }
    }
    
    /**
     * AJAX: Test connection
     */
    public function ajax_test_connection() {
        check_ajax_referer('gatespark_admin', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Permission denied', 'gatespark-revolut')));
        }
        
        $mode = isset($_POST['mode']) ? sanitize_text_field($_POST['mode']) : 'sandbox';
        $is_sandbox = ($mode === 'sandbox');
        
        // Get API key from POST or from saved settings
        if (isset($_POST['api_key']) && !empty($_POST['api_key'])) {
            $api_key = sanitize_text_field($_POST['api_key']);
        } else {
            $gateway = new GateSpark_Gateway();
            $api_key = $is_sandbox 
                ? $gateway->get_option('sandbox_api_key')
                : $gateway->get_option('live_api_key');
        }
        
        if (empty($api_key)) {
            wp_send_json_error(array(
                'message' => __('Please enter an API key first.', 'gatespark-revolut')
            ));
        }
        
        // Test connection
        $api = new GateSpark_API($api_key, $is_sandbox);
        $result = $api->test_connection();
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
}

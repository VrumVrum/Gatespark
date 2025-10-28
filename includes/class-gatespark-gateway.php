<?php
/**
 * GateSpark Gateway Class
 * Main WooCommerce payment gateway integration
 */

if (!defined('ABSPATH')) {
    exit;
}

class GateSpark_Gateway extends WC_Payment_Gateway {
    
    private $api;
    private $webhook_secret;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'gatespark_revolut';
        $this->icon = '';
        $this->has_fields = false;
        $this->method_title = __('GateSpark - Revolut', 'gatespark-revolut');
        $this->method_description = __('Accept card payments via Revolut with built-in analytics and modern UI.', 'gatespark-revolut');
        
        // Supports
        $this->supports = array(
            'products',
            'refunds'
        );
        
        // Load settings
        $this->init_form_fields();
        $this->init_settings();
        
        // Get settings
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->sandbox_mode = $this->get_option('sandbox_mode') === 'yes';
        $this->webhook_secret = $this->get_option('webhook_secret');
        $this->api_key = $this->sandbox_mode 
            ? $this->get_option('sandbox_api_key')
            : $this->get_option('live_api_key');
        
        // Initialize API
        if (!empty($this->api_key)) {
            $this->api = new GateSpark_API($this->api_key, $this->sandbox_mode);
        }
        
        // Hooks
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_api_gatespark_revolut_webhook', array($this, 'handle_webhook'));
        add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));
        
        // Generate webhook secret if not set
        if (empty($this->webhook_secret)) {
            $this->update_option('webhook_secret', bin2hex(random_bytes(32)));
            $this->webhook_secret = $this->get_option('webhook_secret');
        }
    }
    
    /**
     * Initialize gateway settings form fields
     */
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'gatespark-revolut'),
                'type' => 'checkbox',
                'label' => __('Enable Revolut payments', 'gatespark-revolut'),
                'default' => 'no'
            ),
            'title' => array(
                'title' => __('Title', 'gatespark-revolut'),
                'type' => 'text',
                'description' => __('Payment method title shown to customers during checkout.', 'gatespark-revolut'),
                'default' => __('Credit/Debit Card (Revolut)', 'gatespark-revolut'),
                'desc_tip' => true
            ),
            'description' => array(
                'title' => __('Description', 'gatespark-revolut'),
                'type' => 'textarea',
                'description' => __('Payment method description shown to customers.', 'gatespark-revolut'),
                'default' => __('Pay securely with your credit or debit card.', 'gatespark-revolut'),
                'desc_tip' => true
            ),
            'sandbox_mode' => array(
                'title' => __('Sandbox Mode', 'gatespark-revolut'),
                'type' => 'checkbox',
                'label' => __('Enable sandbox mode for testing', 'gatespark-revolut'),
                'default' => 'yes',
                'description' => __('Use sandbox API for testing. Disable for live transactions.', 'gatespark-revolut')
            ),
            'sandbox_api_key' => array(
                'title' => __('Sandbox API Key', 'gatespark-revolut'),
                'type' => 'password',
                'description' => __('Get your sandbox API key from Revolut Business dashboard.', 'gatespark-revolut'),
                'desc_tip' => true
            ),
            'live_api_key' => array(
                'title' => __('Live API Key', 'gatespark-revolut'),
                'type' => 'password',
                'description' => __('Get your live API key from Revolut Business dashboard.', 'gatespark-revolut'),
                'desc_tip' => true
            ),
            'webhook_url' => array(
                'title' => __('Webhook URL', 'gatespark-revolut'),
                'type' => 'text',
                'description' => sprintf(
                    __('Copy this URL to your Revolut dashboard: %s', 'gatespark-revolut'),
                    '<br><code>' . home_url('/') . '?wc-api=gatespark_revolut_webhook</code><br>' .
                    __('REST API (recommended):', 'gatespark-revolut') . ' <code>' . rest_url('gatespark/v1/webhook') . '</code>'
                ),
                'custom_attributes' => array(
                    'readonly' => 'readonly'
                ),
                'default' => home_url('/') . '?wc-api=gatespark_revolut_webhook'
            ),
            'webhook_secret' => array(
                'title' => __('Webhook Secret', 'gatespark-revolut'),
                'type' => 'password',
                'description' => __('Automatically generated secret for webhook signature verification. Keep this secure!', 'gatespark-revolut'),
                'desc_tip' => true,
                'custom_attributes' => array(
                    'readonly' => 'readonly'
                )
            ),
            'order_prefix' => array(
                'title' => __('Order Prefix', 'gatespark-revolut'),
                'type' => 'text',
                'description' => __('Optional prefix for order references.', 'gatespark-revolut'),
                'default' => 'WC-',
                'desc_tip' => true
            ),
            'debug_mode' => array(
                'title' => __('Debug Mode', 'gatespark-revolut'),
                'type' => 'checkbox',
                'label' => __('Enable debug logging', 'gatespark-revolut'),
                'default' => 'yes',
                'description' => __('Log API requests and responses for debugging. Check WooCommerce > Status > Logs.', 'gatespark-revolut')
            ),
            'powered_by' => array(
                'title' => __('Branding', 'gatespark-revolut'),
                'type' => 'checkbox',
                'label' => __('Show "Powered by GateSpark" on checkout page', 'gatespark-revolut'),
                'default' => 'yes',
                'description' => sprintf(
                    __('Support us by showing our branding! %sUpgrade to PRO%s to remove this.', 'gatespark-revolut'),
                    '<a href="https://gatespark.eu/pro" target="_blank">',
                    '</a>'
                )
            )
        );
    }
    
    /**
     * Check if gateway is available
     */
    public function is_available() {
        if ($this->enabled !== 'yes') {
            return false;
        }
        
        if (empty($this->api_key)) {
            return false;
        }
        
        // Check currency
        $currency = get_woocommerce_currency();
        $supported = GateSpark_API::get_supported_currencies();
        
        if (!in_array($currency, $supported)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Process payment
     */
    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return array('result' => 'failure');
        }
        
        // Prepare order data
        $order_data = array(
            'amount' => round($order->get_total() * 100),
            'currency' => $order->get_currency(),
            'merchant_order_ext_ref' => sanitize_text_field($this->get_option('order_prefix') . $order->get_order_number()),
            'description' => sprintf(__('Order #%s', 'gatespark-revolut'), $order->get_order_number()),
            'customer_email' => sanitize_email($order->get_billing_email()),
            'settlement_currency' => $order->get_currency(),
            'capture_mode' => 'AUTOMATIC'
        );
        
        // Create Revolut order
        $result = $this->api->create_order($order_data);
        
        if (is_wp_error($result)) {
            wc_add_notice($result->get_error_message(), 'error');
            $this->log('Payment failed: ' . $result->get_error_message(), $order_id);
            return array('result' => 'failure');
        }
        
        // Save order data
        $order->update_meta_data('_gatespark_revolut_order_id', sanitize_text_field($result['id']));
        $order->update_meta_data('_gatespark_revolut_public_id', sanitize_text_field($result['public_id']));
        $order->save();
        
        // Add order note
        $order->add_order_note(
            sprintf(__('Revolut order created: %s', 'gatespark-revolut'), sanitize_text_field($result['id']))
        );
        
        // Log stats
        GateSpark_Stats::log_transaction($order, 'pending');
        
        $this->log('Order created successfully', $order_id);
        
        // Redirect to payment
        return array(
            'result' => 'success',
            'redirect' => esc_url_raw($result['checkout_url'])
        );
    }
    
    /**
     * Process refund
     */
    public function process_refund($order_id, $amount = null, $reason = '') {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return new WP_Error('error', __('Order not found.', 'gatespark-revolut'));
        }
        
        $revolut_order_id = $order->get_meta('_gatespark_revolut_order_id');
        
        if (empty($revolut_order_id)) {
            return new WP_Error('error', __('Revolut order ID not found.', 'gatespark-revolut'));
        }
        
        // Prepare refund amount
        $refund_amount = $amount ? round($amount * 100) : round($order->get_total() * 100);
        
        // Process refund
        $result = $this->api->refund_order($revolut_order_id, $refund_amount, sanitize_text_field($reason));
        
        if (is_wp_error($result)) {
            $this->log('Refund failed: ' . $result->get_error_message(), $order_id);
            return $result;
        }
        
        // Add order note
        $order->add_order_note(
            sprintf(
                __('Revolut refund processed: %s %s. Reason: %s', 'gatespark-revolut'),
                wc_price($amount),
                $order->get_currency(),
                sanitize_text_field($reason)
            )
        );
        
        // Log refund
        GateSpark_Stats::log_transaction($order, 'refunded', $amount);
        
        $this->log('Refund processed successfully', $order_id);
        
        return true;
    }
    
    /**
     * Handle webhook (WC API method)
     */
    public function handle_webhook() {
        $payload = file_get_contents('php://input');
        $signature = isset($_SERVER['HTTP_X_REVOLUT_SIGNATURE']) ? $_SERVER['HTTP_X_REVOLUT_SIGNATURE'] : '';
        
        // Verify webhook signature
        if (!$this->verify_webhook_signature($payload, $signature)) {
            $this->log('Webhook signature verification failed');
            status_header(401);
            exit;
        }
        
        $data = json_decode($payload, true);
        
        if (empty($data)) {
            $this->log('Webhook error: Empty payload');
            status_header(400);
            exit;
        }
        
        $this->log('Webhook received', 0, $data);
        
        // Process webhook
        $webhook_handler = new GateSpark_Webhooks();
        $webhook_handler->process_webhook($data);
        
        status_header(200);
        exit;
    }
    
    /**
     * Verify webhook signature (HMAC)
     */
    private function verify_webhook_signature($payload, $signature) {
        if (empty($this->webhook_secret)) {
            return false;
        }
        
        $expected_signature = hash_hmac('sha256', $payload, $this->webhook_secret);
        
        return hash_equals($expected_signature, $signature);
    }
    
    /**
     * Thank you page
     */
    public function thankyou_page($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return;
        }
        
        // Show powered by message
        if ($this->get_option('powered_by') === 'yes') {
            echo '<div class="gatespark-powered-by" style="text-align:center;margin-top:20px;padding:15px;background:#f9fafb;border-radius:8px;">';
            echo '<p style="margin:0;color:#6b7280;font-size:13px;">';
            printf(
                __('Payment processed securely by %s', 'gatespark-revolut'),
                '<a href="https://gatespark.eu" target="_blank" style="color:#667eea;font-weight:600;text-decoration:none;">GateSpark</a>'
            );
            echo '</p>';
            echo '</div>';
        }
    }
    
    /**
     * Validate admin options
     */
    public function validate_text_field($key, $value) {
        return sanitize_text_field($value);
    }
    
    public function validate_textarea_field($key, $value) {
        return sanitize_textarea_field($value);
    }
    
    /**
     * Log debug messages
     */
    private function log($title, $order_id = 0, $message = '') {
        if ($this->get_option('debug_mode') !== 'yes') {
            return;
        }
        
        $logger = wc_get_logger();
        $context = array('source' => 'gatespark-gateway');
        
        if (is_array($message) || is_object($message)) {
            $message = print_r($message, true);
        }
        
        $log_message = $title;
        if ($order_id) {
            $log_message .= ' [Order #' . $order_id . ']';
        }
        if ($message) {
            $log_message .= ': ' . $message;
        }
        
        $logger->debug($log_message, $context);
    }
}

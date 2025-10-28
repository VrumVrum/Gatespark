<?php
/**
 * GateSpark API Class
 * Handles communication with Revolut API
 */

if (!defined('ABSPATH')) {
    exit;
}

class GateSpark_API {
    
    private $api_key;
    private $is_sandbox;
    private $base_url;
    
    /**
     * Constructor
     */
    public function __construct($api_key, $is_sandbox = false) {
        $this->api_key = sanitize_text_field($api_key);
        $this->is_sandbox = (bool) $is_sandbox;
        $this->base_url = $is_sandbox 
            ? 'https://sandbox-merchant.revolut.com/api/1.0' 
            : 'https://merchant.revolut.com/api/1.0';
    }
    
    /**
     * Test API connection
     */
    public function test_connection() {
        $response = $this->make_request('GET', '/orders', array('limit' => 1));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }
        
        return array(
            'success' => true,
            'message' => __('Connection successful! Your API credentials are working.', 'gatespark-revolut')
        );
    }
    
    /**
     * Create a payment order
     */
    public function create_order($order_data) {
        return $this->make_request('POST', '/orders', $order_data);
    }
    
    /**
     * Get order details
     */
    public function get_order($order_id) {
        return $this->make_request('GET', '/orders/' . sanitize_text_field($order_id));
    }
    
    /**
     * Capture an order
     */
    public function capture_order($order_id, $amount = null) {
        $data = array();
        if ($amount !== null) {
            $data['amount'] = absint($amount);
        }
        return $this->make_request('POST', '/orders/' . sanitize_text_field($order_id) . '/capture', $data);
    }
    
    /**
     * Refund an order
     */
    public function refund_order($order_id, $amount, $reason = '') {
        $data = array(
            'amount' => absint($amount),
            'description' => sanitize_text_field($reason)
        );
        return $this->make_request('POST', '/orders/' . sanitize_text_field($order_id) . '/refund', $data);
    }
    
    /**
     * Cancel an order
     */
    public function cancel_order($order_id) {
        return $this->make_request('DELETE', '/orders/' . sanitize_text_field($order_id));
    }
    
    /**
     * Make API request
     */
    private function make_request($method, $endpoint, $data = array()) {
        $url = $this->base_url . $endpoint;
        
        $args = array(
            'method' => $method,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
                'User-Agent' => 'GateSpark/' . GATESPARK_VERSION . ' (WordPress/' . get_bloginfo('version') . ')'
            ),
            'timeout' => 30,
            'sslverify' => true
        );
        
        if ($method === 'POST' && !empty($data)) {
            $args['body'] = wp_json_encode($data);
        }
        
        if ($method === 'GET' && !empty($data)) {
            $url = add_query_arg($data, $url);
        }
        
        // Log request (sanitized)
        $this->log('API Request', array(
            'method' => $method,
            'endpoint' => $endpoint,
            'data_keys' => !empty($data) ? array_keys($data) : array()
        ));
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            $this->log('API Error', $response->get_error_message());
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $code = wp_remote_retrieve_response_code($response);
        
        // Log response
        $this->log('API Response', array(
            'code' => $code,
            'body_length' => strlen($body)
        ));
        
        if ($code >= 400) {
            $error_data = json_decode($body, true);
            $error_message = isset($error_data['message']) 
                ? sanitize_text_field($error_data['message'])
                : __('API request failed', 'gatespark-revolut');
            
            return new WP_Error('api_error', $error_message, array('code' => $code));
        }
        
        return json_decode($body, true);
    }
    
    /**
     * Log API activity
     */
    private function log($title, $message) {
        $logger = wc_get_logger();
        $context = array('source' => 'gatespark-api');
        
        if (is_array($message) || is_object($message)) {
            $message = print_r($message, true);
        }
        
        $logger->debug($title . ': ' . $message, $context);
    }
    
    /**
     * Get supported currencies
     */
    public static function get_supported_currencies() {
        return array(
            'AED', 'AUD', 'BGN', 'CAD', 'CHF', 'CZK', 'DKK', 
            'EUR', 'GBP', 'HKD', 'HRK', 'HUF', 'ISK', 'JPY',
            'NOK', 'NZD', 'PLN', 'RON', 'SAR', 'SEK', 'SGD',
            'THB', 'TRY', 'USD', 'ZAR'
        );
    }
}

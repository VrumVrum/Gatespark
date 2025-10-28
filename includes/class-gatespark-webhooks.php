<?php
/**
 * GateSpark – Revolut Gateway for WooCommerce
 *
 * Legal Notice:
 * This file is part of the GateSpark plugin.
 * It is not affiliated with, endorsed by, or sponsored by Revolut.
 * “Revolut” is a trademark of Revolut Ltd.
 */

/**
 * GateSpark Webhooks Class
 * Handles Revolut webhook notifications
 */

if (!defined('ABSPATH')) {
    exit;
}

class GateSpark_Webhooks {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        add_action('woocommerce_api_gatespark_revolut_webhook', array($this, 'handle_webhook'));
    }


    /**
     * Register REST routes
     */
    public function register_rest_routes() {
        register_rest_route(
            'gatespark/v1',
            '/webhook',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'handle_rest_webhook'),
                'permission_callback' => '__return_true',
            )
        );
    }

    
    /**
     * Handle webhook (WC API method)
     */
    public function handle_webhook() {
        $payload = file_get_contents('php://input');
        $data = json_decode($payload, true);
        
        if (empty($data)) {
            $this->log('Webhook error: Empty payload');
            status_header(400);
            exit;
        }
        
        
        $result = $this->process_webhook($data);

        if (is_wp_error($result)) {
            $this->log('Webhook processing failed', $result->get_error_message());
            status_header(400);
            echo wp_json_encode(
                array(
                    'success' => false,
                    'message' => $result->get_error_message(),
                )
            );
            exit;
        }

        status_header(200);
        echo wp_json_encode(
            array(
                'success' => true,
                'message' => __('Webhook processed successfully', 'gatespark-revolut'),
            )
        );
        exit;
    }
    /**
     * Handle REST API webhook
     */
    public function handle_rest_webhook($request) {
        // Basic rate limiting by IP
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
        $key = 'gatespark_webhook_rate_' . md5($ip);
        $limit = 10; // max requests per minute per IP
        $window = 60; // seconds

        $requests = get_transient($key);
        if ($requests === false) {
            $requests = 0;
        }
        if ($requests >= $limit) {
            return new WP_Error(
                'rate_limited',
                __('Too many webhook requests. Please try again later.', 'gatespark-revolut'),
                array('status' => 429)
            );
        }
        set_transient($key, $requests + 1, $window);

        $data = $request->get_json_params();
        $payload = wp_json_encode($data);
        $signature = $request->get_header('X-Revolut-Signature');

        if (empty($data) || empty($signature)) {
            return new WP_Error(
                'invalid_webhook',
                __('Missing payload or signature', 'gatespark-revolut'),
                array('status' => 400)
            );
        }

        // Verify HMAC signature via Gateway secret
        if (!class_exists('GateSpark_Gateway')) {
            return new WP_Error(
                'gateway_missing',
                __('Payment gateway not loaded', 'gatespark-revolut'),
                array('status' => 500)
            );
        }

        $gateway = new GateSpark_Gateway();
        if (!method_exists($gateway, 'verify_webhook_signature') || !$gateway->verify_webhook_signature($payload, $signature)) {
            return new WP_Error(
                'invalid_signature',
                __('Invalid signature', 'gatespark-revolut'),
                array('status' => 401)
            );
        }

        $result = $this->process_webhook($data);
        if (is_wp_error($result)) {
            return $result;
        }

        return new WP_REST_Response(
            array(
                'success' => true,
                'message' => __('Webhook processed successfully', 'gatespark-revolut'),
            ),
            200
        );
    }
    
    /**
     * Process webhook data
     */
    public function process_webhook($data) {
        if (!isset($data['event']) || !isset($data['order_id'])) {
            $this->log('Webhook error: Missing required fields');
            return new WP_Error('missing_fields', __('Missing required webhook fields', 'gatespark-revolut'));
        }
        
        $event = sanitize_text_field($data['event']);
        $revolut_order_id = sanitize_text_field($data['order_id']);
        
        // Find WooCommerce order using HPOS-compatible method
        $orders = wc_get_orders(array(
            'meta_key' => '_gatespark_revolut_order_id',
            'meta_value' => $revolut_order_id,
            'limit' => 1,
            'return' => 'objects'
        ));
        
        if (empty($orders)) {
            return new WP_Error('order_not_found', __('Order not found', 'gatespark-revolut'));
        }
        
        $order = $orders[0];
        
        // Prevent duplicate processing
        $processed_events = $order->get_meta('_gatespark_processed_events');
        if (!is_array($processed_events)) {
            $processed_events = array();
        }
        
        $event_id = $event . '_' . $revolut_order_id . '_' . (isset($data['timestamp']) ? $data['timestamp'] : time());
        
        if (in_array($event_id, $processed_events)) {
            return true;
        }
        
        // Mark event as processed
        $processed_events[] = $event_id;
        $order->update_meta_data('_gatespark_processed_events', $processed_events);
        $order->save();
        
        // Process based on event type
        switch ($event) {
            case 'ORDER_COMPLETED':
                $result = $this->handle_order_completed($order, $data);
                break;
                
            case 'ORDER_AUTHORISED':
                $result = $this->handle_order_authorised($order, $data);
                break;
                
            case 'ORDER_PAYMENT_FAILED':
                $result = $this->handle_order_failed($order, $data);
                break;
                
            case 'ORDER_CANCELLED':
                $result = $this->handle_order_cancelled($order, $data);
                break;
                
            default:
                $result = new WP_Error('unknown_event', __('Unknown event type', 'gatespark-revolut'));
                break;
        }
        
        return $result;
    }
    
    /**
     * Handle order completed
     */
    private function handle_order_completed($order, $data) {
        if ($order->get_status() === 'completed' || $order->get_status() === 'processing') {
            $this->log('Order already processed: ' . $order->get_id());
            return true; // Already processed
        }
        
        $order->payment_complete();
        $order->add_order_note(
            sprintf(
                __('Revolut payment completed. Transaction ID: %s', 'gatespark-revolut'),
                isset($data['order_id']) ? sanitize_text_field($data['order_id']) : 'N/A'
            )
        );
        
        // Log successful transaction
        GateSpark_Stats::log_transaction($order, 'completed');
        
        
        return true;
    }
    
    /**
     * Handle order authorised
     */
    private function handle_order_authorised($order, $data) {
        if ($order->get_status() !== 'pending') {
            $this->log('Order not pending: ' . $order->get_id());
            return true; // Already processed
        }
        
        $order->update_status('on-hold', __('Payment authorised, waiting for capture.', 'gatespark-revolut'));
        $order->add_order_note(
            sprintf(
                __('Revolut payment authorised. Transaction ID: %s', 'gatespark-revolut'),
                isset($data['order_id']) ? sanitize_text_field($data['order_id']) : 'N/A'
            )
        );
        
        
        return true;
    }
    
    /**
     * Handle order failed
     */
    private function handle_order_failed($order, $data) {
        if ($order->get_status() === 'failed') {
            $this->log('Order already failed: ' . $order->get_id());
            return true; // Already processed
        }
        
        $reason = isset($data['reason']) ? sanitize_text_field($data['reason']) : __('Unknown reason', 'gatespark-revolut');
        
        $order->update_status('failed', sprintf(__('Payment failed: %s', 'gatespark-revolut'), $reason));
        $order->add_order_note(
            sprintf(
                __('Revolut payment failed. Reason: %s. Transaction ID: %s', 'gatespark-revolut'),
                $reason,
                isset($data['order_id']) ? sanitize_text_field($data['order_id']) : 'N/A'
            )
        );
        
        // Log failed transaction
        GateSpark_Stats::log_transaction($order, 'failed');
        
        
        return true;
    }
    
    /**
     * Handle order cancelled
     */
    private function handle_order_cancelled($order, $data) {
        if ($order->get_status() === 'cancelled') {
            $this->log('Order already cancelled: ' . $order->get_id());
            return true; // Already processed
        }
        
        $order->update_status('cancelled', __('Payment cancelled by customer.', 'gatespark-revolut'));
        $order->add_order_note(
            sprintf(
                __('Revolut payment cancelled. Transaction ID: %s', 'gatespark-revolut'),
                isset($data['order_id']) ? sanitize_text_field($data['order_id']) : 'N/A'
            )
        );
        
        
        return true;
    }
    
    /**
     * Log webhook activity
     */
    private function log($title, $message = '') {
        $logger = wc_get_logger();
        $context = array('source' => 'gatespark-webhook');
        
        if (is_array($message) || is_object($message)) {
            $message = print_r($message, true);
        }
        
        $log_message = $title;
        if ($message) {
            $log_message .= ': ' . $message;
        }
        
        $logger->debug($log_message, $context);
    }
}
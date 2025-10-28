<?php
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
        add_action('woocommerce_api_gatespark_revolut_webhook', array($this, 'handle_webhook'));
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
        
        $this->log('Webhook received', $data);
        
        // Process webhook
        $this->process_webhook($data);
        
        status_header(200);
        exit;
    }
    
    /**
     * Handle REST API webhook
     */
    public function handle_rest_webhook($request) {
        $data = $request->get_json_params();
        
        if (empty($data)) {
            $this->log('REST Webhook error: Empty payload');
            return new WP_Error('empty_payload', __('Empty webhook payload', 'gatespark-revolut'), array('status' => 400));
        }
        
        $this->log('REST Webhook received', $data);
        
        // Process webhook
        $result = $this->process_webhook($data);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'message' => __('Webhook processed successfully', 'gatespark-revolut')
        ));
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
            $this->log('Webhook error: Order not found for Revolut ID ' . $revolut_order_id);
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
            $this->log('Webhook: Duplicate event ignored - ' . $event_id);
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
                $this->log('Webhook: Unknown event type ' . $event);
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
        
        $this->log('Order completed: ' . $order->get_id());
        
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
        
        $this->log('Order authorised: ' . $order->get_id());
        
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
        
        $this->log('Order failed: ' . $order->get_id());
        
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
        
        $this->log('Order cancelled: ' . $order->get_id());
        
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

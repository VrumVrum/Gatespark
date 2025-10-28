<?php
/**
 * GateSpark Dashboard Widget
 * Displays payment stats on WordPress dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}

class GateSpark_Dashboard {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
    }
    
    /**
     * Add dashboard widget
     */
    public function add_dashboard_widget() {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }
        
        wp_add_dashboard_widget(
            'gatespark_stats',
            __('💳 Revolut Payments (GateSpark)', 'gatespark-revolut'),
            array($this, 'render_widget')
        );
    }
    
    /**
     * Render dashboard widget
     */
    public function render_widget() {
        global $wpdb;
        
        // Get today's stats
        $today = current_time('Y-m-d');
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}gatespark_daily_stats WHERE stat_date = %s",
            $today
        ));
        
        // Get today's orders directly (for real-time data)
        $today_orders = wc_get_orders(array(
            'payment_method' => 'gatespark_revolut',
            'date_created' => '>=' . strtotime('today'),
            'limit' => -1,
            'return' => 'ids'
        ));
        
        $today_revenue = 0;
        $today_count = count($today_orders);
        $today_successful = 0;
        
        foreach ($today_orders as $order_id) {
            $order = wc_get_order($order_id);
            if ($order && in_array($order->get_status(), array('processing', 'completed'))) {
                $today_revenue += $order->get_total();
                $today_successful++;
            }
        }
        
        $success_rate = $today_count > 0 ? round(($today_successful / $today_count) * 100, 1) : 0;
        $avg_order = $today_successful > 0 ? $today_revenue / $today_successful : 0;
        
        ?>
        <div class="gatespark-dashboard-widget">
            <style>
                .gatespark-dashboard-widget {
                    padding: 10px 0;
                }
                .gatespark-stats-grid {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 15px;
                    margin: 15px 0;
                }
                .gatespark-stat-box {
                    background: #f9f9f9;
                    padding: 15px;
                    border-radius: 4px;
                    border-left: 3px solid #2271b1;
                }
                .gatespark-stat-label {
                    font-size: 12px;
                    color: #666;
                    margin-bottom: 5px;
                }
                .gatespark-stat-value {
                    font-size: 24px;
                    font-weight: bold;
                    color: #2271b1;
                }
                .gatespark-widget-footer {
                    text-align: center;
                    margin-top: 15px;
                    padding-top: 15px;
                    border-top: 1px solid #ddd;
                }
                .gatespark-widget-footer a {
                    text-decoration: none;
                }
            </style>
            
            <div class="gatespark-stats-grid">
                <div class="gatespark-stat-box">
                    <div class="gatespark-stat-label"><?php _e('Revenue Today', 'gatespark-revolut'); ?></div>
                    <div class="gatespark-stat-value">
                        <?php echo wc_price($today_revenue); ?>
                    </div>
                </div>
                
                <div class="gatespark-stat-box">
                    <div class="gatespark-stat-label"><?php _e('Transactions', 'gatespark-revolut'); ?></div>
                    <div class="gatespark-stat-value">
                        <?php echo number_format($today_count); ?>
                    </div>
                </div>
                
                <div class="gatespark-stat-box">
                    <div class="gatespark-stat-label"><?php _e('Average Order', 'gatespark-revolut'); ?></div>
                    <div class="gatespark-stat-value">
                        <?php echo wc_price($avg_order); ?>
                    </div>
                </div>
                
                <div class="gatespark-stat-box">
                    <div class="gatespark-stat-label"><?php _e('Success Rate', 'gatespark-revolut'); ?></div>
                    <div class="gatespark-stat-value">
                        <?php echo $success_rate; ?>%
                    </div>
                </div>
            </div>
            
            <?php if ($today_count === 0): ?>
                <p style="text-align: center; color: #666; margin: 20px 0;">
                    <?php _e('No Revolut transactions yet today.', 'gatespark-revolut'); ?>
                </p>
            <?php endif; ?>
            
            <div class="gatespark-widget-footer">
                <a href="<?php echo admin_url('admin.php?page=gatespark-reports'); ?>" class="button button-primary">
                    <?php _e('View Full Reports →', 'gatespark-revolut'); ?>
                </a>
            </div>
        </div>
        <?php
    }
}

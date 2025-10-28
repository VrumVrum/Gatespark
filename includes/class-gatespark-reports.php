<?php
/**
 * GateSpark Reports Class
 * Handles analytics and reporting
 */

if (!defined('ABSPATH')) {
    exit;
}

class GateSpark_Reports {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Schedule daily stats update
        add_action('gatespark_update_daily_stats', array($this, 'update_daily_stats'));
        
        // AJAX handlers
        add_action('wp_ajax_gatespark_export_csv', array($this, 'export_csv'));
    }
    
    /**
     * Render reports page
     */
    public function render_page() {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have permission to access this page.', 'gatespark-revolut'));
        }
        
        // Enqueue Chart.js
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js', array(), '4.4.0', true);
        
        wp_enqueue_style('gatespark-reports', GATESPARK_PLUGIN_URL . 'assets/css/reports.css', array(), GATESPARK_VERSION);
        wp_enqueue_script('gatespark-reports', GATESPARK_PLUGIN_URL . 'assets/js/reports.js', array('jquery', 'chart-js'), GATESPARK_VERSION, true);
        
        // Get report data
        $period = isset($_GET['period']) ? sanitize_text_field($_GET['period']) : '7days';
        
        // Validate period
        if (!in_array($period, array('7days', '30days'), true)) {
            $period = '7days';
        }
        
        $report_data = $this->get_report_data($period);
        
        wp_localize_script('gatespark-reports', 'gatesparkReports', array(
            'chartData' => $report_data['chart_data'],
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gatespark_reports')
        ));
        
        ?>
        <div class="wrap gatespark-reports-wrap">
            <div class="gatespark-header">
                <h1><?php _e('GateSpark Reports', 'gatespark-revolut'); ?></h1>
                <p class="subtitle"><?php _e('Payment analytics and insights for your store', 'gatespark-revolut'); ?></p>
            </div>
            
            <div class="gatespark-period-selector">
                <a href="<?php echo esc_url(admin_url('admin.php?page=gatespark-reports&period=7days')); ?>" 
                   class="period-button <?php echo $period === '7days' ? 'active' : ''; ?>">
                    <?php _e('Last 7 Days', 'gatespark-revolut'); ?>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=gatespark-reports&period=30days')); ?>" 
                   class="period-button <?php echo $period === '30days' ? 'active' : ''; ?>">
                    <?php _e('Last 30 Days', 'gatespark-revolut'); ?>
                </a>
            </div>
            
            <!-- Summary Stats -->
            <div class="gatespark-stats-container">
                <div class="gatespark-stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-content">
                        <div class="stat-label"><?php _e('Total Revenue', 'gatespark-revolut'); ?></div>
                        <div class="stat-value"><?php echo wc_price($report_data['total_revenue']); ?></div>
                    </div>
                </div>
                
                <div class="gatespark-stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <div class="stat-label"><?php _e('Transactions', 'gatespark-revolut'); ?></div>
                        <div class="stat-value"><?php echo number_format($report_data['transaction_count']); ?></div>
                    </div>
                </div>
                
                <div class="gatespark-stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-content">
                        <div class="stat-label"><?php _e('Success Rate', 'gatespark-revolut'); ?></div>
                        <div class="stat-value"><?php echo esc_html($report_data['success_rate']); ?>%</div>
                    </div>
                </div>
                
                <div class="gatespark-stat-card">
                    <div class="stat-icon">📈</div>
                    <div class="stat-content">
                        <div class="stat-label"><?php _e('Avg Order Value', 'gatespark-revolut'); ?></div>
                        <div class="stat-value"><?php echo wc_price($report_data['avg_order']); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Revenue Chart -->
            <div class="gatespark-chart-container">
                <h2><?php _e('Revenue Over Time', 'gatespark-revolut'); ?></h2>
                <canvas id="gatespark-revenue-chart"></canvas>
            </div>
            
            <!-- Recent Transactions -->
            <div class="gatespark-transactions-container">
                <div class="transactions-header">
                    <h2><?php _e('Recent Transactions', 'gatespark-revolut'); ?></h2>
                    <button class="button button-primary" id="gatespark-export-csv">
                        <span class="dashicons dashicons-download"></span>
                        <?php _e('Export CSV', 'gatespark-revolut'); ?>
                    </button>
                </div>
                
                <div class="table-responsive">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Date', 'gatespark-revolut'); ?></th>
                                <th><?php _e('Order', 'gatespark-revolut'); ?></th>
                                <th><?php _e('Customer', 'gatespark-revolut'); ?></th>
                                <th><?php _e('Amount', 'gatespark-revolut'); ?></th>
                                <th><?php _e('Status', 'gatespark-revolut'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($report_data['transactions'])): ?>
                                <?php foreach ($report_data['transactions'] as $transaction): ?>
                                    <tr>
                                        <td><?php echo esc_html($transaction['date']); ?></td>
                                        <td>
                                            <a href="<?php echo esc_url($transaction['order_url']); ?>" class="order-link">
                                                #<?php echo esc_html($transaction['order_number']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo esc_html($transaction['customer']); ?></td>
                                        <td><?php echo wc_price($transaction['amount']); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo esc_attr($transaction['status']); ?>">
                                                <?php echo esc_html($transaction['status_label']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5">
                                        <div class="gatespark-empty-state">
                                            <div class="empty-icon">📭</div>
                                            <h3><?php _e('No transactions yet', 'gatespark-revolut'); ?></h3>
                                            <p><?php _e('Transactions will appear here once customers start making payments.', 'gatespark-revolut'); ?></p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Upgrade to PRO notice -->
            <div class="gatespark-upgrade-notice">
                <div class="upgrade-content">
                    <div class="upgrade-icon">⚡</div>
                    <h3><?php _e('Want More Insights?', 'gatespark-revolut'); ?></h3>
                    <p><?php _e('Upgrade to GateSpark PRO for advanced features:', 'gatespark-revolut'); ?></p>
                    <div class="features-grid">
                        <div class="feature">💳 <?php _e('Apple Pay & Google Pay', 'gatespark-revolut'); ?></div>
                        <div class="feature">📅 <?php _e('Custom date ranges', 'gatespark-revolut'); ?></div>
                        <div class="feature">🎯 <?php _e('Payment method breakdown', 'gatespark-revolut'); ?></div>
                        <div class="feature">👥 <?php _e('Customer insights', 'gatespark-revolut'); ?></div>
                        <div class="feature">🌍 <?php _e('Geographic analytics', 'gatespark-revolut'); ?></div>
                        <div class="feature">📧 <?php _e('Scheduled reports', 'gatespark-revolut'); ?></div>
                    </div>
                    <a href="https://gatespark.eu/pro" class="button button-hero" target="_blank">
                        <?php _e('Upgrade to PRO - €99/year →', 'gatespark-revolut'); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get report data
     */
    private function get_report_data($period) {
        // Calculate date range
        $days = $period === '7days' ? 7 : 30;
        $start_date = date('Y-m-d', strtotime("-{$days} days"));
        $end_date = date('Y-m-d');
        
        // Get stats from Stats class
        $totals = GateSpark_Stats::get_totals($start_date, $end_date);
        $stats = GateSpark_Stats::get_range($start_date, $end_date);
        
        // Prepare chart data
        $chart_labels = array();
        $chart_values = array();
        
        foreach ($stats as $stat) {
            $chart_labels[] = date_i18n('M j', strtotime($stat->stat_date));
            $chart_values[] = floatval($stat->total_revenue);
        }
        
        // Get recent transactions
        $orders = wc_get_orders(array(
            'payment_method' => 'gatespark_revolut',
            'date_created' => '>=' . strtotime($start_date),
            'limit' => 50,
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        $transactions = array();
        foreach ($orders as $order) {
            $transactions[] = array(
                'date' => $order->get_date_created()->date_i18n('Y-m-d H:i'),
                'order_number' => $order->get_order_number(),
                'order_url' => $order->get_edit_order_url(),
                'customer' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                'amount' => $order->get_total(),
                'status' => $order->get_status(),
                'status_label' => wc_get_order_status_name($order->get_status())
            );
        }
        
        return array(
            'total_revenue' => $totals['total_revenue'],
            'transaction_count' => $totals['transaction_count'],
            'success_rate' => $totals['success_rate'],
            'avg_order' => $totals['avg_order'],
            'transactions' => $transactions,
            'chart_data' => array(
                'labels' => $chart_labels,
                'values' => $chart_values
            )
        );
    }
    
    /**
     * Update daily stats (cron job)
     */
    public function update_daily_stats() {
        GateSpark_Stats::update_yesterday();
    }
    
    /**
     * Export CSV
     */
    public function export_csv() {
        check_ajax_referer('gatespark_reports', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Permission denied', 'gatespark-revolut'));
        }
        
        $period = isset($_GET['period']) ? sanitize_text_field($_GET['period']) : '30days';
        $days = $period === '7days' ? 7 : 30;
        $start_date = date('Y-m-d', strtotime("-{$days} days"));
        
        $orders = wc_get_orders(array(
            'payment_method' => 'gatespark_revolut',
            'date_created' => '>=' . strtotime($start_date),
            'limit' => -1,
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=gatespark-transactions-' . date('Y-m-d') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        
        // Add BOM for Excel UTF-8 support
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // CSV headers
        fputcsv($output, array('Date', 'Order Number', 'Customer', 'Email', 'Amount', 'Currency', 'Status'));
        
        // CSV data
        foreach ($orders as $order) {
            fputcsv($output, array(
                $order->get_date_created()->date('Y-m-d H:i:s'),
                $order->get_order_number(),
                $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                $order->get_billing_email(),
                $order->get_total(),
                $order->get_currency(),
                $order->get_status()
            ));
        }
        
        fclose($output);
        exit;
    }
}

<?php
/**
 * GateSpark Stats Helper Class
 * Centralized statistics management
 *
 * @package GateSpark
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

final class GateSpark_Stats {
    
    private static $table_name = 'gatespark_daily_stats';
    
    public static function create_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::$table_name;
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            stat_date date NOT NULL,
            total_revenue decimal(10,2) DEFAULT 0,
            transaction_count int DEFAULT 0,
            successful_count int DEFAULT 0,
            failed_count int DEFAULT 0,
            refunded_count int DEFAULT 0,
            refunded_amount decimal(10,2) DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY stat_date (stat_date),
            KEY stat_date_index (stat_date)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        update_option('gatespark_db_version', '1.0.0');
    }
    
    public static function log_transaction($order, $status, $amount = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::$table_name;
        $stat_date = current_time('Y-m-d');
        $transaction_amount = $amount !== null ? floatval($amount) : floatval($order->get_total());
        
        self::ensure_date_exists($stat_date);
        
        switch ($status) {
            case 'completed':
            case 'processing':
                $wpdb->query($wpdb->prepare(
                    "UPDATE {$table_name} 
                    SET total_revenue = total_revenue + %f,
                        transaction_count = transaction_count + 1,
                        successful_count = successful_count + 1
                    WHERE stat_date = %s",
                    $transaction_amount,
                    $stat_date
                ));
                break;
                
            case 'failed':
                $wpdb->query($wpdb->prepare(
                    "UPDATE {$table_name} 
                    SET transaction_count = transaction_count + 1,
                        failed_count = failed_count + 1
                    WHERE stat_date = %s",
                    $stat_date
                ));
                break;
                
            case 'refunded':
                $wpdb->query($wpdb->prepare(
                    "UPDATE {$table_name} 
                    SET refunded_count = refunded_count + 1,
                        refunded_amount = refunded_amount + %f
                    WHERE stat_date = %s",
                    $transaction_amount,
                    $stat_date
                ));
                break;
        }
        
        return true;
    }
    
    private static function ensure_date_exists($date) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::$table_name;
        
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE stat_date = %s",
            $date
        ));
        
        if (!$exists) {
            $wpdb->insert(
                $table_name,
                array('stat_date' => $date),
                array('%s')
            );
        }
    }
    
    public static function get_range($start_date, $end_date) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::$table_name;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} 
            WHERE stat_date >= %s AND stat_date <= %s 
            ORDER BY stat_date ASC",
            $start_date,
            $end_date
        ));
    }
    
    public static function get_date($date) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::$table_name;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE stat_date = %s",
            $date
        ));
    }
    
    public static function get_today() {
        return self::get_date(current_time('Y-m-d'));
    }
    
    public static function get_totals($start_date, $end_date) {
        $stats = self::get_range($start_date, $end_date);
        
        $totals = array(
            'total_revenue' => 0,
            'transaction_count' => 0,
            'successful_count' => 0,
            'failed_count' => 0,
            'refunded_count' => 0,
            'refunded_amount' => 0,
            'success_rate' => 0,
            'avg_order' => 0
        );
        
        foreach ($stats as $stat) {
            $totals['total_revenue'] += floatval($stat->total_revenue);
            $totals['transaction_count'] += intval($stat->transaction_count);
            $totals['successful_count'] += intval($stat->successful_count);
            $totals['failed_count'] += intval($stat->failed_count);
            $totals['refunded_count'] += intval($stat->refunded_count);
            $totals['refunded_amount'] += floatval($stat->refunded_amount);
        }
        
        if ($totals['transaction_count'] > 0) {
            $totals['success_rate'] = round(($totals['successful_count'] / $totals['transaction_count']) * 100, 1);
        }
        
        if ($totals['successful_count'] > 0) {
            $totals['avg_order'] = $totals['total_revenue'] / $totals['successful_count'];
        }
        
        return $totals;
    }
    
    public static function update_yesterday() {
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        
        $orders = wc_get_orders(array(
            'payment_method' => 'gatespark_revolut',
            'date_created' => '>=' . strtotime($yesterday . ' 00:00:00'),
            'date_created' => '<=' . strtotime($yesterday . ' 23:59:59'),
            'limit' => -1
        ));
        
        $total_revenue = 0;
        $transaction_count = count($orders);
        $successful_count = 0;
        $failed_count = 0;
        $refunded_count = 0;
        $refunded_amount = 0;
        
        foreach ($orders as $order) {
            $status = $order->get_status();
            
            if (in_array($status, array('processing', 'completed'))) {
                $total_revenue += $order->get_total();
                $successful_count++;
            } elseif ($status === 'failed') {
                $failed_count++;
            } elseif ($status === 'refunded') {
                $refunded_count++;
                $refunded_amount += $order->get_total();
            }
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . self::$table_name;
        
        $wpdb->replace(
            $table_name,
            array(
                'stat_date' => $yesterday,
                'total_revenue' => $total_revenue,
                'transaction_count' => $transaction_count,
                'successful_count' => $successful_count,
                'failed_count' => $failed_count,
                'refunded_count' => $refunded_count,
                'refunded_amount' => $refunded_amount
            ),
            array('%s', '%f', '%d', '%d', '%d', '%d', '%f')
        );
    }
}

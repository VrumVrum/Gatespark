# GateSpark - Smart Revolut Gateway for WooCommerce

🚀 **The modern Revolut payment gateway with built-in analytics**

[![WordPress Plugin Version](https://img.shields.io/badge/WordPress-6.4+-blue.svg)](https://wordpress.org)
[![WooCommerce Version](https://img.shields.io/badge/WooCommerce-6.0+-purple.svg)](https://woocommerce.com)
[![PHP Version](https://img.shields.io/badge/PHP-7.4+-777BB4.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-GPLv2-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

## Why GateSpark?

The official Revolut plugin is **basic**. It processes payments but gives you **zero insights**. GateSpark changes that.

### What Makes GateSpark Different?

| Feature | Official Plugin | GateSpark FREE |
|---------|----------------|----------------|
| Card Payments | ✅ | ✅ |
| Dashboard Widget | ❌ | ✅ |
| Reports & Charts | ❌ | ✅ |
| Transaction History | ⚠️ WC Only | ✅ Filtered |
| CSV Exports | ❌ | ✅ |
| Test Connection | ❌ | ✅ |
| Modern UI | ❌ | ✅ |
| Success Rate Tracking | ❌ | ✅ |

**Result:** A FREE plugin that's objectively better than the official one.

## Features

### 🆓 FREE Version

- **Card Payments** - 22+ currencies supported
- **Dashboard Widget** - See today's revenue, transactions, success rate
- **Reports Page** - 7-day and 30-day analytics
- **Revenue Charts** - Beautiful Chart.js visualizations
- **Transaction History** - Last 50 transactions with filtering
- **CSV Exports** - Download 30 days of transaction data
- **Test Connection** - One-click API credential verification
- **Modern UI** - Clean, organized, 2025 design
- **Sandbox Mode** - Safe testing environment
- **Refunds** - Process refunds directly
- **Webhooks** - Real-time payment notifications
- **HPOS Compatible** - Works with High-Performance Order Storage

### 💎 PRO Version ($99/year)

- Everything in FREE, plus:
- **Revolut Pay** - One-click payments for Revolut users
- **Apple Pay** - Seamless Apple Pay integration
- **Google Pay** - Fast Google Pay checkout
- **Custom Date Ranges** - Analyze any time period
- **Payment Method Breakdown** - See which methods perform best
- **Customer Insights** - Lifetime value, repeat rate, top customers
- **Geographic Analytics** - Revenue by country, currency analysis
- **Scheduled Reports** - Daily/weekly emails, Slack integration
- **Priority Support** - <24hr response time

## Installation

### From WordPress.org

1. Go to **Plugins → Add New**
2. Search for "GateSpark"
3. Click **Install Now** → **Activate**
4. Go to **WooCommerce → Settings → Payments**
5. Click **GateSpark - Revolut** → **Manage**

### Manual Installation

1. Download the plugin ZIP file
2. Go to **Plugins → Add New → Upload Plugin**
3. Choose the ZIP file and click **Install Now**
4. Click **Activate Plugin**

### From Source

```bash
cd wp-content/plugins/
git clone https://github.com/gatespark/gatespark-revolut.git
```

## Setup

### 1. Get Your Revolut API Credentials

1. Log in to [Revolut Business](https://business.revolut.com)
2. Go to **Settings → Developer API**
3. Create a new API key (Production or Sandbox)
4. Copy the API key

### 2. Configure GateSpark

1. Go to **WooCommerce → Settings → Payments → GateSpark - Revolut**
2. Paste your API key (Sandbox or Live)
3. Click **Test Connection** ✅
4. Enable the payment method
5. Click **Save changes**

### 3. Set Up Webhooks (Optional but Recommended)

1. Copy the webhook URL from GateSpark settings
2. Go to your Revolut Business dashboard
3. Navigate to **Settings → Webhooks**
4. Add the webhook URL
5. Select events: `ORDER_COMPLETED`, `ORDER_AUTHORISED`, `ORDER_PAYMENT_FAILED`

### 4. Check Your Analytics

Go to **WooCommerce → GateSpark Reports** to see your payment analytics!

## Supported Currencies

AED, AUD, BGN, CAD, CHF, CZK, DKK, EUR, GBP, HKD, HRK, HUF, ISK, JPY, NOK, NZD, PLN, RON, SAR, SEK, SGD, THB, TRY, USD, ZAR

## Requirements

- **WordPress:** 5.8 or higher
- **WooCommerce:** 6.0 or higher
- **PHP:** 7.4 or higher
- **Revolut Business account**
- **SSL certificate** (HTTPS required)

## Development

### Project Structure

```
gatespark-revolut/
├── gatespark-revolut.php       # Main plugin file
├── includes/
│   ├── class-gatespark-gateway.php    # WooCommerce gateway
│   ├── class-gatespark-api.php        # Revolut API client
│   ├── class-gatespark-admin.php      # Admin UI
│   ├── class-gatespark-dashboard.php  # Dashboard widget
│   ├── class-gatespark-reports.php    # Reports & analytics
│   └── class-gatespark-webhooks.php   # Webhook handler
├── assets/
│   ├── css/
│   │   ├── admin.css           # Admin styles
│   │   └── reports.css         # Reports page styles
│   └── js/
│       ├── admin.js            # Admin JavaScript
│       └── reports.js          # Charts & reports JS
└── templates/
    └── (future template files)
```

### Database Schema

#### `wp_gatespark_daily_stats`

Stores aggregated daily statistics for fast reporting.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint(20) | Primary key |
| `stat_date` | date | Date of stats (unique) |
| `total_revenue` | decimal(10,2) | Total revenue |
| `transaction_count` | int | Number of transactions |
| `successful_count` | int | Successful transactions |
| `failed_count` | int | Failed transactions |
| `refunded_count` | int | Refunded transactions |
| `refunded_amount` | decimal(10,2) | Total refunded amount |

### Hooks & Filters

#### Actions

```php
// After daily stats update
do_action('gatespark_after_stats_update', $date, $stats);

// Before order creation
do_action('gatespark_before_create_order', $order_data, $wc_order);

// After successful payment
do_action('gatespark_payment_complete', $wc_order, $revolut_order);
```

#### Filters

```php
// Modify API request data
apply_filters('gatespark_api_request_data', $data, $endpoint);

// Modify report data
apply_filters('gatespark_report_data', $report_data, $period);

// Modify chart colors
apply_filters('gatespark_chart_colors', $colors);
```

## Testing

### Unit Tests (Coming Soon)

```bash
composer install
./vendor/bin/phpunit
```

### Manual Testing Checklist

- [ ] Install plugin successfully
- [ ] Configure API credentials
- [ ] Test connection works
- [ ] Process test payment (sandbox)
- [ ] Check dashboard widget appears
- [ ] Verify reports page loads
- [ ] Check chart renders correctly
- [ ] Export CSV works
- [ ] Process refund
- [ ] Test webhook handling

## FAQ

**Q: Can I use this alongside the official Revolut plugin?**  
A: No, you should disable the official plugin to avoid conflicts.

**Q: Will my existing Revolut payments data transfer?**  
A: Your existing orders will remain in WooCommerce, but GateSpark starts tracking new analytics from activation date.

**Q: Does this work with WordPress Multisite?**  
A: Not yet, but it's on our roadmap.

**Q: Can I customize the reports?**  
A: Yes! Use the provided hooks and filters to extend functionality.

## Roadmap

### Version 1.1 (Coming Soon)
- [ ] Advanced filtering on reports page
- [ ] Real-time payment notifications
- [ ] Better error messages
- [ ] Order status customization

### Version 1.2
- [ ] Multi-currency support
- [ ] Subscription payments
- [ ] More chart types
- [ ] Email reports

### PRO Version Features
- [ ] Revolut Pay integration
- [ ] Apple Pay & Google Pay
- [ ] Payment method analytics
- [ ] Customer insights
- [ ] Geographic reports
- [ ] Scheduled reports

## Support

- **Documentation:** https://docs.gatespark.eu
- **Support Email:** support@gatespark.eu
- **Issues:** https://github.com/gatespark/gatespark-revolut/issues
- **Community:** https://community.gatespark.eu

## Contributing

We welcome contributions! Please:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This plugin is licensed under the GPL v2 or later.

```
Copyright (C) 2025 GateSpark

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.
```

## Credits

- Built with ❤️ by the GateSpark team
- Charts powered by [Chart.js](https://www.chartjs.org/)
- Icons from [Lucide](https://lucide.dev/)

---

**Made with 💪 to be better than the official Revolut plugin**

⭐ If you find GateSpark useful, please star this repo!

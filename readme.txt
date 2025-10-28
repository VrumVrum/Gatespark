=== GateSpark - Smart Revolut Gateway ===
Contributors: gatespark
Tags: revolut, payment gateway, woocommerce, payments, analytics
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Modern Revolut payment gateway with built-in analytics. Better than the official plugin with dashboard widgets, reports, and a clean 2025 UI.

== Description ==

**GateSpark is the smart alternative to the official Revolut plugin.**

Unlike the official Revolut plugin, GateSpark gives you:

* 📊 **Dashboard widget** showing today's revenue, transactions, and success rate
* 📈 **Reports page** with revenue charts and analytics
* 💳 **Transaction history** with filtering and search
* 📥 **CSV exports** for easy data analysis
* ✅ **Test connection buttons** to verify your API credentials
* 🎨 **Modern 2025 UI** - clean, organized, professional
* ⚡ **Better performance** - optimized code, no bloat

### Why Switch from the Official Plugin?

The official Revolut plugin is basic. It processes payments but gives you zero insights into your performance. With GateSpark, you can:

* See how much you made today at a glance
* Track your success rate and spot issues early
* Analyze revenue trends with beautiful charts
* Export transaction data for accounting

**All for FREE** ✨

### Free Version Features

* ✅ Card payments (22+ currencies supported)
* ✅ Dashboard widget with daily stats
* ✅ Reports page with 7-day and 30-day views
* ✅ Revenue line charts
* ✅ Transaction history (last 50)
* ✅ CSV exports (30-day data)
* ✅ Test connection buttons
* ✅ Sandbox and live modes
* ✅ Refunds support
* ✅ Webhooks integration
* ✅ HPOS compatible
* ✅ Debug logging
* ✅ Easy onboarding wizard

### PRO Version (€99/year)

Want even more? Upgrade to PRO for:

* 💎 Revolut Pay, Apple Pay, Google Pay
* 💎 Custom date ranges & advanced filtering
* 💎 Payment method breakdown
* 💎 Customer insights & lifetime value
* 💎 Geographic analytics
* 💎 Scheduled reports (email, Slack)
* 💎 Priority support

[Learn more about PRO →](https://gatespark.eu/pro)

### Supported Currencies

AED, AUD, BGN, CAD, CHF, CZK, DKK, EUR, GBP, HKD, HRK, HUF, ISK, JPY, NOK, NZD, PLN, RON, SAR, SEK, SGD, THB, TRY, USD, ZAR

### Requirements

* WordPress 5.8 or higher
* WooCommerce 6.0 or higher
* PHP 7.4 or higher
* Revolut Business account
* SSL certificate (HTTPS required)

### Links

* [Website](https://gatespark.eu)
* [Documentation](https://gatespark.eu/docs)
* [Support](https://gatespark.eu/support)
* [GitHub](https://github.com/gatespark/gatespark-revolut)

== Installation ==

### Automatic Installation (Recommended)

1. Go to **Plugins → Add New**
2. Search for "GateSpark"
3. Click **Install Now** → **Activate**
4. Complete the onboarding wizard

### Manual Installation

1. Download the plugin ZIP file
2. Go to **Plugins → Add New → Upload Plugin**
3. Choose the ZIP file and click **Install Now**
4. Click **Activate Plugin**
5. Complete the onboarding wizard

### Setup

1. **Get Your API Key**
   - Log in to [Revolut Business](https://business.revolut.com)
   - Go to Settings → Developer API
   - Create a new API key (Production or Sandbox)
   - Copy the API key

2. **Configure GateSpark**
   - Go to WooCommerce → Settings → Payments → GateSpark - Revolut
   - Paste your API key (Sandbox or Live)
   - Click "Test Connection" ✅
   - Enable the payment method
   - Click "Save changes"

3. **Set Up Webhooks (Recommended)**
   - Copy the webhook URL from GateSpark settings
   - Go to your Revolut Business dashboard
   - Navigate to Settings → Webhooks
   - Add the webhook URL
   - Select events: `ORDER_COMPLETED`, `ORDER_AUTHORISED`, `ORDER_PAYMENT_FAILED`

4. **Check Your Analytics**
   - Go to WooCommerce → GateSpark Reports to see your payment analytics!

== Frequently Asked Questions ==

= Is this plugin free? =

Yes! The free version includes card payments, dashboard widgets, reports, charts, and CSV exports. Everything most merchants need.

= What's the difference between FREE and PRO? =

FREE gives you card payments and basic analytics. PRO adds alternative payment methods (Revolut Pay, Apple Pay, Google Pay) and advanced analytics like payment method breakdown, customer insights, geographic reports, and scheduled reports.

= Do I need a Revolut Business account? =

Yes, you need a Revolut Business account to use this plugin. Sign up at https://business.revolut.com

= Is this plugin compatible with HPOS? =

Yes! GateSpark is fully compatible with WooCommerce High-Performance Order Storage (HPOS).

= How do I get my API credentials? =

1. Log in to your Revolut Business dashboard
2. Go to Settings → Developer API
3. Create a new API key for Production or Sandbox
4. Copy the API key and paste it into GateSpark settings

= Does this work with the official Revolut plugin? =

No, you should disable the official Revolut plugin before activating GateSpark to avoid conflicts.

= Where can I see my payment analytics? =

Go to WooCommerce → GateSpark Reports to see your full analytics dashboard. You'll also see a widget on your WordPress dashboard showing today's stats.

= Can I export my transaction data? =

Yes! On the GateSpark Reports page, click "Export CSV" to download your transaction history in CSV format.

= What if I need help? =

Check our documentation at https://gatespark.eu/docs or contact support at support@gatespark.eu

= Is webhook signature verification supported? =

Yes! GateSpark uses HMAC-based webhook signature verification for enhanced security.

= Can I test before going live? =

Absolutely! Use sandbox mode to test payments without processing real transactions.

== Screenshots ==

1. Dashboard widget showing today's payment stats
2. Reports page with revenue charts and analytics
3. Modern settings page with test connection buttons
4. Transaction history with filtering
5. CSV export functionality
6. Easy onboarding wizard

== Changelog ==

= 1.0.0 - 2025-01-XX =
* Initial release
* Card payments support (22+ currencies)
* Dashboard widget with daily stats
* Reports page with 7-day and 30-day views
* Revenue charts using Chart.js
* Transaction history
* CSV exports with nonce verification
* Test connection buttons
* Modern UI design
* Sandbox and live modes
* Refunds support
* Webhooks integration with HMAC verification
* REST API webhook endpoint
* HPOS compatibility
* Debug logging via WC_Logger
* Easy onboarding wizard
* Proper security: nonce checks, sanitization, escaping

== Upgrade Notice ==

= 1.0.0 =
Initial release of GateSpark - the smart Revolut gateway with built-in analytics.

== Privacy Policy ==

GateSpark does not collect or store any personal data. All transaction data is stored in your WordPress database. Payment processing is handled securely by Revolut.

== Support ==

For support, documentation, and bug reports:
* Email: support@gatespark.eu
* Documentation: https://gatespark.eu/docs
* GitHub: https://github.com/gatespark/gatespark-revolut/issues

== Credits ==

* Built with ❤️ by the GateSpark team
* Charts powered by [Chart.js](https://www.chartjs.org/)
* Payment processing by [Revolut](https://www.revolut.com/)

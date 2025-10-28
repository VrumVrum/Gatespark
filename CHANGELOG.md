# Changelog

All notable changes to GateSpark will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Planned Features
- Advanced filtering on reports page
- Real-time payment notifications
- Multi-currency analytics
- Subscription payments support
- More chart types (pie, bar, area)

## [1.0.0] - 2025-01-XX

### Added - Initial Release

#### Core Features
- Card payment processing via Revolut API
- Support for 22+ currencies (AED, AUD, BGN, CAD, CHF, CZK, DKK, EUR, GBP, HKD, HRK, HUF, ISK, JPY, NOK, NZD, PLN, RON, SAR, SEK, SGD, THB, TRY, USD, ZAR)
- Sandbox and Live mode support
- Refund processing
- Webhook integration for payment status updates

#### Analytics & Reporting
- Dashboard widget showing today's payment statistics
- Reports page with 7-day and 30-day views
- Revenue line charts using Chart.js
- Transaction history table (last 50 transactions)
- Success rate tracking
- CSV export functionality (30-day data)

#### User Interface
- Modern 2025 UI design
- Clean, organized settings page
- Test connection buttons for API verification
- Visual feedback for actions
- Contextual help tooltips
- Responsive design for mobile devices

#### Developer Features
- HPOS (High-Performance Order Storage) compatibility
- Enhanced debug logging
- WordPress hooks and filters for customization
- Detailed error messages
- Daily stats cron job for performance
- Database table for cached statistics

#### Documentation
- Comprehensive README.md
- WordPress.org README.txt
- Installation guide (INSTALL.md)
- Feature comparison document (COMPARISON.md)
- Inline code documentation

### Technical Details
- **PHP Version:** 7.4 minimum
- **WordPress Version:** 5.8 minimum
- **WooCommerce Version:** 6.0 minimum
- **Database:** Custom table for daily stats caching
- **API Integration:** Revolut Merchant API v1.0

### Security
- API keys stored securely
- Input sanitization and validation
- Nonce verification for AJAX requests
- Proper escaping of output
- SQL injection prevention with prepared statements

### Performance
- Cached daily statistics for fast reporting
- Minimal database queries
- Optimized asset loading
- CDN for Chart.js library

---

## Version History Summary

| Version | Date | Major Changes |
|---------|------|---------------|
| 1.0.0 | 2025-01-XX | Initial release with full analytics |

---

## Upgrade Notes

### From Official Revolut Plugin to GateSpark 1.0.0

No data migration needed. Simply:
1. Deactivate official Revolut plugin
2. Install and activate GateSpark
3. Enter your existing API credentials
4. Test connection and you're done!

Your existing WooCommerce orders remain unchanged.

---

## Future Roadmap

### Version 1.1 (Q1 2025)
- [ ] Advanced filtering options
- [ ] Real-time notifications
- [ ] Improved error handling
- [ ] Order status customization

### Version 1.2 (Q2 2025)
- [ ] Multi-currency support
- [ ] Subscription payments
- [ ] Additional chart types
- [ ] Email reports

### PRO Version (Q1 2025)
- [ ] Revolut Pay integration
- [ ] Apple Pay support
- [ ] Google Pay support
- [ ] Payment method breakdown
- [ ] Customer insights
- [ ] Geographic analytics
- [ ] Custom date ranges
- [ ] Scheduled reports
- [ ] Priority support

---

## Contributors

- GateSpark Team - Initial work and ongoing development

## Support

For support and bug reports:
- Email: support@gatespark.eu
- Documentation: https://gatespark.eu/docs
- GitHub Issues: https://github.com/gatespark/gatespark-revolut/issues

---

## License

This project is licensed under the GPL v2 or later - see the [LICENSE](LICENSE) file for details.

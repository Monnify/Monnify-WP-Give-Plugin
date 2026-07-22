=== Give - Monnify Gateway ===
Contributors: monnify
Tags: give, givewp, donations, monnify, payments
Requires at least: 6.6
Tested up to: 6.8
Stable tag: 1.0.0
Requires PHP: 7.4
Requires Give: 4.10.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Accept donations via Monnify (card, bank transfer, and USSD) on your GiveWP donation forms.

== Description ==

Give - Monnify Gateway adds Monnify as a payment gateway option for GiveWP donation forms. Donors are redirected to Monnify's hosted checkout to complete their card, bank transfer, or USSD payment, then returned to your site once payment is verified.

= Plugin Features =

* Hosted-checkout (redirect) payment flow - no card data ever touches your server
* Supports both GiveWP's classic (option-based) and visual form builder donation forms
* Test/Live mode with separate API credentials for each
* Server-side transaction verification on return - donation status is never trusted from the redirect alone
* Webhook listener for asynchronous bank-transfer/USSD payment confirmation
* Refund support from the donation details screen

== Installation ==

1. Make sure GiveWP is installed and activated.
2. Upload the plugin files to the `/wp-content/plugins/give-monnify` directory, or install the plugin through the WordPress plugins screen directly.
3. Activate the plugin through the 'Plugins' screen in WordPress.
4. Go to Give > Settings > Payment Gateways > Monnify and enter your Monnify API Key, Secret Key, and Contract Code (for Test and/or Live mode).
5. Copy the generated Webhook URL into your Monnify dashboard's webhook settings.

== Changelog ==

= 1.0.0 =
* Initial release.

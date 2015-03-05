=== White Payments ===
Contributors: jamesmay
Donate link: https://whitepayments.com/
Tags: white, payments, whitepayments, middle east payments, credit card, middle east payment gateway, payment gateway, whitepayments.com
Requires at least: 3.0.1
Tested up to: 4.1.1
Stable tag: 2.0.12
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

White makes it easy to accept online payments (credit and debit cards) in the Middle East. No setup fees, no monthly fees. Start in 5 minutes!

== Description ==

White is the only payment solution you need to start accepting online payments. That's right, no merchant accounts, no payment gateways and no visits to the bank. Get started online, and in under 5 minutes. (*Currently only available to companies in the Middle East*)

= Using Wordpress + WooCommerce? =

Then you're going to love this!

This WooCommerce plugin makes integrating your store with White a walk in the park! In the same time it would take you to make a cup of some great turkish coffee, you could be up and running with online payments!

Doesn't matter if you're in Bahrain or the UAE, it'll work fine either way.

== Requirements ==

In order to start using White, please head over to [http://whitepayments.com/](our website) and register for an online account (it'll only take a few minutes; we'll wait..)

Got the account? Great .. now make sure to get the following details as well: `Live secret key`, `Test secret key` and the `Public Encryption key`. This will be needed when setting up the WooCommerce payment plugin.

== Installation ==

1. Extract the `white-woo.zip` file to your `wp-content/plugins/` folder.
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure the options in the WooCommerce --> Settings --> Checkout screen.

== Frequently Asked Questions ==

= What version of PHP is required for this plugin to work? =

Use PHP v5.4 or later.

== Changelog ==

= 2.0.12 =
- Resolve error formatting backwards-incompatible change by WooCommerce

= 2.0.10 =
- Updated White to refer to vendor folder, and not composer version

= 2.0.8 =
- Added notice for users in test mode

= 2.0.6 =
- Send additional order information to White

= 2.0.5 =
- Enable automatic plugin updates

= 2.0.4 =
- Fixed an issue with the White Payments plugin not working when a 2Checkout plugin is installed.

= 2.0.3 =
- Removed `form-row-first` from checkout form

= 2.0.0 =
- Updated to use the new version of the API

= 1.1.5 =
- Switched to the AWS hosted white.js (from the outdated Rackspace-hosted version)

= 1.0 =
- First commit

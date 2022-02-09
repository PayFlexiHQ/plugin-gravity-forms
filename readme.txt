=== PayFlexi Installment Payment Plans for Gravity Forms ===
Contributors: stanwarri
Plugin URI: https://payflexi.co
Tags: payments, flexible payment, installment payment, gravityforms, payment plans
Requires at least: 5.1
Tested up to: 5.9
Requires PHP: 7.2 and higher
Stable tag: 1.3.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

PayFlexi payment plans plugin for Gravity Forms Addon is a payment option that lets your customers to spread the amount of payment into several installments.

= Features =

* Accept one-time payment or installment payment from your customers.
* Let customers customize plans within the limits that you set.
* Set the minimum amount to enable for installment payment.
* Set the minimum amount to enable for weekly or monthly installment payment.
* Accept payments via your existing payment processor and get paid instantly.
* Manage and view all payment schedules from your dedicated merchant dashboard.
* Customers have access to dedicated dashboard for managing their payment schedules.

With PayFlexi, you can bring your existing payment processor to accept payment. We currently support;

* __Stripe__
* __PayStack__
* __Flutterwave__

New Payment Gateways will be added regularly. If there is a Payment Gateway that you need urgently or a feature missing that you think we must add, [get in touch with us](https://payflexi.co/contact/) and we will consider it.


== Installation ==
 
= Minimum Requirements =
* Gravity Forms v2.4+
* WordPress v5.1+
* SSL Certificate Installed and - Configured
* Download and install the add-on
* A PayFlexi Merchant account

= Automatic Installation =
* 	Login to your WordPress Admin area
* 	Go to "Plugins > Add New" from the left hand menu
* 	In the search box type __PayFlexi Instalment Payment Gateway for Gravity Forms__
*	From the search result you will see __PayFlexi Instalment Payment Gateway for Gravity Forms__ click on __Install Now__ to install the plugin
*	A popup window will ask you to confirm your wish to install the Plugin.
*	After installation, activate the plugin.
* 	Open the settings page for Gravity Forms and click the "PayFlexi" tab.
*	Configure your __PayFlexi Payment Gateway__ settings. See below for details.

= Manual Installation =
* 	Download the plugin zip file
* 	Login to your WordPress Admin. Click on "Plugins > Add New" from the left hand menu.
*   Click on the "Upload" option, then click "Choose File" to select the zip file from your computer. Once selected, press "OK" and press the "Install Now" button.
*   Activate the plugin.
* 	Open the settings page for Gravity Forms and click the "PayFlexi" tab.
*	Configure your __PayFlexi Payment Gateway__ settings. See below for details.

= Configure the plugin =
* __Mode__ - Test mode enables you to test payments before going live. If you ready to start receving real payment on your site, kindly check Live.
* __Enabled Payment Gateway__ - Add the corresponding gateway you connected on PayFlexi. Enter "stripe" if you connected your Stripe account on PayFlexi.
* __Test Secret API Key__ - Enter your Test Secret Key here. Get your API keys from your PayFlexi Merchant Account under Developer > API
* __Test Public API Key__ - Enter your Test Public Key here. Get your API keys from your PayFlexi Merchant Account under Developer > API
* __Live Secret API Key__ - Enter your Live Secret Key here. Get your API keys from your PayFlexi Merchant Account under Developer > API
* __Live Public API Key__ - Enter your Live Public Key here. Get your API keys from your PayFlexi Merchant Account under Developer > API
* Click on __Save Settings__ for the changes you made to be effected.

<strong>You have to set the Webhook URL in the [API Keys & Webhooks](https://merchant.payflexi.co/developers?tab=api-keys-integrations) settings page in your PayFlexi Merchant Account</strong>. Go to the plugin settings page for more information.

= Updating =
 
Automatic updates should work like a charm; as always though, ensure you backup your site just in case.


== Frequently Asked Questions ==
 
= Where can I find help and documentation to understand PayFlexi? =
 
You can find help and information on PayFlexi on our [Help Desk](https://support.payflexi.co/)

== Changelog ==

= 2.0.0 - February 9, 2022 =
* Compatibility with WordPress v5.9 and PHP 8

= 1.2.1 - December 7, 2021 =
* Fixed payment status issue based on gateway response

= 1.2.0 - October 26, 2021 =
* Fixed redirect url after payment confirmation

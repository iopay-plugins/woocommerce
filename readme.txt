=== IOPAY for WooCommerce ===
Contributors: iopay, linknacional
Donate link: https://iopay.com.br/
Tags: woocommerce, iopay, payment, pix
Requires at least: 5.7
Tested up to: 6.3
Requires PHP: 7.0
Stable tag: 1.2.1
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Receive payments by credit card, pix and banking ticket

== Description ==

[IOPAY](https://iopay.com.br/) is the best way to receive online payments by credit card, pix and bank slip, allowing the customer to make the entire payment without leaving your WooCommerce store.

Learn more about how Iopay works in:

https://iopay.com.br/

**Dependencies**

* Payment gateway IOPAY for WooCommerce plugin is dependent on [WooCommerce](https://wordpress.org/plugins/woocommerce/) plugin, please make sure WooCommerce is installed and properly configured before plugin activation.

* Payment gateway IOPAY for WooCommerce plugin is dependent on [Brazilian Market on WooCommerce](https://br.wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/) plugin, please make sure Brazilian Market on WooCommerce is installed and properly configured before plugin activation.

**User instructions**

With the plugin installed, access the WordPress admin and enter "WooCommerce" > "Settings" > "Payment" and configure the options "IOPAY - Banking Ticket", "IOPAY - Credit Card" e "IOPAY - PIX".

Enable the option you want, fill in the Email, **Iopay API Key** and **Iopay Encryption Key** options that you can find inside your IOPAY account at **https://minhaconta.iopay.com.br/settings/online_payment**.

You will also need to use the plugin [WooCommerce Extra Checkout Fields for Brazil](http://wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/) to be able to send CPF and CNPJ fields.

That's it, your store can now receive payments through IOPAY.

More information about plugin settings at: [installation guide](https://docs.iopay.com.br/products/modulos-para-ecommerce/wordpress-woocommerce).

= Compatibility =

Compatible since WooCommerce version 7.4.x.

This plugin works integrated with [WooCommerce Extra Checkout Fields for Brazil](http://wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/), in this way, it is possible to send customer documents such as "CPF" or "CNPJ", in addition to the "number" and "district" fields of the address.

= Installation =

Check out our [installation guide](https://docs.iopay.com.br/products/modulos-para-ecommerce/wordpress-woocommerce).

== Frequently Asked Questions ==

= What is the plugin license? =

* This plugin is released under a GPL license.

= What is needed to use this plugin? =

* WooCommerce version 7.4.x or latter installed and active;
* Brazilian Market on WooCommerce version 3.4.x or latter installed and active;
* An active account at [IOPAY](https://iopay.com.br/);
* IOPAY [integration cretentials](https://docs.iopay.com.br/credenciais-de-acesso).

= How much does IOPAY cost? =

Check prices at "https://iopay.com.br/precos".

= The order was paid and the status was "processing" and not "completed", is this correct? =

Yes, that's right and it means the plugin is working as it should.

Every payment gateway in WooCommerce must change the order status to "processing" at the moment the payment is confirmed and it must never be changed alone to "completed", as the order should only go to the "completed" status after it has been delivered.

For downloadable products the default WooCommerce setting is to allow access only when the order has the status "completed", however in the WooCommerce settings on the *Products* tab it is possible to activate the option **"Grant access to download the product after payment "** and thus release the download when the order status is "processing".

= Is it mandatory to send all fields to process the payment? =

It is not mandatory if you do not use anti-fraud.

It is possible to remove the address, company and telephone fields, keeping only first name, last name and e-mail using the plugin [WooCommerce Digital Goods Checkout](https://wordpress.org/plugins/wc-digital-goods-checkout/), but remember these fields are mandatory when your plan has anti-fraud included.

== Screenshots ==

1. none

== Changelog ==

= v1.2.1 =
**09/08/2023**
* Fixed a error with customer not recognized for the transaction;
* Script optimizations;
* General code optimizations.

= v1.2.0 =
**28/06/2023**
* Subscription implementation for official WooCommerce plugin;
* Subscription implementation for free WPS WooCommerce plugin;
* Removal of user metadata when removing plugin.

= 1.1.5 =
**12/06/2023**
* Fixed CPF/CNPJ detection bug;
* Fixed unrecognized delivery fields bug;
* Added IoPay customer saving by WordPress registration;
* Added attribute escaping to templates;
* Improved clarity of plugin settings;
* Improved payment input validation.

= 1.1.4 =
**30/05/2023**
* Bug fix for transaction not performed for customer without registration.

= 1.1.3 = 25/05/2023
**25/05/2023**
* Fixed transaction description bug exceeding 60 character limit.

= 1.1.2 =
**18/05/2023**
* Contributors name updated;
* Remove URL from .po files.

= 1.1.1 =
* **09/05/2023**
* Addition of space in the title of installment interest settings;
* Added sanitization and handling of empty attributes;
* Standardization of script loading and styling according to WordPress regulations;
* Fixed translations, added missing translations and tweaked translation files.

= 1.1.0 =
* **05/05/2023**
* Credit card bug fix without installments configured;
* Removing deprecated PHP functions;
* Improved PHP error handling system;
* Corrected admin area notifications URL;
* Fixed bug of notifications route record function;
* Addition of barcode in billing with bank slip;
* Improved notification functions via webhook.

= 1.0.0 =
* **21/09/2022**
* Plugin launch.

== Upgrade Notice ==

= 1.0.0 =
* Plugin launch.
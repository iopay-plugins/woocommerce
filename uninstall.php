<?php

/*
 * Uninstall plugin
 *
 * @since 1.1.0
 */

// Exit if accessed directly.
if ( ! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

$optionCreditCardIopay = get_option('woocommerce_iopay-credit-card_settings');

// Clear all metadata
delete_metadata('user', 0, 'iopay_card_token_' . $optionCreditCardIopay['api_key'], '', true);
delete_metadata('user', 0, 'iopay_customer_' . $optionCreditCardIopay['api_key'], '', true);

// Clear WooCommerce Gateway options
delete_option('woocommerce_iopay-banking-ticket_settings');
delete_option('woocommerce_iopay-pix_settings');
delete_option('woocommerce_iopay-credit-card_settings');
delete_option('woocommerce_iopay_admin_notice_documentation_link');

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

// Clear WooCommerce Gateway options
delete_option('woocommerce_iopay-banking-ticket_settings');
delete_option('woocommerce_iopay-pix_settings');
delete_option('woocommerce_iopay-credit-card_settings');
delete_option('woocommerce_iopay_admin_notice_documentation_link');

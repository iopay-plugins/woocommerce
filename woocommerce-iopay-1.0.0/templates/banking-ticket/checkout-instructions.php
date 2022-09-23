<?php
/**
 * Bank Slip - Payment instructions.
 *
 * @author  Iopay
 * @package WooCommerce_Iopay/Templates
 * @version 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div id="iopay-bank-slip-instructions">
	<p><?php esc_html_e( 'After clicking "Place order" you will have access to banking banking ticket which you can print and pay in your internet banking or in a lottery retailer.', 'woocommerce-iopay' ); ?><br /><?php esc_html_e( 'Note: The order will be confirmed only after the payment approval.', 'woocommerce-iopay' ); ?></p>
</div>

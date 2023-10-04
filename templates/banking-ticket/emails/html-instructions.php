<?php
/**
 * Bank Slip - HTML email instructions.
 *
 * @author  Iopay
 *
 * @version 2.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<h2><?php esc_html_e( 'Payment', 'woocommerce-iopay' ); ?>
</h2>

<p class="order_details">
    <?php esc_html_e( 'Please use the link below to view your banking ticket, you can print and pay in your internet banking or in a lottery retailer:', 'woocommerce-iopay' ); ?><br /><a
        class="button" href="<?php echo esc_url( $url ); ?>"
        target="_blank"><?php esc_html_e( 'Pay the banking ticket', 'woocommerce-iopay' ); ?></a><br /><?php esc_html_e( 'After we receive the banking ticket payment confirmation, your order will be processed.', 'woocommerce-iopay' ); ?>
</p>
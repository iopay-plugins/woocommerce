<?php
/**
 * Bank Slip - Payment instructions.
 *
 * @author  Iopay
 *
 * @version 2.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div id="iopay-bank-slip-message" class="woocommerce-message">
    <div id="iopay-bank-slip-code">
        <?php esc_html_e($barcode); ?>
    </div>

    <div id="iopay-bank-slip-btn">
        <a id="iopay-redirection-link" class="button" href="<?php echo esc_url( $url ); ?>"
        target="_blank"><?php esc_html_e( 'Pay the banking ticket', 'woocommerce-iopay' ); ?></a>
    </div>

    <span id="iopay-user-instructions">
        <?php esc_html_e( 'Please click in the following button to view your banking ticket.', 'woocommerce-iopay' ); ?>
        <br />
        <?php esc_html_e( 'You can print and pay in your internet banking or in a lottery retailer.', 'woocommerce-iopay' ); ?>
        <br />
        <?php esc_html_e( 'After we receive the banking ticket payment confirmation, your order will be processed.', 'woocommerce-iopay' ); ?>
    </span>
</div>

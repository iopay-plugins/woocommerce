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

<style>
    #iopay-bank-slip-message{
        display: flex;
        flex-direction: column;
    }
    #iopay-bank-slip-btn {
        display: flex;
        justify-content: center;
        padding: 15px 0px 0px 0px;
    }
    #iopay-redirection-link {
        text-decoration: none;
        padding: 0.5rem;
        border: 1px solid #abb8c3;
        border-radius: 4px;
    }
    #iopay-bank-slip-code {
        display: flex;
        justify-content: center;
        padding: 8px 0px;
        border-top: solid 1px #abb8c3;
        border-bottom: solid 1px #abb8c3 ;
    }
    #iopay-user-instructions {
        padding: 15px 0px 0px 0px; 
    }
</style>
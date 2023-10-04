<?php
/**
 * Bank Slip - Payment instructions.
 *
 * @author  Iopay
 *
 * @version 2.0.11
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="woocommerce-message">
    <span><a class="button" href="<?php echo esc_url( $url ); ?>"
            target="_blank"><?php esc_html_e( 'View order', 'woocommerce-iopay' ); ?></a><?php esc_html_e( 'Your banking ticket is being generated, access your order to view it.', 'woocommerce-iopay' ); ?><br /></span>
</div>
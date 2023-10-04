<?php
/**
 * Credit Card - HTML email instructions.
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
    <?php printf( wp_kses( __( 'Payment successfully made using %1$s credit card in %2$s.', 'woocommerce-iopay' ), array('strong' => array()) ), '<strong>' . esc_html( $card_brand ) . '</strong>', '<strong>' . esc_html(intval( $installments )) . 'x</strong>' ); ?>
</p>
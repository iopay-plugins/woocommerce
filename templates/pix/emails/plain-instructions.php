<?php
/**
 * Credit Card - Plain email instructions.
 *
 * @author  Iopay
 *
 * @version 2.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

esc_html_e( 'Payment', 'woocommerce-iopay' );

echo "\n\n";

printf( esc_html__( 'Payment successfully made using %1$s credit card in %2$s.', 'woocommerce-iopay' ), esc_html( $card_brand ), intval( $installments ) . 'x' );

echo "\n\n****************************************************\n\n";

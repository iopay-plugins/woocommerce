<?php
/**
 * Iopay My Account actions.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * WC_Iopay_My_Account class.
 */
final class WC_Iopay_My_Account {
    /**
     * Initialize my account actions.
     */
    public function __construct() {
    }

    /**
     * Add banking ticket link/button in My Orders section on My Accout page.
     *
     * @param array    $actions actions
     * @param WC_Order $order   order data
     *
     * @return array
     */
    public static function my_orders_banking_ticket_link($actions, $order) {
        if ( 'iopay-banking-ticket' !== $order->payment_method ) {
            return $actions;
        }

        if ( ! in_array( $order->get_status(), array('pending', 'on-hold'), true ) ) {
            return $actions;
        }

        $data = get_post_meta( $order->id, '_wc_iopay_transaction_data', true );
        if ( ! empty( $data['boleto_url'] ) ) {
            $actions[] = array(
                'url' => $data['boleto_url'],
                'name' => __( 'Print Banking Ticket', 'woocommerce-iopay' ),
            );
        }

        return $actions;
    }
}

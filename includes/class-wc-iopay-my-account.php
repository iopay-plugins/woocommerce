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
class WC_Iopay_My_Account {
    /**
     * Initialize my account actions.
     */
    public function __construct() {
        add_filter( 'woocommerce_my_account_my_orders_actions', array($this, 'my_orders_banking_ticket_link'), 10, 2 );
    }

    /**
     * Add banking ticket link/button in My Orders section on My Accout page.
     *
     * @param array    $actions actions
     * @param WC_Order $order   order data
     *
     * @return array
     */
    public function my_orders_banking_ticket_link($actions, $order) {
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

new WC_Iopay_My_Account();

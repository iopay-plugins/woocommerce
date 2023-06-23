<?php

/**
 * Iopay Banking Ticket gateway.
 */
if ( ! defined('ABSPATH')) {
    exit;
}

/**
 * WC_Iopay_Banking_Ticket_Gateway class.
 *
 * @extends Wc_Iopay_Paymethod_Gateway
 */
class WC_Iopay_Banking_Ticket_Gateway extends Wc_Iopay_Paymethod_Gateway {
    /**
     * Constructor for the gateway.
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->id = 'iopay-banking-ticket';
        $this->icon = apply_filters('wc_iopay_banking_ticket_icon', false);
        $this->has_fields = true;
        $this->sandbox = $this->get_option('sandbox');
        $this->method_title = __('Iopay - Banking Ticket', 'woocommerce-iopay');
        $this->method_description = __('Accept banking ticket payments using Iopay.', 'woocommerce-iopay');

        // Load the form fields.
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();

        // Define user set variables.
        $this->title = $this->get_option('title');
        $this->sandbox = $this->get_option('sandbox');
        $this->description = $this->get_option('description');
        $this->api_key = $this->get_option('api_key');
        $this->encryption_key = $this->get_option('encryption_key');
        $this->email_auth = $this->get_option('email_auth');
        $this->debug = $this->get_option('debug');
        $this->interest_rate = $this->get_option('interest_rate_value');
        $this->late_fee_value = $this->get_option('late_fee_mode');
        $this->expiration_date = $this->get_option('expiration_date');

        // Active logs.
        if ('yes' === $this->debug) {
            $this->log = new WC_Logger();
        }

        // Set the API.
        $this->api = new WC_Iopay_API($this);

        // Actions.
        add_action('wp_enqueue_scripts', array($this, 'checkout_styles'));
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));
        add_action('woocommerce_email_after_order_table', array($this, 'email_instructions'), 10, 3);

        // Recurrency for wps-subscription plugin support
        add_filter( 'wps_sfw_supported_payment_gateway_for_woocommerce', array($this, 'add_subscription_support'), 10, 2 );
        add_action( 'wps_sfw_other_payment_gateway_renewal', array($this, 'process_subscription_payment'), 10, 3 );
        add_action( 'wps_sfw_subscription_cancel', array($this, 'cancel_subscription'), 10, 2 );
    }

    /**
     * This function is add a supported payment gateway.
     *
     * @param array  $supported_payment_method supported_payment_method
     * @param string $payment_method           payment_method
     *
     * @since    1.2.0
     */
    public function add_subscription_support($supported_payment_method, $payment_method) {
        if ( $this->id === $payment_method ) {
            $supported_payment_method[] = $payment_method;
        }

        return $supported_payment_method;
    }

    /**
     * Process subscription payment.
     *
     * @param WC_Order $order           order
     * @param int      $subscription_id subscription_id
     * @param string   $payment_method  payment_method
     *
     * @since    1.2.0
     */
    public function process_subscription_payment($order, $subscription_id, $payment_method) {
        if ( $order && is_object( $order ) ) {
            $order_id = $order->get_id();
            $payment_method = get_post_meta( $order_id, '_payment_method', true );
            $wps_sfw_renewal_order = get_post_meta( $order_id, 'wps_sfw_renewal_order', true );

            if ( $this->id === $payment_method && 'yes' === $wps_sfw_renewal_order ) {
                $response = $this->api->process_recurring_payment($subscription_id, $order_id);

                if ('success' === $response['result']) {
                    $order->update_status( 'wc-processing' );
                } else {
                    $order_notes = __( 'Transaction failed API error', 'woocommerce-iopay' );
                    $order->update_status( 'failed', $order_notes );

                    return;
                }
            }
        }
    }

    /**
     * This function is used to cancel subscriptions status.
     *
     * @param string $wps_subscription_id wps_subscription_id
     * @param string $status              status
     *
     * @since 1.2.0
     */
    public function cancel_subscription($wps_subscription_id, $status) {
        $wps_payment_method = get_post_meta( $wps_subscription_id, '_payment_method', true );
        if ( $this->id === $wps_payment_method ) {
            if ( 'Cancel' === $status ) {
                wps_sfw_send_email_for_cancel_susbcription( $wps_subscription_id );
                update_post_meta( $wps_subscription_id, 'wps_subscription_status', 'cancelled' );
            }
        }
    }

    /**
     * Admin page.
     */
    public function admin_options() {
        include __DIR__ . '/admin/views/html-admin-page.php';
    }

    /**
     * Check if the gateway is available to take payments.
     *
     * @return bool
     */
    public function is_available() {
        return parent::is_available() && ! empty($this->api_key) && ! empty($this->encryption_key) && $this->api->using_supported_currency();
    }

    /**
     * Settings fields.
     */
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'woocommerce-iopay'),
                'type' => 'checkbox',
                'label' => __('Enable Iopay Banking Ticket', 'woocommerce-iopay'),
                'default' => 'no',
            ),
            'sandbox' => array(
                'title' => __('Sandbox Iopay', 'woocommerce-iopay'),
                'type' => 'checkbox',
                'label' => __('Activate Iopay Sandbox', 'woocommerce-iopay'),
                'default' => 'no',
                'desc_tip' => true),
            'title' => array(
                'title' => __('Title', 'woocommerce-iopay'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'woocommerce-iopay'),
                'desc_tip' => true,
                'default' => __('Banking Ticket', 'woocommerce-iopay'),
            ),
            'description' => array(
                'title' => __('Description', 'woocommerce-iopay'),
                'type' => 'textarea',
                'description' => __('This controls the description which the user sees during checkout.', 'woocommerce-iopay'),
                'desc_tip' => true,
                'default' => __('Pay with Banking Ticket', 'woocommerce-iopay'),
            ),
            'statement_descriptor' => array(
                'title' => __('Instructions', 'woocommerce-iopay'),
                'type' => 'textarea',
                'description' => __('Instructions your customer will see on the ticket.', 'woocommerce-iopay'),
                'desc_tip' => true,
            ),
            'interest_rate_value' => array(
                'title' => __('Daily interest rate %', 'woocommerce-iopay'),
                'type' => 'text',
                'description' => __('Daily interest for delay in percentage, not to exceed 3.3% per day. Note: use 0 to not charge interest.', 'woocommerce-iopay'),
                'desc_tip' => true,
                'default' => '0',
            ),
            'late_fee_value' => array(
                'title' => __('Fine for delay in %', 'woocommerce-iopay'),
                'type' => 'text',
                'description' => __('Fine for late payment in percentage, not exceeding 100% of the value of the bill. Note: use 0 to not charge a fine.', 'woocommerce-iopay'),
                'desc_tip' => true,
                'default' => '0',
            ),
            'expiration_date' => array(
                'title' => __('Expiry days', 'woocommerce-iopay'),
                'type' => 'number',
                'description' => __('The minimum maturity period is 1 day and the maximum 180 days from the day following the issuance of the bill. If not informed, the default expiration is 3 days.', 'woocommerce-iopay'),
                'desc_tip' => true,
                'default' => 3,
                'custom_attributes' => array('minlength' => 1, 'maxlength' => 100),
            ),
            'integration' => array(
                'title' => __('Integration Settings', 'woocommerce-iopay'),
                'type' => 'title',
                'description' => '',
            ),
            'integration2' => array(
                'title' => __('Webhook - Notifications warning', 'woocommerce-iopay'),
                'type' => 'title',
                'description' => sprintf(__('Please register the notification URL %s in your panel: %s.', 'woocommerce-iopay'), '<span style="font-size:18px; font-weight:bold">( <a href="#" id="copy_link_iopay"  >' . site_url() . '/wp-json/iopay/v1/notification</a>  )</span>', '<a href="https://minhaconta.iopay.com.br/settings/online_payment">' . __('Iopay Dashboard > My Account page', 'woocommerce-iopay') . '</a>'),
            ),
            'email_auth' => array(
                'title' => __('Email Auth', 'woocommerce-iopay'),
                'type' => 'email',
                'description' => __('Email used for authentication with the API.', 'woocommerce-iopay'),
                'desc_tip' => true,
                'default' => __(get_option('admin_email'), 'woocommerce-iopay'),
            ),
            'api_key' => array(
                'title' => __('Iopay API Key', 'woocommerce-iopay'),
                'type' => 'password',
                'description' => sprintf(__('Please enter your Iopay API Key. This is needed to process the payment and notifications. Is possible get your API Key in %s.', 'woocommerce-iopay'), '<a href="https://minhaconta.iopay.com.br/login/">' . __('Iopay Dashboard > My Account page', 'woocommerce-iopay') . '</a>'),
                'default' => '',
                'custom_attributes' => array(
                    'required' => 'required',
                ),
            ),
            'encryption_key' => array(
                'title' => __('Iopay Encryption Key', 'woocommerce-iopay'),
                'type' => 'password',
                'description' => sprintf(__('Please enter your Iopay Encryption key. This is needed to process the payment. Is possible get your Encryption Key in %s.', 'woocommerce-iopay'), '<a href="https://minhaconta.iopay.com.br/login/">' . __('Iopay Dashboard > My Account page', 'woocommerce-iopay') . '</a>'),
                'default' => '',
                'custom_attributes' => array(
                    'required' => 'required',
                ),
            ),
            'testing' => array(
                'title' => __('Gateway Testing', 'woocommerce-iopay'),
                'type' => 'title',
                'description' => '',
            ),
            'debug' => array(
                'title' => __('Debug Log', 'woocommerce-iopay'),
                'type' => 'checkbox',
                'label' => __('Enable logging', 'woocommerce-iopay'),
                'default' => 'no',
                'description' => sprintf(__('Log Iopay events, such as API requests. You can check the log in %s', 'woocommerce-iopay'), '<a href="' . esc_url(admin_url('admin.php?page=wc-status&tab=logs&log_file=' . esc_attr($this->id) . '-' . sanitize_file_name(wp_hash($this->id)) . '.log')) . '">' . __('System Status &gt; Logs', 'woocommerce-iopay') . '</a>'),
            ),
        );
    }

    /**
     * Payment fields.
     */
    public function payment_fields() {
        $description = $this->get_description();
        if ( ! empty($description)) {
            echo wp_kses_post(wpautop(wptexturize($description)));
        }

        wc_get_template(
            'banking-ticket/checkout-instructions.php',
            array(),
            'woocommerce/iopay/',
            WC_Iopay::get_templates_path()
        );
    }

    /**
     * Process the payment.
     *
     * @param int $order_id order ID
     *
     * @return array redirect data
     */
    public function process_payment($order_id) {
        return $this->api->process_regular_payment($order_id);
    }

    /**
     * Define style load for frontend PIX.
     *
     * @since 1.1.1
     */
    public function checkout_styles() {
        if (is_checkout() || is_add_payment_method_page() || is_order_received_page() || is_order_received_page()) {
            wp_enqueue_style('iopay-banking-ticket-style', plugins_url('assets/css/banking-ticket.css', plugin_dir_path(__FILE__)), array(), WC_Iopay::VERSION);
        }
    }

    /**
     * Thank You page message.
     *
     * @param int $order_id order ID
     */
    public function thankyou_page($order_id) {
        $order = wc_get_order($order_id);
        $data = get_post_meta($order_id, 'data_payment_iopay', true);

        if (isset($data['barcode'], $data['url']) && in_array($order->get_status(), array('pending', 'processing', 'on-hold'), true)) {
            $template = 'payment';
            $barcode = $this->format_barcode($data['barcode']);

            wc_get_template(
                'banking-ticket/' . $template . '-instructions.php',
                array(
                    'url' => $data['url'],
                    'barcode' => $barcode,
                ),
                'woocommerce/iopay/',
                WC_Iopay::get_templates_path()
            );
        }
    }

    /**
     * Add content to the WC emails.
     *
     * @param WC_Order $order         order object
     * @param bool     $sent_to_admin send to admin
     * @param bool     $plain_text    plain text or HTML
     *
     * @return string payment instructions
     */
    public function email_instructions($order, $sent_to_admin, $plain_text = false) {
        if ($sent_to_admin || ! in_array($order->get_status(), array('processing', 'on-hold'), true) || $this->id !== $order->payment_method) {
            return;
        }

        $data = get_post_meta($order->id, '_wc_iopay_transaction_data', true);

        if (isset($data['url'])) {
            $email_type = $plain_text ? 'plain' : 'html';

            wc_get_template(
                'banking-ticket/emails/' . $email_type . '-instructions.php',
                array(
                    'url' => $data['url'],
                ),
                'woocommerce/iopay/',
                WC_Iopay::get_templates_path()
            );
        }
    }

    /**
     * Format string to a bank slip barcode.
     *
     * @since 1.1.0
     *
     * @param string $barcode
     *
     * @return string
     */
    private function format_barcode($barcode) {
        $barcode = preg_replace('/\D/', '', $barcode);

        return substr($barcode, 0, 5) . '.' . substr($barcode, 5, 5) . ' ' .
        substr($barcode, 10, 5) . '.' . substr($barcode, 15, 6) . ' ' .
        substr($barcode, 21, 5) . '.' . substr($barcode, 26, 6) . ' ' .
        substr($barcode, 32, 1) . ' ' . substr($barcode, 33);
    }
}

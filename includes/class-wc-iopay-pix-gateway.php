<?php

/**
 * Iopay PIX gateway.
 */
if ( ! defined('ABSPATH')) {
    exit;
}

/**
 * WC_Iopay_Credit_Card_Gateway class.
 *
 * @extends Wc_Iopay_Paymethod_Gateway
 */
class WC_Iopay_Pix_Gateway extends Wc_Iopay_Paymethod_Gateway {
    /**
     * Constructor for the gateway.
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->id = 'iopay-pix';
        $this->icon = apply_filters('wc_iopay_pix_icon', false);
        $this->has_fields = true;
        $this->method_title = __('Iopay - PIX', 'woocommerce-iopay');
        $this->method_description = __('Accept PIX payments using Iopay.', 'woocommerce-iopay');

        // Load the form fields.
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();

        // Define user set variables.
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->sandbox = $this->get_option('sandbox');
        $this->api_key = $this->get_option('api_key');
        $this->encryption_key = $this->get_option('encryption_key');
        $this->email_auth = $this->get_option('email_auth');
        $this->debug = $this->get_option('debug');

        // Active logs.
        if ('yes' === $this->debug) {
            $this->log = new WC_Logger();
        }

        // Set the API.
        $this->api = new WC_Iopay_API($this);

        // Actions.
        add_action('wp_enqueue_scripts', array($this, 'checkout_scripts'));
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));
        add_action('woocommerce_email_after_order_table', array($this, 'email_instructions'), 10, 3);
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
                'label' => __('Enable Iopay PIX', 'woocommerce-iopay'),
                'default' => 'no',
            ),
            'sandbox' => array(
                'title' => __('Sandbox Iopay', 'woocommerce-iopay'),
                'type' => 'checkbox',
                'label' => __('Ativar Sandbox Iopay', 'woocommerce-iopay'),
                'default' => 'no',
                'desc_tip' => true),
            'title' => array(
                'title' => __('Title', 'woocommerce-iopay'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'woocommerce-iopay'),
                'desc_tip' => true,
                'default' => __('PIX', 'woocommerce-iopay'),
            ),
            'description' => array(
                'title' => __('Description', 'woocommerce-iopay'),
                'type' => 'textarea',
                'description' => __('This controls the description which the user sees during checkout.', 'woocommerce-iopay'),
                'desc_tip' => true,
                'default' => __('Pay with PIX', 'woocommerce-iopay'),
            ),
            'integration' => array(
                'title' => __('Integration Settings', 'woocommerce-iopay'),
                'type' => 'title',
                'description' => '',
            ),
            'integration2' => array(
                'title' => __('Webhook - aviso de notificações', 'woocommerce-iopay'),
                'type' => 'title',
                'description' => sprintf(__('Por favor cadastre A URL de notificação <span style="font-size:18px; font-weight:bold">( <a href="#" id="copy_link_iopay"  >' . site_url() . '/wp-json/iopay/v1/notification</a>  )</span> em seu painel:  %s.', 'woocommerce-iopay'), '<a href="https://minhaconta.iopay.com.br/settings/online_payment">' . __('Iopay Dashboard > My Account page', 'woocommerce-iopay') . '</a>'),
            ),
            'email_auth' => array(
                'title' => __('E-mail Auth', 'woocommerce-iopay'),
                'type' => 'email',
                'description' => __('E-mail usando para autenticacao com a API.', 'woocommerce-iopay'),
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
     * Define script load for frontend PIX.
     *
     * @since 1.1.0
     */
    public function checkout_scripts() {
        if (is_checkout()) {
            wp_enqueue_script('iopay-pix', plugins_url('assets/js/pix.js', plugin_dir_path(__FILE__)), array('jquery'), date('is'), true);
        }
    }

    /**
     * Payment fields.
     */
    public function payment_fields() {
        if ($description = $this->get_description()) {
            echo wp_kses_post(wpautop(wptexturize($description)));
        }
        $cart_total = $this->get_order_total();

        echo '<div id="iopay-checkout-params" ';
        echo 'data-total="' . esc_attr($cart_total * 100) . '" ';
        echo 'data-max_installment="' . esc_attr(apply_filters('wc_iopay_checkout_pix_max_installments', $this->api->get_max_installment($cart_total))) . '"';
        echo '></div>';
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
     * Thank You page message.
     *
     * @param int $order_id order ID
     */
    public function thankyou_page($order_id) {
        $order = wc_get_order($order_id);
        $data = get_post_meta($order_id, 'data_payment_iopay', true);
        $data_success = get_post_meta($order_id, 'data_success_iopay', true);
        $id_transaction = $data['id'];

        if (isset($id_transaction) && in_array($order->get_status(), array('pending', 'processing', 'on-hold'), true)) {
            wc_get_template(
                'pix/payment-instructions.php',
                array(
                    'order' => $order,
                    'status' => $data_success['status'],
                    'qrcode_link' => $data_success['qrcode_link'],
                    'pix_qrcode_url' => $data_success['pix_qrcode_url'],
                    'expected_on' => $data_success['expected_on'],
                    'id_transaction' => $id_transaction,
                    'expiration_date' => $data['expiration_date'],
                    'pix_link' => $data['pix_link'],
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

        $order = wc_get_order($order->id);
        $data = get_post_meta($order->id, 'data_payment_iopay', true);
        $data_success = get_post_meta($order->id, 'data_success_iopay', true);
        $id_transaction = $data['id'];

        if (isset($data['pix_qrcode_url'])) {
            $email_type = $plain_text ? 'plain' : 'html';

            wc_get_template(
                'pix/emails/' . $email_type . '-instructions.php',
                array(
                    'url' => $data['pix_qrcode_url'],
                ),
                'woocommerce/iopay/',
                WC_Iopay::get_templates_path()
            );
        }
    }
}

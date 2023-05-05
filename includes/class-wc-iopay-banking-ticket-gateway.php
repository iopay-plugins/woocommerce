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
                'label' => __('Enable Iopay Banking Ticket', 'woocommerce-iopay'),
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
                'title' => __('Intruções', 'woocommerce-iopay'),
                'type' => 'textarea',
                'description' => __('Intruções que seu cliente verá no boleto.', 'woocommerce-iopay'),
                'desc_tip' => true,
            ),
            'interest_rate_value' => array(
                'title' => __('Taxa de juros diária %', 'woocommerce-iopay'),
                'type' => 'text',
                'description' => __('Juros diarios por atraso em porcentagem, nao ultrapassar 3.3% ao dia. Nota: use 0 para nao cobrar juros.', 'woocommerce-iopay'),
                'desc_tip' => true,
                'default' => '0',
            ),
            'late_fee_value' => array(
                'title' => __('Multa por atraso em %', 'woocommerce-iopay'),
                'type' => 'text',
                'description' => __('Multa por atraso em porcentagem, nao ultrapassar 100% do valor do bolero. Nota: use 0 para nao cobrar multa', 'woocommerce-iopay'),
                'desc_tip' => true,
                'default' => '0',
            ),
            'expiration_date' => array(
                'title' => __('Dias de vencimento', 'woocommerce-iopay'),
                'type' => 'number',
                'description' => __('O prazo mínimo de vencimento é de 1 dia e máximo 180 dias a contar do dia posterior à emissão do boleto. Caso não seja informado o vencimento padrão é de 3 dias', 'woocommerce-iopay'),
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
        if ($description = $this->get_description()) {
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

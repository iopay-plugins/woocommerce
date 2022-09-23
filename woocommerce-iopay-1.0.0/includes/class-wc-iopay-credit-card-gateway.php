<?php

/**
 * Iopay Credit Card gateway
 *
 * @package WooCommerce_Iopay/Gateway
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WC_Iopay_Credit_Card_Gateway class.
 *
 * @extends WC_Payment_Gateway
 */
class WC_Iopay_Credit_Card_Gateway extends WC_Payment_Gateway {

    /**
     * Constructor for the gateway.
     */
    protected $data_rate = array();

    public function __construct() {
        $this->id = 'iopay-credit-card';

        $this->icon = apply_filters('wc_iopay_credit_card_icon', false);
        $this->has_fields = true;
        $this->method_title = __('Iopay - Credit Card', 'woocommerce-iopay');
        $this->method_description = __('Accept credit card payments using Iopay.', 'woocommerce-iopay');
     

        // Load the settings.
        $this->init_settings();

        // Define user set variables.
        $this->title = $this->get_option('title');
        $this->email_auth = $this->get_option('email_auth');
        $this->description = $this->get_option('description');
        $this->sandbox = $this->get_option('sandbox');
        $this->api_key = $this->get_option('api_key');
        $this->encryption_key = $this->get_option('encryption_key');
        $this->register_refused_order = $this->get_option('register_refused_order');
        $this->max_installment = $this->get_option('max_installment');
        $this->smallest_installment = $this->get_option('smallest_installment');
        $this->free_installments = $this->get_option('free_installments', '1');
        $this->debug = $this->get_option('debug');
        $this->antifraude = $this->get_option('antifraude');


        for ($installment = 1; $installment <= 12; $installment++) {

            $destino = 'interest_rate_installment_' . $installment;
            $$destino = $destino;


            $this->$destino = $this->get_option('interest_rate_installment_' . $installment, '0');


            $data_rate['interest_rate_installment_' . $installment] = array(
                'title' => __('Juros parcela ' . $installment, 'woocommerce-iopay'),
                'type' => 'text',
                'default' => '0',
            );
        }

        $this->data_rate = $data_rate;

        // Load the form fields.
        $this->init_form_fields();



        // Active logs.
        if ('yes' === $this->debug) {
            $this->log = new WC_Logger();
        }

        // Set the API.
        $this->api = new WC_Iopay_API($this);

        // Actions.
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('wp_enqueue_scripts', array($this, 'checkout_scripts'));
        add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));
        add_action('woocommerce_email_after_order_table', array($this, 'email_instructions'), 10, 3);
        add_action('woocommerce_api_wc_iopay_credit_card_gateway', array($this, 'ipn_handler'));
    }

    /**
     * Admin page.
     */
    public function admin_options() {
        include dirname(__FILE__) . '/admin/views/html-admin-page.php';
    }

    /**
     * Check if the gateway is available to take payments.
     *
     * @return bool
     */
    public function is_available() {
        return parent::is_available() && !empty($this->api_key) && !empty($this->encryption_key) && $this->api->using_supported_currency();
    }

    /**
     * Settings fields.
     */
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'woocommerce-iopay'),
                'type' => 'checkbox',
                'label' => __('Enable Iopay Credit Card', 'woocommerce-iopay'),
                'default' => 'no',
            ),
            'sandbox' => array(
                'title' => __('Sandbox Iopay', 'woocommerce-iopay'),
                'type' => 'checkbox',
                'label' => __('Ativar Sandbox Iopay', 'woocommerce-iopay'),
                'default' => 'no',
                'desc_tip' => true,
            ),
            'title' => array(
                'title' => __('Title', 'woocommerce-iopay'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'woocommerce-iopay'),
                'desc_tip' => true,
                'default' => __('Credit Card', 'woocommerce-iopay'),
            ),
            'description' => array(
                'title' => __('Description', 'woocommerce-iopay'),
                'type' => 'textarea',
                'description' => __('This controls the description which the user sees during checkout.', 'woocommerce-iopay'),
                'desc_tip' => true,
                'default' => __('Pay with Credit Card', 'woocommerce-iopay'),
            ),
            'integration' => array(
                'title' => __('Integration Settings', 'woocommerce-iopay'),
                'type' => 'title',
                'description' => '',
            ),
            'integration2' => array(
                'title' => __('Webhook - aviso de notificações', 'woocommerce-iopay'),
                'type' => 'title',
                'description' => sprintf(__('Por favor cadastre A URL de notificação <span style="font-size:18px; font-weight:bold">( <a href="#" id="copy_link_iopay"  >' . site_url() . '/iopay/v1/notification</a>  )</span> em seu painel:  %s.', 'woocommerce-iopay'), '<a href="https://minhaconta.iopay.com.br/settings/online_payment">' . __('Iopay Dashboard > My Account page', 'woocommerce-iopay') . '</a>'),
            ),
            'email_auth' => array(
                'title' => __('E-mail Auth', 'woocommerce-iopay'),
                'type' => 'email',
                'description' => __('This email authentication.', 'woocommerce-iopay'),
                'desc_tip' => true,
                'default' => __(get_option('admin_email'), 'woocommerce-iopay'),
            ),
            'api_key' => array(
                'title' => __('Iopay API Key', 'woocommerce-iopay'),
                'type' => 'text',
                'description' => sprintf(__('Please enter your Iopay API Key. This is needed to process the payment and notifications. Is possible get your API Key in %s.', 'woocommerce-iopay'), '<a href="https://minhaconta.iopay.com.br/login/">' . __('Iopay Dashboard > My Account page', 'woocommerce-iopay') . '</a>'),
                'default' => '',
                'custom_attributes' => array(
                    'required' => 'required',
                ),
            ),
            'encryption_key' => array(
                'title' => __('Iopay Encryption Key', 'woocommerce-iopay'),
                'type' => 'text',
                'description' => sprintf(__('Please enter your Iopay Encryption key. This is needed to process the payment. Is possible get your Encryption Key in %s.', 'woocommerce-iopay'), '<a href="https://minhaconta.iopay.com.br/login/">' . __('Iopay Dashboard > My Account page', 'woocommerce-iopay') . '</a>'),
                'default' => '',
                'custom_attributes' => array(
                    'required' => 'required',
                ),
            ),
            'register_refused_order' => array(
                'title' => __('Register Refused Order', 'woocommerce-iopay'),
                'type' => 'checkbox',
                'label' => __('Register order for refused transactions', 'woocommerce-iopay'),
                'default' => 'no',
                'desc_tip' => true,
                'description' => __('Register order for refused transactions when Iopay Checkout is enabled', 'woocommerce-iopay'),
            ),
            'installments' => array(
                'title' => __('Installments', 'woocommerce-iopay'),
                'type' => 'title',
                'description' => '',
            ),
            'max_installment' => array(
                'title' => __('Number of Installment', 'woocommerce-iopay'),
                'type' => 'select',
                'class' => 'wc-enhanced-select',
                'default' => '12',
                'description' => __('Maximum number of installments possible with payments by credit card.', 'woocommerce-iopay'),
                'desc_tip' => true,
                'options' => array(
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                    '6' => '6',
                    '7' => '7',
                    '8' => '8',
                    '9' => '9',
                    '10' => '10',
                    '11' => '11',
                    '12' => '12',
                ),
            ),
            'interest_rate_installment_1' => array(
                'title' => __('Juros parcela 1', 'woocommerce-iopay'),
                'type' => 'text',
                'default' => '0',
            ),
            'interest_rate_installment_2' => array(
                'title' => __('Juros parcela 2', 'woocommerce-iopay'),
                'type' => 'text',
                'default' => '0',
            ),
            'interest_rate_installment_3' => array(
                'title' => __('Juros parcela 3', 'woocommerce-iopay'),
                'type' => 'text',
                'default' => '0',
            ),
            'interest_rate_installment_4' => array(
                'title' => __('Juros parcela 4', 'woocommerce-iopay'),
                'type' => 'text',
                'default' => '0',
            ),
            'interest_rate_installment_5' => array(
                'title' => __('Juros parcela 6', 'woocommerce-iopay'),
                'desc_tip' => true,
                'default' => '0',
            ),
            'interest_rate_installment_7' => array(
                'title' => __('Juros parcela 7', 'woocommerce-iopay'),
                'type' => 'text',
                'default' => '0',
            ),
            'interest_rate_installment_8' => array(
                'title' => __('Juros parcela 8', 'woocommerce-iopay'),
                'type' => 'text',
                'default' => '0',
            ),
            'interest_rate_installment_9' => array(
                'title' => __('Juros parcela 9', 'woocommerce-iopay'),
                'type' => 'text',
                'default' => '0',
            ),
            'interest_rate_installment_10' => array(
                'title' => __('Juros parcela 10', 'woocommerce-iopay'),
                'type' => 'text',
                'default' => '0',
            ),
            'interest_rate_installment_11' => array(
                'title' => __('Juros parcela 11', 'woocommerce-iopay'),
                'type' => 'text',
                'default' => '0',
            ),
            'interest_rate_installment_12' => array(
                'title' => __('Juros parcela 12', 'woocommerce-iopay'),
                'type' => 'text',
                'default' => '0',
            ),
            'antifraude' => array(
                'title' => __('Chave Publica Antifraude', 'woocommerce-iopay'),
                'type' => 'text',
                'description' => __('Se o seu plano possui antifraude coloque a sua chave publica aqui.', 'woocommerce-iopay'),
                'desc_tip' => false
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
     * Checkout scripts.
     */
    public function checkout_scripts() {

        if (is_checkout()) {
            
        

            $customer = array();
            wp_enqueue_script('iopay-credit-card', plugins_url('assets/js/credit-card.js', plugin_dir_path(__FILE__)), array('jquery'), date('is'), true);
            $_SESSION["iopay_session"] = date('YmdHis') . sha1(rand(1, 30));
            wp_localize_script(
                 'iopay-credit-card', 'wcIopayParams', array(
                'session_id' => $_SESSION["iopay_session"],
                'url_iopay_auth' => $this->api->get_api_url().'v1/card/authentication',
                'url_iopay_tokenize' => $this->api->get_api_url().'v1/card/tokenize/token',
                'secret' => $this->encryption_key,
                'io_seller_id' => $this->api_key,
                'email' => $this->email_auth,
                'interestRate' => $this->api->get_interest_rate(),
                'freeInstallments' => $this->free_installments,
                'postbackUrl' => WC()->api_request_url(get_class($this)),
                'customerFields' => $customer,
                'checkoutPayPage' => !empty($customer),
                'uiColor' => apply_filters('wc_iopay_checkout_ui_color', '#1a6ee1'),
                'register_refused_order' => $this->register_refused_order,
                    )
            );

            if ($this->get_option('antifraude')) {
                wp_enqueue_script('iopay-antifraude', plugins_url('assets/js/checkout_antifraude.js', plugin_dir_path(__FILE__)), array('jquery'), date('is'), true);
                wp_localize_script(
                        'iopay-antifraude', 'wcIopayParams2', array(
                    'public_key' => $this->get_option('antifraude'),
                    'plan' => 'with_anti_fraud',
                    'session_id' => $_SESSION["iopay_session"],
                    'encryptionKey' => $this->encryption_key
                        )
                );
            }
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
        $installments = $this->api->get_installments($cart_total);
        wc_get_template(
             'credit-card/payment-form.php', array(
            'cart_total' => $cart_total,
            'max_installment' => $this->max_installment,
            'smallest_installment' => $this->api->get_smallest_installment(),
            'installments' => $installments,
                ), 'woocommerce/iopay/', WC_Iopay::get_templates_path()
        );
    }

    /**
     * Process the payment.
     *
     * @param int $order_id Order ID.
     *
     * @return array Redirect data.
     */
    public function process_payment($order_id) {
        return $this->api->process_regular_payment($order_id);
    }

    

    /**
     * Thank You page message.
     *
     * @param int $order_id Order ID.
     */
    public function thankyou_page($order_id) {
        $order = wc_get_order($order_id);
        $data = get_post_meta($order_id, 'data_payment_iopay', true);
        $data_success = get_post_meta($order_id, 'data_success_iopay', true);

      
        
        if (in_array($order->get_status(), array('processing', 'on-hold'), true)) {
            wc_get_template(
                    'credit-card/payment-instructions.php', array(
                'card_brand' => $data['card_brand'],
                         'status' =>$data_success['status'],  
                'installments' => $data['installments'],
                    ), 'woocommerce/iopay/', WC_Iopay::get_templates_path()
            );
        }
    }

    /**
     * Add content to the WC emails.
     *
     * @param  object $order         Order object.
     * @param  bool   $sent_to_admin Send to admin.
     * @param  bool   $plain_text    Plain text or HTML.
     *
     * @return string                Payment instructions.
     */
    public function email_instructions($order, $sent_to_admin, $plain_text = false) {
        if ($sent_to_admin || !in_array($order->get_status(), array('processing', 'on-hold'), true) || $this->id !== $order->payment_method) {
            return;
        }

       $data = get_post_meta($order_id, 'data_payment_iopay', true);

        if (isset($data['installments'])) {
            $email_type = $plain_text ? 'plain' : 'html';

            wc_get_template(
                    'credit-card/emails/' . $email_type . '-instructions.php', array(
                'card_brand' => $data['card_brand'],
                'installments' => $data['installments'],
                    ), 'woocommerce/iopay/', WC_Iopay::get_templates_path()
            );
        }
    }

    /**
     * IPN handler.
     */
    public function ipn_handler() {
        $this->api->ipn_handler();
    }

}

<?php

/**
 * Plugin Name: IOPAY for WooCommerce
 * Plugin URI: http://github.com/claudiosmweb/woocommerce-iopay
 * Description: Gateway de pagamento IOPAY para WooCommerce.
 * Author: Jeronimo Cardoso
 * Author URI: https://iopay.com.br/
 * Version: 1.0.0
 * License: GPLv2 or later
 * Text Domain: woocommerce-iopay
 * Domain Path: /languages/
 *
 * @package WooCommerce_Iopay
 */
if (!defined('ABSPATH')) {
    exit;
}


if (!class_exists('WC_Iopay')) :

    /**
     * WooCommerce WC_Iopay main class.
     */
    class WC_Iopay {

        /**
         * Plugin version.
         *
         * @var string
         */
        const VERSION = '1.0.0';

        /**
         * Instance of this class.
         *
         * @var object
         */
        protected static $instance = null;

        /**
         * Initialize the plugin public actions.
         */
        private function __construct() {

            // define('WP_MEMORY_LIMIT', '512M');
            // Load plugin text domain.
            add_action('init', array($this, 'load_plugin_textdomain'));

            //global $Muscleboss;
            //$Muscleboss->showErrors();
            //$base = new self();
            //$base->expire_payment_scheduled();
            // Checks if WooCommerce is installed.
            if (class_exists('WC_Payment_Gateway')) {
                $this->upgrade();
                $this->includes();

                add_filter('woocommerce_payment_gateways', array($this, 'add_gateway'));
                add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'plugin_action_links'));
            } else {
                add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            }

            // Custom settings for the "Brazilian Market on WooCommerce" plugin billing fields.
            if (class_exists('Extra_Checkout_Fields_For_Brazil')) {
                add_action('wcbcf_billing_fields', array($this, 'wcbcf_billing_fields_custom_settings'));
            }

            // Runs a specific method right after the plugin activation.
            add_action('admin_init', array($this, 'after_activation'));

            // Endpoint confirmations payments 
            add_action('rest_api_init', array($this, 'register'));

            // Dismissible notices.
            add_action('wp_loaded', array($this, 'hide_notices'));
            add_action('admin_notices', array($this, 'brazilian_market_missing_notice'));
            add_action('admin_notices', array($this, 'iopay_documentation_link_notice'));


            add_action('wp_ajax_wc_iopay_pix_payment_check', array($this, 'check_pix_payment'));
            add_action('wp_ajax_nopriv_wc_iopay_pix_payment_check', array($this, 'check_pix_payment'));
//        add_action('wp_loaded', array($this, 'wp_loaded'));
//        add_action('wc_pagarme_pix_payment_schedule', $this, 'check_expired_codes');
        }

        /**
         * Check payment ajax request
         *
         * @return void
         */
        public function check_pix_payment() {



            $order_id = $_GET['key']; //wc_get_order_id_by_order_key($_GET['key']);
            $order = wc_get_order($order_id);




            if ($order) {
                $paid = $order->get_status() === 'processing' ? true : false;
                wp_send_json(['paid' => $paid]);
                die();
            }

            wp_die(esc_html__('Order not exists', 'wc-pagarme-pix-payment'), '', array('response' => 401));
        }

        /**
         * Return an notification Iopay.
         *
         * @return 200 OR 4**.
         */
        function register() {

            register_rest_route('iopay/v1', '/notification', array(
                'methods' => 'POST',
                'callback' => array($this, 'get_notification')
            ));
        }

        /**
         * Return an instance of this class.
         *
         * @return object A single instance of this class.
         */
        public static function get_instance() {
            // If the single instance hasn't been set, set it now.
            if (null === self::$instance) {
                self::$instance = new self;
            }

            return self::$instance;
        }

        /**
         * Includes.
         */
        private function includes() {
            include_once dirname(__FILE__) . '/includes/class-wc-iopay-api.php';
            include_once dirname(__FILE__) . '/includes/class-wc-iopay-my-account.php';
            include_once dirname(__FILE__) . '/includes/class-wc-iopay-banking-ticket-gateway.php';
            include_once dirname(__FILE__) . '/includes/class-wc-iopay-credit-card-gateway.php';
            include_once dirname(__FILE__) . '/includes/class-wc-iopay-pix-gateway.php';
        }

        /**
         * Load the plugin text domain for translation.
         */
        public function load_plugin_textdomain() {
            load_plugin_textdomain('woocommerce-iopay', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        }

        /**
         * Get templates path.
         *
         * @return string
         */
        public static function get_templates_path() {
            return plugin_dir_path(__FILE__) . 'templates/';
        }

        /**
         * Add the gateway to WooCommerce.
         *
         * @param  array $methods WooCommerce payment methods.
         *
         * @return array
         */
        public function add_gateway($methods) {
            $methods[] = 'WC_Iopay_Banking_Ticket_Gateway';
            $methods[] = 'WC_Iopay_Credit_Card_Gateway';
            $methods[] = 'WC_Iopay_Pix_Gateway';

            return $methods;
        }

        /**
         * Action links.
         *
         * @param  array $links Plugin links.
         *
         * @return array
         */
        public function plugin_action_links($links) {
            $plugin_links = array();

            $banking_ticket = 'wc_iopay_banking_ticket_gateway';
            $credit_card = 'wc_iopay_credit_card_gateway';
            $pix = 'wc_iopay_pix_gateway';

            $plugin_links[] = '<a href="' . esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=' . $banking_ticket)) . '">' . __('Bank Slip Settings', 'woocommerce-iopay') . '</a>';

            $plugin_links[] = '<a href="' . esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=' . $credit_card)) . '">' . __('Credit Card Settings', 'woocommerce-iopay') . '</a>';

            $plugin_links[] = '<a href="' . esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=' . $pix)) . '">' . __('PIX COnfiguração', 'woocommerce-iopay') . '</a>';


            return array_merge($plugin_links, $links);
        }

        /**
         * WooCommerce fallback notice.
         */
        public function woocommerce_missing_notice() {
            include dirname(__FILE__) . '/includes/admin/views/html-notice-missing-woocommerce.php';
        }

        /**
         * Brazilian Market plugin missing notice.
         */
        public function brazilian_market_missing_notice() {
            if (( is_admin() && get_option('woocommerce_iopay_admin_notice_missing_brazilian_market') === 'yes')) {
                // Do not show the notice if the Brazilian Market plugin is installed.
                if (class_exists('Extra_Checkout_Fields_For_Brazil')) {
                    delete_option('woocommerce_iopay_admin_notice_missing_brazilian_market');
                    return;
                }

                include dirname(__FILE__) . '/includes/admin/views/html-notice-missing-brazilian-market.php';
            }
        }

        /**
         * Iopay documentation notice.
         */
        public function iopay_documentation_link_notice() {
            if (is_admin() && get_option('woocommerce_iopay_admin_notice_documentation_link') === 'yes') {
                include dirname(__FILE__) . '/includes/admin/views/html-notice-iopay-documentation-link.php';
            }
        }

        /**
         * Upgrade.
         *
         * @since 1.0.0
         */
        private function upgrade() {
            if (is_admin()) {
                if ($old_options = get_option('woocommerce_iopay_settings')) {

                    // Banking ticket options.
                    $banking_ticket = array(
                        'enabled' => $old_options['enabled'],
                        'title' => 'Boleto bancário',
                        'sandbox' => $old_options['sandbox'],
                        'description' => '',
                        'api_key' => $old_options['api_key'],
                        'encryption_key' => $old_options['encryption_key'],
                        'email_auth' => $old_options['email_auth'],
                        'debug' => $old_options['debug'],
                        'email_auth' => $old_options['email_auth'],
                        'interest_rate_value' => $old_options['interest_rate_value'],
                        'late_fee_value' => $old_options['late_fee_value'],
                        'expiration_date' => $old_options['expiration_date']
                    );

                    // Credit card options.
                    $credit_card = array(
                        'enabled' => $old_options['enabled'],
                        'title' => 'Cartão de crédito',
                        'email_auth' => $old_options['email_auth'],
                        'sandbox' => $old_options['sandbox'],
                        'description' => '',
                        'api_key' => $old_options['api_key'],
                        'register_refused_order' => $old_options['register_refused_order'],
                        'encryption_key' => $old_options['encryption_key'],
                        'checkout' => 'no',
                        'max_installment' => $old_options['max_installment'],
                        'smallest_installment' => $old_options['smallest_installment'],
                        'interest_rate' => $old_options['interest_rate'],
                        'free_installments' => $old_options['free_installments'],
                        'debug' => $old_options['debug'],
                        'free_installments' => $old_options['free_installments'],
                        'antifraude' => $old_options['antifraude']
                    );

                    // PIX.
                    $pix = array(
                        'enabled' => $old_options['enabled'],
                        'title' => 'PIX',
                        'sandbox' => $old_options['sandbox'],
                        'description' => '',
                        'email_auth' => $old_options['email_auth'],
                        'api_key' => $old_options['api_key'],
                        'encryption_key' => $old_options['encryption_key'],
                        'debug' => $old_options['debug'],
                    );

                    update_option('woocommerce_iopay-banking-ticket_settings', $banking_ticket);
                    update_option('woocommerce_iopay-credit-card_settings', $credit_card);
                    update_option('woocommerce_iopay-pix_settings', $pix);
                    delete_option('woocommerce_iopay_settings');
                }
            }
        }

        /**
         * Custom settings for the "Brazilian Market on WooCommerce" plugin billing fields.
         *
         * @param  array $wcbcf_billing_fields "Brazilian Market on WooCommerce" plugin billing fields.
         *
         * @return array
         */
        public function wcbcf_billing_fields_custom_settings($wcbcf_billing_fields) {
            $wcbcf_billing_fields['billing_neighborhood']['required'] = true;

            return $wcbcf_billing_fields;
        }

        /**
         * Hide a notice if the GET variable is set.
         */
        public static function hide_notices() {
            if (isset($_GET['woocommerce-iopay-hide-notice'])) {
                $notice_to_hide = sanitize_text_field(wp_unslash($_GET['woocommerce-iopay-hide-notice']));
                delete_option('woocommerce_iopay_admin_notice_' . $notice_to_hide);
            }
        }

        /**
         * Activate.
         *
         * Fired by `register_activation_hook` when the plugin is activated.
         */
        public static function activation() {
            if (is_multisite()) {
                return;
            }

            add_option('woocommerce_iopay_activated', 'yes');
        }

        /**
         * After activation.
         */
        public function after_activation() {
            if (is_admin() && get_option('woocommerce_iopay_activated') === 'yes') {
                delete_option('woocommerce_iopay_activated');

                add_option('woocommerce_iopay_admin_notice_documentation_link', 'yes');
                add_option('woocommerce_iopay_admin_notice_missing_brazilian_market', 'yes');
            }
        }

        /**
         * Get Info Order 
         * @param string $token Token geretation subject.
         * @param string $url   Url Endpoint IOPAY.
         * @param string $id    id Transactiom message.
         */
        private function iopayRequestTransaction($token, $url, $id) {

            header('Content-Type: application/json');
            $ch = curl_init($url .= 'v1/transaction/get/' . $id);
            $authorization = "Authorization: Bearer " . $token;
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', $authorization)); // Inject the token into the header
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET'); // Specify the request method as get
            $result = curl_exec($ch);
            curl_close($ch);
            $dados = json_decode($result);

            if (!isset($dados->success)) {
                return false;
            }

            return $dados->success;
        }

        /**
         * Send email notification.
         *
         * @param string $subject Email subject.
         * @param string $title   Email title.
         * @param string $message Email message.
         */
        protected function send_email($subject, $title, $message) {
            $mailer = WC()->mailer();
            $mailer->send(get_option('admin_email'), $subject, $mailer->wrap_message($title, $message));
        }

        /**
         * Recept notification IOPAY.
         *
         * @param string WP_REST_Request $request $type, $id, $reference_id.
         */
        function get_notification(WP_REST_Request $request) {

            @ob_clean();
            $parameters = $request->get_params();




            $reference_id = (int) $parameters['reference_id'];
            $type = $parameters['type'];
            $status = $parameters['status'];
            $url = '';
            $token = '';

            $id = $parameters['id'];

            if ($type == 'transaction.succeeded') {

                if ($status == 'succeeded') {

                    $order = wc_get_order($reference_id);

                    if ($order->get_payment_method() == 'iopay-credit-card') {
                        $wc_iopay_api = new WC_Iopay_API(new WC_Iopay_Credit_Card_Gateway());
                    } elseif ($order->get_payment_method() == 'iopay-banking-ticket') {
                        $wc_iopay_api = new WC_Iopay_API(new WC_Iopay_Banking_Ticket_Gateway());
                    } elseif ($order->get_payment_method() == 'iopay-pix') {
                        $wc_iopay_api = new WC_Iopay_API(new WC_Iopay_Pix_Gateway());
                    } else {
                        $log = new WC_Logger();
                        $log->add('IOPAY-ERROR', json_encode($parameters));
                        wp_die(esc_html__('Iopay Request Failure', 'woocommerce-iopay'), '', array('response' => 402));
                    }

                    if (!$wc_iopay_api) {
                        $log = new WC_Logger();
                        $log->add('IOPAY-ERROR', json_encode($parameters));
                        wp_die(esc_html__('Iopay Request Failure', 'woocommerce-iopay'), '', array('response' => 402));
                    }

                    $url = $wc_iopay_api->get_api_url();
                    $token = $wc_iopay_api->getIOPayAuthorization();

                    if ($url == '' || $token == '' || $id == '') {

                        $log = new WC_Logger();
                        $log->add('IOPAY-ERROR', json_encode($parameters));
                        wp_die(esc_html__('Iopay Request Failure', 'woocommerce-iopay'), '', array('response' => 402));
                    }

                    $post_data = get_post_meta($order->id, 'data_success_iopay', true);

                    if ($post_data["id"] == $id) {

                        //consultar IOPAY
                        $dados_iopay = $this->iopayRequestTransaction($token, $url, $id);

                        $sales_receipt = $dados_iopay->sales_receipt;
                        $customer = $dados_iopay->customer;
                        $id_retorno = $dados_iopay->id;
                        $amount = $dados_iopay->amount;
                        $payment_method = $dados_iopay->payment_method;
                        $status_iopay = $dados_iopay->status;

                        if ($order->get_status() == 'on-hold' || $order->get_status() == 'pending') {

                            if ($id_retorno != $post_data["id"]) {
                                $log = new WC_Logger();
                                $log->add('IOPAY-ERROR', json_encode($parameters));
                                wp_die(esc_html__('ID nao corresponde', 'woocommerce-iopay'), '', array('response' => 402));
                            } elseif ($amount != $order->get_total()) {
                                $log = new WC_Logger();
                                $log->add('IOPAY-ERROR', json_encode($parameters));
                                wp_die(esc_html__('Pagamento divergente', 'woocommerce-iopay'), '', array('response' => 402));
                            }

                            $status = sanitize_text_field($status_iopay);


                            if ($order) {
                                $wc_iopay_api->process_order_status($order, $status);
                            } else {
                                $log = new WC_Logger();
                                $log->add('IOPAY-ERROR', json_encode($parameters));
                                wp_die(esc_html__('Iopay Request Failure', 'woocommerce-iopay'), '', array('response' => 402));
                            }
                        }
                    }
                }
            }

            return new WP_REST_Response("ok", 200);
        }

    }

    add_action('plugins_loaded', array('WC_Iopay', 'get_instance'));
    register_activation_hook(plugin_basename(__FILE__), array('WC_Iopay', 'activation'));

endif;

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
            // Load plugin text domain.
            add_action('init', array($this, 'load_plugin_textdomain'));

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

//            add_action('rest_api_init', function () {
//                register_rest_route('iopay/v1', 'notification', array(
//                    'methods' => 'GET', // array( 'GET', 'POST', 'PUT', )
//                    'callback' => 'get_notification',
//                ));
//            });
//            
            add_action('rest_api_init', array($this, 'register'));


            // Dismissible notices.
            add_action('wp_loaded', array($this, 'hide_notices'));
            add_action('admin_notices', array($this, 'brazilian_market_missing_notice'));
            add_action('admin_notices', array($this, 'iopay_documentation_link_notice'));
        }

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
         * @since 2.0.0
         */
        private function upgrade() {
            if (is_admin()) {
                if ($old_options = get_option('woocommerce_iopay_settings')) {
                    // Banking ticket options.
                    $banking_ticket = array(
                        'enabled' => $old_options['enabled'],
                        'title' => 'Boleto bancário',
                        'description' => '',
                        'api_key' => $old_options['api_key'],
                        'encryption_key' => $old_options['encryption_key'],
                        'debug' => $old_options['debug'],
                    );

                    // Credit card options.
                    $credit_card = array(
                        'enabled' => $old_options['enabled'],
                        'title' => 'Cartão de crédito',
                        'description' => '',
                        'api_key' => $old_options['api_key'],
                        'encryption_key' => $old_options['encryption_key'],
                        'checkout' => 'no',
                        'max_installment' => $old_options['max_installment'],
                        'smallest_installment' => $old_options['smallest_installment'],
                        'interest_rate' => $old_options['interest_rate'],
                        'free_installments' => $old_options['free_installments'],
                        'debug' => $old_options['debug'],
                    );

                    // PIX.
                    $pix = array(
                        'enabled' => $old_options['enabled'],
                        'title' => 'PIX',
                        'description' => '',
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
         * Process the order status.
         *
         * @param WC_Order $order  Order data.
         * @param string   $status Transaction status.
         */
        public function process_order_status($order, $status) {
            if ('yes' === $this->gateway->debug) {
                $this->gateway->log->add($this->gateway->id, 'Payment status for order ' . $order->get_order_number() . ' is now: ' . $status);
            }

            switch ($status) {
                case 'succeeded' :
                    if (!in_array($order->get_status(), array('processing', 'completed'), true)) {
                        $order->update_status('on-hold', __('Iopay: The transaction was authorized.', 'woocommerce-iopay'));
                    }

                    break;
                case 'pre_authorized':
                    $transaction_id = get_post_meta($order->id, '_wc_iopay_transaction_id', true);
                    $transaction_url = '<a href="https://minhaconta.iopay.com.br/login/#/transactions/' . intval($transaction_id) . '">https://minhaconta.iopay.com.br/login/#/transactions/' . intval($transaction_id) . '</a>';

                    /* translators: %s transaction details url */
                    $order->update_status('on-hold', __('Iopay: You should manually analyze this transaction to continue payment flow, access %s to do it!', 'woocommerce-iopay'), $transaction_url);

                    break;
                case 'paid' :
                    if (!in_array($order->get_status(), array('processing', 'completed'), true)) {
                        $order->add_order_note(__('Iopay: Transaction paid.', 'woocommerce-iopay'));
                    }

                    // Changing the order for processing and reduces the stock.
                    $order->payment_complete();

                    break;

                case 'failed' :
                    $order->update_status('failed', __('Iopay: The transaction was rejected by the card company or by fraud.', 'woocommerce-iopay'));

                    $transaction_id = get_post_meta($order->id, '_wc_iopay_transaction_id', true);
                    $transaction_url = '<a href="https://minhaconta.iopay.com.br/login/#/transactions/' . intval($transaction_id) . '">https://minhaconta.iopay.com.br/login/#/transactions/' . intval($transaction_id) . '</a>';

                    $this->send_email(
                            sprintf(esc_html__('The transaction for order %s was rejected by the card company or by fraud', 'woocommerce-iopay'), $order->get_order_number()), esc_html__('Transaction failed', 'woocommerce-iopay'), sprintf(esc_html__('Order %1$s has been marked as failed, because the transaction was rejected by the card company or by fraud, for more details, see %2$s.', 'woocommerce-iopay'), $order->get_order_number(), $transaction_url)
                    );

                    break;
                case 'refunded' :
                    $order->update_status('refunded', __('Iopay: The transaction was refunded/canceled.', 'woocommerce-iopay'));

                    $transaction_id = get_post_meta($order->id, '_wc_iopay_transaction_id', true);
                    $transaction_url = '<a href="https://minhaconta.iopay.com.br/login/#/transactions/' . intval($transaction_id) . '">https://minhaconta.iopay.com.br/login/#/transactions/' . intval($transaction_id) . '</a>';

                    $this->send_email(
                            sprintf(esc_html__('The transaction for order %s refunded', 'woocommerce-iopay'), $order->get_order_number()), esc_html__('Transaction refunded', 'woocommerce-iopay'), sprintf(esc_html__('Order %1$s has been marked as refunded by Iopay, for more details, see %2$s.', 'woocommerce-iopay'), $order->get_order_number(), $transaction_url)
                    );

                    break;


                default :
                    break;
            }
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

        function get_notification(WP_REST_Request $request) {


            @ob_clean();
            $parameters = $request->get_params();
            $id = $parameters['id'];
            $reference_id = (int) $parameters['reference_id'];
            $type = $parameters['type'];
            $order = wc_get_order($reference_id);



            // data_payment_iopay

            $post_data = get_post_meta($order->id, 'data_success_iopay', true);

            if ($post_data["id"] == $id) {

                //consultar IOPAY
                $dados_iopay['return'] = 'ok';
                $dados_iopay['id'] = 38;

                $status = sanitize_text_field('succeeded');

                if ($order && $order->id == $dados_iopay["id"]) {
                    $this->process_order_status($order, $status);
                }else {
                    
                    wp_die(esc_html__('Iopay Request Failure', 'woocommerce-iopay'), '', array('response' => 402));
           
                }
                
                
        }

            return new WP_REST_Response("ok", 200);
        }

//        function get_notification(WP_REST_Request $request) {
//            // You can get the combined, merged set of parameters:
//            $parameters = $request->get_params();
//
//            return array($parameters);
//        }
    }

    add_action('plugins_loaded', array('WC_Iopay', 'get_instance'));
    register_activation_hook(plugin_basename(__FILE__), array('WC_Iopay', 'activation'));

endif;

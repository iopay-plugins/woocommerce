<?php

/**
 * Iopay API.
 */
if ( ! defined('ABSPATH')) {
    exit;
}

/**
 * WC_Iopay_API class.
 */
class WC_Iopay_API {
    /**
     * API URL.
     */
    public const API_URL = 'https://api.iopay.com.br/api/';

    /**
     * Gateway class.
     *
     * @var WC_Payment_Gateway
     */
    protected $gateway;

    /**
     * API URL.
     *
     * @var string
     */
    protected $api_url = 'https://api.iopay.com.br/api/';

    /**
     * API URL.
     *
     * @var string
     */
    protected $api_url_sandbox = 'https://sandbox.api.iopay.com.br/api/';

    /**
     * JS Library URL.
     *
     * @var string
     */
    protected $js_url = 'https://assets.iopay/js/iopay.min.js';

    /**
     * Checkout JS Library URL.
     *
     * @var string
     */
    protected $checkout_js_url = 'https://assets.iopay/checkout/checkout.js';

    /**
     * Constructor.
     *
     * @param WC_Payment_Gateway $gateway gateway instance
     */
    public function __construct($gateway = null) {
        $this->gateway = $gateway;
        add_action('admin_enqueue_scripts', array($this, 'iopay_scripts'));
    }

    /**
     * Get API URL.
     *
     * @return string
     */
    public function get_api_url() {
        if ('yes' == $this->gateway->sandbox) {
            return $this->api_url_sandbox;
        }

        return $this->api_url;
    }

    /**
     * Get API URL.
     *
     * @return string
     */
    public function get_api_sandbox_url() {
        return $this->api_sandbox_url;
    }

    /**
     * Get JS Library URL.
     *
     * @return string
     */
    public function get_js_url() {
        return $this->js_url;
    }

    /**
     * Get Checkout JS Library URL.
     *
     * @return string
     */
    public function get_checkout_js_url() {
        return $this->checkout_js_url;
    }

    /**
     * Returns a bool that indicates if currency is amongst the supported ones.
     *
     * @return bool
     */
    public function using_supported_currency() {
        return 'BRL' === get_woocommerce_currency();
    }

    /**
     * Get the smallest installment amount.
     *
     * @return int
     */
    public function get_smallest_installment() {
        return ( 5 > $this->gateway->smallest_installment ) ? 500 : wc_format_decimal($this->gateway->smallest_installment) * 100;
    }

    /**
     * Get the interest rate.
     *
     * @return float
     */
    public function get_interest_rate() {
        return wc_format_decimal($this->gateway->interest_rate);
    }

    /**
     * Obtem o access_token para utilização dos recursos da API IOPAY.
     *
     * @return mixed
     */
    public function getIOPayAuthorization() {
        try {
            $settings = $this->gateway->settings;
            $credentials = array(
                'io_seller_id' => $settings['api_key'],
                'email' => $settings['email_auth'],
                'secret' => $settings['encryption_key'],
            );

            $uri = $this->get_api_url() . 'auth/login';

            $result = wp_remote_post($uri, array(
                'headers' => array('Content-Type' => 'application/json'),
                'body' => json_encode($credentials),
            ));

            $auth = json_decode(wp_remote_retrieve_body($result));

            if (is_wp_error($result)) {
                throw new Exception('Request failed or invalid', 500);
            }

            $token = $auth->access_token;
            if ('' == $token || null == $token) {
                return 'Unauthorized';
            }

            if (isset($auth->error)) {
                return $auth->error;
            }

            return $token;
        } catch (Exception $e) {
            // TODO add better logging
            /* if(is_wp_error($result)) {
                $this->gateway->log->log('error', $e->getMessage() . \PHP_EOL . var_export($result->get_error_messages(), true), array('source' => $this->gateway->id));
            } */

            return $e->getMessage();
        }
    }

    /**
     * Get the installments.
     *
     * @param float $amount order amount
     *
     * @return array
     */
    public function get_installments($amount) {
        for ($installment = 1; $installment <= 12; ++$installment) {
            $destino = 'interest_rate_installment_' . $installment;
            ${$destino} = $destino;
            $interest_rate[$installment] = $this->gateway->{$destino};
        }

        // Set the installment data.
        $data = array(
            'amount' => $amount,
            'interest_rate' => $interest_rate,
            'max_installments' => $this->gateway->max_installment,
            'free_installments' => $this->gateway->free_installments,
            'smallest_installment' => $this->gateway->smallest_installment,
        );

        return $data;
    }

    /**
     * Get max installment.
     *
     * @param float $amount order amount
     *
     * @return int
     */
    public function get_max_installment($amount) {
        $installments = $this->get_installments($amount);
        $smallest_installment = $this->get_smallest_installment();
        $max = 1;

        foreach ($installments as $number => $installment) {
            if ($smallest_installment > $installment['installment_amount']) {
                break;
            }

            $max = $number;
        }

        return $max;
    }

    /**
     * Generate the transaction data.
     *
     * @param WC_Order $order  order data
     * @param array    $posted form posted data
     *
     * @return array transaction data
     */
    public function generate_transaction_data($order) {
        // Set the request data.
        $data = array(
            'io_seller_id' => $this->gateway->api_key,
            'encryption_key' => $this->gateway->encryption_key,
            'currency' => 'BRL',
            'email' => $this->gateway->email_auth,
            'amount' => $order->get_total() * 100,
            'postback_url' => WC()->api_request_url(get_class($this->gateway)),
            'customer' => array(
                'id' => $order->get_user_id(),
                'name' => trim($order->billing_first_name . ' ' . $order->billing_last_name),
                'email' => $order->billing_email,
            ),
            'metadata' => array(
                'order_number' => $order->get_order_number(),
            ),
        );

        if (1 == $order->billing_persontype) {
            $documento = $order->billing_cpf;
            $customer_type = 'person_natural';
        } else {
            $documento = $order->billing_cnpj;
            $customer_type = 'person_legal';
        }

        if ( ! empty($order->billing_phone)) {
            $phone_aux = $order->billing_phone;

            $ddd = substr($phone_aux, 0, 5);
            $number_phone = substr($phone_aux, 5);

            $billing_phone = trim($ddd) . $number_phone;
        }
        $iopay_customer = false; // get_user_meta($order->get_user_id(), 'iopay_customer_'.$this->gateway->api_key);

        if ( ! $iopay_customer) {
            $endpoint = 'v1/customer/new';
            $data_customer = array(
                'first_name' => $order->billing_first_name,
                'last_name' => $order->billing_last_name,
                'email' => $order->billing_email,
                'taxpayer_id' => $documento,
                'phone_number' => $billing_phone,
                'customer_type' => $customer_type,
                'address' => array(
                    'line1' => $order->billing_address_1,
                    'line2' => $order->billing_number,
                    'line3' => $order->billing_address_2,
                    'neighborhood' => $order->billing_neighborhood,
                    'city' => $order->billing_city,
                    'state' => $order->billing_state,
                    'postal_code' => $order->billing_postcode,
                ),
            );

            $iopay_customer = $this->iopayRequest($this->get_api_url() . $endpoint, $data_customer);

            update_user_meta($order->get_user_id(), 'iopay_customer_' . $this->gateway->api_key, array_map('sanitize_text_field', $iopay_customer));
        }

        $string_produtos = '';

        foreach ($order->get_items() as $item) {
            $dados_item = $item->get_data();

            $items[] = array(
                'name' => trim($dados_item['name']),
                'code' => (string) $item->get_product_id(),
                'amount' => (int) number_format($dados_item['total'] / $dados_item['quantity'], 0, '', ''),
                'quantity' => $dados_item['quantity'],
            );

            $string_produtos .= 'Compra Produto: ' . trim($dados_item['name']) . ' - ';
        }

        $data['products'] = $items;

        if ( ! empty($order->billing_phone)) {
            $phone = $this->only_numbers($order->billing_phone);

            $data['customer']['phone'] = array(
                'ddd' => substr($phone, 0, 2),
                'number' => substr($phone, 2),
            );

            $billing_phone = '(' . substr($phone, 0, 2) . ')' . substr($phone, 2);
        }

        // Address.
        if ( ! empty($order->billing_address_1)) {
            $data['customer']['address'] = array(
                'street' => $order->billing_address_1,
                'complementary' => $order->billing_address_2,
                'zipcode' => $this->only_numbers($order->billing_postcode),
            );

            // Non-WooCommerce default address fields.
            if ( ! empty($order->billing_number)) {
                $data['customer']['address']['street_number'] = $order->billing_number;
            }
            if ( ! empty($order->billing_neighborhood)) {
                $data['customer']['address']['neighborhood'] = $order->billing_neighborhood;
            }
        }

        // Set the document number.
        if (class_exists('Extra_Checkout_Fields_For_Brazil')) {
            $wcbcf_settings = get_option('wcbcf_settings');
            $person_type = (string) $wcbcf_settings['person_type'];
            if ('0' !== $person_type) {
                if (( '1' === $person_type && '1' === $order->billing_persontype ) || '2' === $person_type) {
                    $data['customer']['document_number'] = $this->only_numbers($order->billing_cpf);
                }

                if (( '1' === $person_type && '2' === $order->billing_persontype ) || '3' === $person_type) {
                    $data['customer']['name'] = $order->billing_company;
                    $data['customer']['document_number'] = $this->only_numbers($order->billing_cnpj);
                }
            }
        } else {
            if ( ! empty($order->billing_cpf)) {
                $data['customer']['document_number'] = $this->only_numbers($order->billing_cpf);
            }
            if ( ! empty($order->billing_cnpj)) {
                $data['customer']['name'] = $order->billing_company;
                $data['customer']['document_number'] = $this->only_numbers($order->billing_cnpj);
            }
        }

        // Set the customer gender.
        if ( ! empty($order->billing_sex)) {
            $data['customer']['sex'] = strtoupper(substr($order->billing_sex, 0, 1));
        }

        // Set the customer birthdate.
        if ( ! empty($order->billing_birthdate)) {
            $birthdate = explode('/', $order->billing_birthdate);

            $data['customer']['born_at'] = $birthdate[1] . '-' . $birthdate[0] . '-' . $birthdate[2];
        }

        if ('iopay-credit-card' === $this->gateway->id) {
            $installment = $_POST['iopay_installments'];
            $destino = 'interest_rate_installment_' . $installment;
            ${$destino} = $destino;

            $interest_rate = $this->gateway->${$destino};

            $total_juros = $order->get_total();
            $total_sem_juros = 0;

            if ($interest_rate > 0) {
                // $total_parcela = $order->get_total() / $installment;
                // $tax = ((($order->get_total()) * $interest_rate) / 100);
                $total_juros = ((($order->get_total() * $interest_rate) / 100) + $order->get_total());
                $total_sem_juros = $total_juros - $order->get_total();
            }

            // Set up the Item Fee
            $fee = new WC_Order_Item_Fee();

            // Give the Fee a name e.g. Discount
            $fee->set_name('Acréscimo cartão de crédito');

            // Set the Fee
            $fee->set_total($total_sem_juros);

            // Add to the Order
            $order->add_item($fee);

            // Recalculate the totals. IMPORTANT!
            $order->calculate_totals();

            // Save the Order
            $order->save();

            $data['payment_method'] = 'credit';
            $setting = $this->gateway->settings;

            $data['data_creditcard'] = array(
                'amount' => number_format($total_juros * 100, 0, '', ''),
                'currency' => 'BRL',
                'description' => 'Produto teste',
                'token' => $_POST['token'],
                'capture' => 1,
                'statement_descriptor' => 'Compra com cartao',
                'installment_plan' => array('number_installments' => (int) $installment),
                'io_seller_id' => $this->gateway->api_key,
                'payment_type' => 'credit',
                'reference_id' => $order->get_order_number(),
                'products' => $items,
            );

            if ($setting['antifraude']) {
                $phone_aux = $_POST['billing_phone'];

                $ddd = substr($phone_aux, 0, 5);
                $number_phone = substr($phone_aux, 5);

                $billing_phone = trim($ddd) . $number_phone;

                $taxpayer_id = str_replace(array('.', '-'), array('', ''), $_POST['billing_cpf']);
                $firstname = null == $_POST['shipping_first_name'] ? $_POST['billing_first_name'] : $_POST['shipping_first_name'];
                $lastname = null == $_POST['shipping_last_name'] ? $_POST['billing_last_name'] : $_POST['shipping_last_name'];
                $address_1 = null == $_POST['billing_address_1'] ? $_POST['billing_address_1'] : $_POST['billing_address_1'];
                $address_2 = null == $_POST['shipping_number'] ? $_POST['billing_number'] : $_POST['shipping_number'];
                $address_3 = null == $_POST['billing_address_2'] ? $_POST['billing_address_2'] : $_POST['billing_address_2'];
                $postal_code = null == $_POST['shipping_postcode'] ? $_POST['billing_postcode'] : $_POST['shipping_postcode'];
                $city = null == $_POST['shipping_city'] ? $_POST['billing_city'] : $_POST['shipping_city'];
                $state = null == $_POST['shipping_state'] ? $_POST['billing_state'] : $_POST['shipping_state'];
                $client_type = 'pf';
                $phone_number = $billing_phone;
                $antifraud_sessid = $_POST['session_id'];

                $shipping = array(
                    'taxpayer_id' => (string) trim($taxpayer_id),
                    'firstname' => (string) $firstname,
                    'lastname' => (string) $lastname,
                    'address_1' => (string) $address_1,
                    'address_2' => (string) $address_2,
                    'address_3' => (string) $address_3,
                    'postal_code' => $postal_code,
                    'city' => $city,
                    'state' => $state,
                    'client_type' => $client_type,
                    'phone_number' => $phone_number,
                );

                $data['data_creditcard']['antifraud_sessid'] = $antifraud_sessid;
                $data['data_creditcard']['shipping'] = $shipping;
            }
        } elseif ('iopay-banking-ticket' === $this->gateway->id) {
            $data['payment_method'] = 'boleto';
            $setting = $this->gateway->settings;
            $expiration_date = date('Y-m-d', strtotime('+3 days', strtotime(date('Y-m-d'))));
            $interest_value = (float) str_replace(',', '.', $setting['interest_rate_value']);
            $late_fee_value = (float) str_replace(',', '.', $setting['late_fee_value']);

            if ($setting['expiration_date'] > 0) {
                $expiration_date = date('Y-m-d', strtotime('+' . $setting['expiration_date'] . ' days', strtotime(date('Y-m-d'))));
            }

            if ($interest_value > 0) {
                $data['data_boleto'] = array(
                    'amount' => (int) number_format($order->get_total() * 100, 0, '', ''),
                    'currency' => 'BRL',
                    'description' => $string_produtos,
                    'statement_descriptor' => $setting['statement_descriptor'],
                    'io_seller_id' => $this->gateway->api_key,
                    'payment_type' => 'boleto',
                    'reference_id' => $order->get_order_number(),
                    'products' => $items,
                    'expiration_date' => $expiration_date,
                    'interest' => array(
                        'mode' => 'daily_percentage',
                        'value' => number_format($interest_value, 2, '.', ''),
                    ),
                    'late_fee' => array(
                        'mode' => 'percentage',
                        'value' => number_format($late_fee_value, 2, '.', ''),
                    ),
                );
            } else {
                $data['data_boleto'] = array(
                    'amount' => (int) number_format($order->get_total() * 100, 0, '', ''),
                    'currency' => 'BRL',
                    'description' => $string_produtos,
                    'statement_descriptor' => $setting['statement_descriptor'],
                    'io_seller_id' => $this->gateway->api_key,
                    'payment_type' => 'boleto',
                    'reference_id' => $order->get_order_number(),
                    'products' => $items);
            }
        } elseif ('iopay-pix' === $this->gateway->id) {
            $data['payment_method'] = 'pix';
            $setting = $this->gateway->settings;

            $data['data_pix'] = array(
                'amount' => (int) number_format($order->get_total() * 100, 0, '', ''),
                'currency' => 'BRL',
                'description' => $string_produtos,
                'io_seller_id' => $this->gateway->api_key,
                'payment_type' => 'pix',
                'reference_id' => $order->get_order_number(),
            );
        }

        // Add filter for Third Party plugins.
        return apply_filters('wc_iopay_transaction_data', $data, $order);
    }

    /**
     * Get customer data from checkout pay page.
     *
     * @return array
     */
    public function get_customer_data_from_checkout_pay_page() {
        global $wp;

        $order = wc_get_order((int) $wp->query_vars['order-pay']);
        $data = $this->generate_transaction_data($order, array());
        $customer = array();

        if (empty($data['customer'])) {
            return $customer;
        }

        $_customer = $data['customer'];
        $customer['customerName'] = $_customer['name'];
        $customer['customerEmail'] = $_customer['email'];

        if (isset($_customer['document_number'])) {
            $customer['customerDocumentNumber'] = $_customer['document_number'];
        }

        if (isset($_customer['address'])) {
            $customer['customerAddressStreet'] = $_customer['address']['street'];
            $customer['customerAddressComplementary'] = $_customer['address']['complementary'];
            $customer['customerAddressZipcode'] = $_customer['address']['zipcode'];

            if (isset($_customer['address']['street_number'])) {
                $customer['customerAddressStreetNumber'] = $_customer['address']['street_number'];
            }
            if (isset($_customer['address']['neighborhood'])) {
                $customer['customerAddressNeighborhood'] = $_customer['address']['neighborhood'];
            }
        }

        if (isset($_customer['phone'])) {
            $customer['customerPhoneDdd'] = $_customer['phone']['ddd'];
            $customer['customerPhoneNumber'] = $_customer['phone']['number'];
        }

        return $customer;
    }

    /**
     * Generate checkout data.
     *
     * @param WC_Order $order order data
     * @param string   $token checkout token
     *
     * @return array checkout data
     */
    public function generate_checkout_data($order, $token) {
        $transaction = $this->get_transaction_data($order, $token);
        $installments = $this->get_installments($order->get_total());

        // Valid transaction.
        if ( ! isset($transaction['amount'])) {
            return array('error' => __('Invalid transaction data.', 'woocommerce-iopay'));
        }

        // Test if using more installments that allowed.
        if ($this->gateway->max_installment < $transaction['installments'] || empty($installments[$transaction['installments']])) {
            if ('yes' === $this->gateway->debug) {
                $this->gateway->log->add($this->gateway->id, 'Payment made with more installments than allowed for order ' . $order->get_order_number());
            }

            return array('error' => __('Payment made with more installments than allowed.', 'woocommerce-iopay'));
        }

        $installment = $installments[$transaction['installments']];

        // Test smallest installment amount.
        if (1 !== intval($transaction['installments']) && $this->get_smallest_installment() > $installment['installment_amount']) {
            if ('yes' === $this->gateway->debug) {
                $this->gateway->log->add($this->gateway->id, 'Payment divided into a lower amount than permitted for order ' . $order->get_order_number());
            }

            return array('error' => __('Payment divided into a lower amount than permitted.', 'woocommerce-iopay'));
        }

        // Check the transaction amount.
        if (intval($transaction['amount']) !== intval($installment['amount'])) {
            if ('yes' === $this->gateway->debug) {
                $this->gateway->log->add($this->gateway->id, 'Wrong payment amount total for order ' . $order->get_order_number());
            }

            return array('error' => __('Wrong payment amount total.', 'woocommerce-iopay'));
        }

        $data = array(
            'api_key' => $this->gateway->api_key,
            'amount' => $transaction['amount'],
            'metadata' => array(
                'order_number' => $order->get_order_number(),
            ),
        );

        return apply_filters('wc_iopay_checkout_data', $data);
    }

    /**
     * Do the transaction.
     *
     * @param WC_Order $order order data
     * @param array    $args  transaction args
     * @param string   $token checkout token
     *
     * @return array response data
     */
    public function do_transaction($order, $args) {
        if ('yes' === $this->gateway->debug) {
            $this->gateway->log->add($this->gateway->id, 'Doing a transaction for order ' . $order->get_order_number() . '...');
        }

        $response = $this->do_request($args);

        if (is_wp_error($response)) {
            if ('yes' === $this->gateway->debug) {
                $this->gateway->log->add($this->gateway->id, 'WP_Error in doing the transaction: ' . $response->error->message);
            }

            return array();
        }

        $data = $response;

        if (isset($data->error)) {
            if ('yes' === $this->gateway->debug) {
                $this->gateway->log->add($this->gateway->id, 'Failed to make the transaction: ' . print_r($response, true));
            }

            return $data;
        }

        if ('yes' === $this->gateway->debug) {
            $this->gateway->log->add($this->gateway->id, 'Transaction completed successfully! The transaction response is: ' . print_r($data, true));
        }

        return $data;
    }

    /**
     * Process regular payment.
     *
     * @param int $order_id order ID
     *
     * @return array redirect data
     */
    public function process_regular_payment($order_id) {
        $order = wc_get_order($order_id);
        $data = $this->generate_transaction_data($order);
        $transaction = $this->do_transaction($order, $data);

//            echo '<pre>';
//            var_dump($transaction);
//            exit;
//

        if (isset($transaction->status)) {
            wc_add_notice($transaction->status, 'error');

            return array(
                'result' => 'fail',
            );
        }
        if (isset($transaction->error)) {
            wc_add_notice($transaction->error->message, 'error');

            return array(
                'result' => 'fail',
            );
        }

        $this->save_order_meta_fields($order_id, $transaction);
        $this->process_order_status($order, $transaction['status']);
        // Empty the cart.
        WC()->cart->empty_cart();

        // Redirect to thanks page.
        return array(
            'result' => 'success',
            'redirect' => $this->gateway->get_return_url($order),
        );
    }

    /**
     * Process the order status.
     *
     * @param WC_Order $order  order data
     * @param string   $status transaction status
     */
    public function process_order_status($order, $status) {
        if ('yes' === $this->gateway->debug) {
            $this->gateway->log->add($this->gateway->id, 'Payment status for order ' . $order->get_order_number() . ' is now: ' . $status);
        }

        switch ($status) {
            case 'succeeded':
                if ( ! in_array($order->get_status(), array('on-hold', 'processing', 'completed'), true)) {
                    $order->update_status('processing', __('Iopay: The transaction was authorized.', 'woocommerce-iopay'));
                }

                break;

            case 'pre_authorized':
                $transaction_id = get_post_meta($order->id, '_wc_iopay_transaction_id', true);
                $transaction_url = '<a href="https://minhaconta.iopay.com.br/login/#/transactions/' . intval($transaction_id) . '">https://minhaconta.iopay.com.br/login/#/transactions/' . intval($transaction_id) . '</a>';

                // translators: %s transaction details url
                $order->update_status('on-hold', __('Iopay: You should manually analyze this transaction to continue payment flow, access %s to do it!', 'woocommerce-iopay'), $transaction_url);

                break;

            case 'paid':
                if ( ! in_array($order->get_status(), array('processing', 'completed'), true)) {
                    $order->add_order_note(__('Iopay: Transaction paid.', 'woocommerce-iopay'));
                }

                // Changing the order for processing and reduces the stock.
                $order->payment_complete();

                break;

            case 'failed':
                $order->update_status('failed', __('Iopay: The transaction was rejected by the card company or by fraud.', 'woocommerce-iopay'));

                $transaction_id = get_post_meta($order->id, '_wc_iopay_transaction_id', true);
                $transaction_url = '<a href="https://minhaconta.iopay.com.br/login/#/transactions/' . intval($transaction_id) . '">https://minhaconta.iopay.com.br/login/#/transactions/' . intval($transaction_id) . '</a>';

                $this->send_email(
                    sprintf(esc_html__('The transaction for order %s was rejected by the card company or by fraud', 'woocommerce-iopay'), $order->get_order_number()),
                    esc_html__('Transaction failed', 'woocommerce-iopay'),
                    sprintf(esc_html__('Order %1$s has been marked as failed, because the transaction was rejected by the card company or by fraud, for more details, see %2$s.', 'woocommerce-iopay'), $order->get_order_number(), $transaction_url)
                );

                break;

            case 'refunded':
                $order->update_status('refunded', __('Iopay: The transaction was refunded/canceled.', 'woocommerce-iopay'));

                $transaction_id = get_post_meta($order->id, '_wc_iopay_transaction_id', true);
                $transaction_url = '<a href="https://minhaconta.iopay.com.br/login/#/transactions/' . intval($transaction_id) . '">https://minhaconta.iopay.com.br/login/#/transactions/' . intval($transaction_id) . '</a>';

                $this->send_email(
                    sprintf(esc_html__('The transaction for order %s refunded', 'woocommerce-iopay'), $order->get_order_number()),
                    esc_html__('Transaction refunded', 'woocommerce-iopay'),
                    sprintf(esc_html__('Order %1$s has been marked as refunded by Iopay, for more details, see %2$s.', 'woocommerce-iopay'), $order->get_order_number(), $transaction_url)
                );

                break;

            default:
                break;
        }
    }

    public function iopay_scripts() {
        wp_enqueue_script('iopay-main', plugins_url('assets/js/main.js', plugin_dir_path(__FILE__)), array('jquery'), date('is'), true);
    }

    /**
     * Only numbers.
     *
     * @param string|int $string string to convert
     *
     * @return string|int
     */
    protected function only_numbers($string) {
        return preg_replace('([^0-9])', '', $string);
    }

    /**
     * Do requests in the Iopay API.
     *
     * @param array $data request data
     *
     * @return array request response
     */
    protected function do_request($data = array()) {
        $iopay_customer = get_user_meta($data['customer']['id'], 'iopay_customer_' . $this->gateway->api_key);

        if ('boleto' == $data['payment_method']) {
            $data = $data['data_boleto'];
        } elseif ('pix' == $data['payment_method']) {
            $data = $data['data_pix'];
        } elseif ('credit' == $data['payment_method']) {
            $data = $data['data_creditcard'];
        }

        $endpoint = 'v1/transaction/new/' . $iopay_customer[0]['id'];

        return $this->iopayRequest($this->get_api_url() . $endpoint, $data);
    }

    /**
     * Get card brand name.
     *
     * @param string $brand card brand
     *
     * @return string
     */
    protected function get_card_brand_name($brand) {
        $names = array(
            'visa' => __('Visa', 'woocommerce-iopay'),
            'mastercard' => __('MasterCard', 'woocommerce-iopay'),
            'amex' => __('American Express', 'woocommerce-iopay'),
            'aura' => __('Aura', 'woocommerce-iopay'),
            'jcb' => __('JCB', 'woocommerce-iopay'),
            'diners' => __('Diners', 'woocommerce-iopay'),
            'elo' => __('Elo', 'woocommerce-iopay'),
            'hipercard' => __('Hipercard', 'woocommerce-iopay'),
            'discover' => __('Discover', 'woocommerce-iopay'),
        );

        return isset($names[$brand]) ? $names[$brand] : $brand;
    }

    /**
     * Save order meta fields.
     * Save fields as meta data to display on order's admin screen.
     *
     * @param int   $id   order ID
     * @param array $data order data
     */
    protected function save_order_meta_fields($id, $data) {
        $data['payment_method'] = (array) $data['payment_method'];
        $data['payment_method']['metadata'] = (array) $data['payment_method']['metadata'];

        // Transaction data.
        $payment_data = array_map(
            'sanitize_text_field',
            $data
        );

        update_post_meta($id, 'data_success_iopay', array_map('sanitize_text_field', $data));
        update_post_meta($id, 'data_payment_iopay', array_map('sanitize_text_field', $data['payment_method']));

        // Meta data.
        $meta_data = array(
            __('Banking Ticket URL', 'woocommerce-iopay') => sanitize_text_field($data['payment_method']['url']),
            __('Credit Card', 'woocommerce-iopay') => $this->get_card_brand_name(sanitize_text_field($data['card_brand'])),
            __('Installments', 'woocommerce-iopay') => sanitize_text_field($data['installments']),
            __('Total paid', 'woocommerce-iopay') => number_format(intval($data['amount']) / 100, wc_get_price_decimals(), wc_get_price_decimal_separator(), wc_get_price_thousand_separator()),
            __('Anti Fraud Score', 'woocommerce-iopay') => sanitize_text_field($data['antifraud_score']),
            '_wc_iopay_transaction_data' => $payment_data,
            '_wc_iopay_transaction_id' => intval($data['id']),
            '_transaction_id' => intval($data['id']),
        );

        $order = wc_get_order($id);

        // WooCommerce 3.0 or later.
        if ( ! method_exists($order, 'update_meta_data')) {
            foreach ($meta_data as $key => $value) {
                update_post_meta($id, $key, $value);
            }
        } else {
            foreach ($meta_data as $key => $value) {
                $order->update_meta_data($key, $value);
            }

            $order->save();
        }
    }

    /**
     * Send email notification.
     *
     * @param string $subject email subject
     * @param string $title   email title
     * @param string $message email message
     */
    protected function send_email($subject, $title, $message) {
        $mailer = WC()->mailer();
        $mailer->send(get_option('admin_email'), $subject, $mailer->wrap_message($title, $message));
    }

    private function iopayRequest($url, $data, $method = 'POST') {
        try {
            $token = $this->getIOPayAuthorization();

            $header = array(
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            );

            $post = json_encode($data);

            switch ($method) {
                case 'POST':
                    $result = wp_remote_post($url, array(
                        'headers' => $header,
                        'body' => $post,
                    ));

                    break;

                case 'GET':
                    $result = wp_remote_get($url, array(
                        'headers' => $header,
                    ));

                    break;

                default:
                    throw new Exception('Method not allowed', 404);

                    break;
            }

            if (is_wp_error($result)) {
                throw new Exception('Request failed or invalid', 500);
            }

            $data = wp_remote_retrieve_body($result);
            $data = json_decode($data);

            if ($data->success) {
                return (array) $data->success;
            }

            return $data;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}

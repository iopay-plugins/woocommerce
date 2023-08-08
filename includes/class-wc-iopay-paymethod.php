<?php

/**
 * IoPay payment method main class.
 */
if ( ! defined('ABSPATH')) {
    exit;
}

/**
 * Wc_Iopay_Paymethod_Gateway class.
 *
 * @since 1.1.0
 *
 * @extends WC_Payment_Gateway
 */
abstract class Wc_Iopay_Paymethod_Gateway extends WC_Payment_Gateway {
    /**
     * Defines gateway environment.
     *
     * @since 1.1.0
     *
     * @var string
     *
     * @see
     */
    public $sandbox;

    /**
     * Debug option for the payment gateway.
     *
     * @since 1.1.0
     *
     * @var string
     *
     * @see
     */
    public $debug;

    /**
     * WooCommeerce Logger.
     *
     * @since 1.1.0
     *
     * @var WC_Logger
     *
     * @see
     */
    public $log;

    /**
     * API Gateway.
     *
     * @since 1.1.0
     *
     * @var WC_Iopay_API
     *
     * @see
     */
    public $api;

    /**
     * IoPay api key credential.
     *
     * @since 1.1.0
     *
     * @var string
     *
     * @see
     */
    public $api_key;

    /**
     * Interest rate for bank slip after
     * expiration date.
     *
     * @since 1.1.0
     *
     * @var string
     *
     * @see
     */
    public $interest_rate;

    /**
     * IoPay encryption key credential.
     *
     * @since 1.1.0
     *
     * @var string
     *
     * @see
     */
    public $encryption_key;

    /**
     * IoPay email credential.
     *
     * @since 1.1.0
     *
     * @var string
     *
     * @see
     */
    public $email_auth;

    /**
     * Credit card max installment value.
     *
     * @since 1.1.0
     *
     * @var string
     *
     * @see
     */
    public $max_installment;

    /**
     * Credit card minimum installment value.
     *
     * @since 1.1.0
     *
     * @var string
     *
     * @see
     */
    public $smallest_installment;

    /**
     * Credit card installments without
     * any tax.
     *
     * @since 1.1.0
     *
     * @var string
     *
     * @see
     */
    public $free_installments;

    /**
     * Late fee value for bank slip after
     * expiration date.
     *
     * @since 1.1.0
     *
     * @var string
     *
     * @see
     */
    protected $late_fee_value;

    /**
     * Expiration date for the bank slip
     * in days.
     *
     * @since 1.1.0
     *
     * @var string
     *
     * @see
     */
    protected $expiration_date;

    /**
     * Register refused credit card
     * payment as a WC_Order.
     *
     * @since 1.1.0
     *
     * @var string
     *
     * @see
     */
    protected $register_refused_order;

    /**
     * Antifraud for IoPay credit card payment
     * is enabled.
     *
     * @since 1.1.0
     *
     * @var string
     *
     * @see
     */
    protected $antifraude;

    /**
     * Interest rate for each installment.
     *
     * @since 1.1.0
     *
     * @var string[][]
     *
     * @see
     */
    protected $data_rate;
}

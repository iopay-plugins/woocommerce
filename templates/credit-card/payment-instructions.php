<?php
defined('ABSPATH') || exit;

$paid = 'succeeded' === $status ? true : false;

if (false == $paid) {
    $paid = 'paid' === $status ? true : false;
}

?>


<div class="text-center">

    <div id="successPixPaymentBox"
        style="display: <?php esc_attr_e($paid ? 'block' : 'none'); ?>;">
        <h4>Obrigado pelo pagamento!</h4>
        <svg id="successAnimation" class="animated" xmlns="http://www.w3.org/2000/svg" width="180" height="180"
            viewBox="0 0 70 70">
            <path id="successAnimationResult" fill="#D8D8D8"
                d="M35,60 C21.1928813,60 10,48.8071187 10,35 C10,21.1928813 21.1928813,10 35,10 C48.8071187,10 60,21.1928813 60,35 C60,48.8071187 48.8071187,60 35,60 Z M23.6332378,33.2260427 L22.3667622,34.7739573 L34.1433655,44.40936 L47.776114,27.6305926 L46.223886,26.3694074 L33.8566345,41.59064 L23.6332378,33.2260427 Z" />
            <circle id="successAnimationCircle" cx="35" cy="35" r="24" stroke="#979797" stroke-width="2"
                stroke-linecap="round" fill="transparent" />
            <polyline id="successAnimationCheck" stroke="#979797" stroke-width="2" points="23 34 34 43 47 27"
                fill="transparent" />
        </svg>
    </div>
    <div id="watingPixPaymentBox"
        style="display: <?php esc_attr_e($paid ? 'none' : 'block'); ?>;">

        <?php

        switch ($status) {
            case 'pre_authorized':
                _e('Payment under review.', 'woocommerce-iopay');

                break;

            case 'failed':
                _e('Payment failed, please contact our call center, or try again later.', 'woocommerce-iopay');

                break;

            default:
                _e('There was an error with your payment.', 'woocommerce-iopay');

                break;
        }
?>

    </div>
</div>
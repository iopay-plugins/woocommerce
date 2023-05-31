<?php
defined('ABSPATH') || exit;

if ($order) {
    $paid = 'succeeded' === $status ? true : false;
}

ob_start();

$copy_button_html = $pix_qrcode_url;
$copy_button_html = ob_get_clean();
$order_recived_message = 'Voce tem 15min para pagar com qrcode';

ob_start();

$qr_code_html = $qrcode_link;
$qr_code_html = ob_get_clean();

$qr_code = $pix_link;
?>


<div class="text-center">
    <section class="woocommerce-order-details"
        style="display: <?php esc_attr_e($paid ? 'none' : 'block'); ?>;">


        <table class="woocommerce-table" style="width: 100%">

            <thead>
                <tr>
                    <th class="woocommerce-table__product-name product-name">QRCODE</th>
                    <th class="woocommerce-table__product-table product-total">PIX COPIA E COLA</th>
                </tr>
            </thead>

            <tbody>
                <tr class="woocommerce-table">

                    <td class="woocommerce-table__product-name product-name">
                        <img src="<?php esc_attr_e($pix_qrcode_url); ?>" />

                    </td>

                    <td class="woocommerce-table__product-total product-total">
                        <button class="button copy-qr-code"><i class="fa fa-copy fa-lg pr-3"></i>Clique aqui para copiar
                            o código</button><br><br>
                        <textarea class="button copy-qr-code" cols="40"
                            rows="6"><?php esc_html_e($pix_link); ?></textarea>
                        <p class="text-success qrcode-copyed"
                            style="text-align: center; display: none; margin-top: 15px;">Código copiado com
                            sucesso!<br>Vá até o aplicativo do seu banco e cole o código.</p>


                    </td>

                </tr>

            </tbody>


        </table>

    </section>

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
        if (preg_match('/\[copy_button\]/i', $order_recived_message)) {
            $order_recived_message = preg_replace('/\[copy_button\]/i', $copy_button_html, $order_recived_message, 1);
        } else {
            $order_recived_message .= sprintf('<p>%s</p>', $copy_button_html);
        }

        if (preg_match('/\[qr_code\]/i', $order_recived_message)) {
            $order_recived_message = preg_replace('/\[qr_code\]/i', $qr_code_html, $order_recived_message, 1);
        } else {
            $order_recived_message .= sprintf('<p>%s</p>', $qr_code_html);
        }

        if (preg_match('/\[text_code\]/i', $order_recived_message)) {
            $order_recived_message = preg_replace('/\[text_code\]/i', $qr_code, $order_recived_message, 1);
        }

        if (preg_match('/\[expiration_date\]/i', $order_recived_message)) {
            $order_recived_message = preg_replace('/\[expiration_date\]/i', date('d/m/Y H:i:s', strtotime($expiration_date)), $order_recived_message, 1);
        }

        esc_html_e($order_recived_message);
?>

        <div><input type="hidden"
                value="<?php esc_attr_e($qr_code); ?>"
                id="pixQrCodeInput"></div>
        <input type="hidden" name="wc_iopay_pix_order_key"
            value="<?php esc_attr_e( sanitize_text_field( $order->id ) ); ?>" />

    </div>
</div>
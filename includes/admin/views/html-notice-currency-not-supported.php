<?php
/**
 * Notice: Currency not supported.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?>

<div class="error inline">
    <p><strong><?php esc_html_e( 'Iopay Disabled', 'woocommerce-iopay' ); ?></strong>:
        <?php printf( wp_kses( __( 'Currency %s is not supported. Works only with Brazilian Real.', 'woocommerce-iopay' ), array('code' => array()) ), '<code>' . esc_html( get_woocommerce_currency() ) . '</code>' ); ?>
    </p>
</div>

<?php
?>
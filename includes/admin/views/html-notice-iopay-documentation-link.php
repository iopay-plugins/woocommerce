<?php
/**
 * Notice: Iopay documentation link.
 */
?>

<div class="updated notice is-dismissible">
    <p><?php esc_html_e( 'We recommend checking out our documentation before starting using', 'woocommerce-iopay' ); ?>
        <strong><?php esc_html_e( 'WooCommerce Iopay', 'woocommerce-iopay' ); ?></strong>!
    </p>
    <p>
        <a href="https://docs-api.iopay.com.br/#intro" class="button button-primary"
            target="_blank"><?php esc_html_e( 'Iopay documentation', 'woocommerce-iopay' ); ?></a>
    </p>
    <p><a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'woocommerce-iopay-hide-notice', 'documentation_link' ) ) ); ?>"
            class="notice-dismiss" style="text-decoration:none;"></a></p>
</div>
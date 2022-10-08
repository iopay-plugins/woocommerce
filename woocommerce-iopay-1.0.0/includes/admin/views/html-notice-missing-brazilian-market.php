<?php
/**
 * Notice: Missing Brazilian Market.
 *
 * @package WooCommerce_Iopay/Admin/Notices
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$is_installed = false;

if ( function_exists( 'get_plugins' ) ) {
	$all_plugins  = get_plugins();
	$is_installed = ! empty( $all_plugins['woocommerce-extra-checkout-fields-for-brazil/woocommerce-extra-checkout-fields-for-brazil.php'] );
}

?>

<div class="updated notice is-dismissible">
	<p><strong><?php esc_html_e( 'WooCommerce Iopay', 'woocommerce-iopay' ); ?></strong> <?php esc_html_e( 'recommends using the last version of Brazilian Market plugin to work!', 'woocommerce-iopay' ); ?></p>

	<?php if ( $is_installed && current_user_can( 'install_plugins' ) ) : ?>
		<p><a href="<?php echo esc_url( wp_nonce_url( self_admin_url( 'plugins.php?action=activate&plugin=woocommerce-extra-checkout-fields-for-brazil/woocommerce-extra-checkout-fields-for-brazil.php&plugin_status=active' ), 'activate-plugin_woocommerce-extra-checkout-fields-for-brazil/woocommerce-extra-checkout-fields-for-brazil.php' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Active Brazilian Market', 'woocommerce-iopay' ); ?></a></p>
	<?php else : ?>
		<?php if ( current_user_can( 'install_plugins' ) ) : ?>
			<p><a href="<?php echo esc_url( wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=woocommerce-extra-checkout-fields-for-brazil' ), 'install-plugin_woocommerce-extra-checkout-fields-for-brazil' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Install Brazilian Market', 'woocommerce-iopay' ); ?></a></p>
		<?php else : ?>
			<p><a href="https://wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/" class="button button-primary"><?php esc_html_e( 'Install Brazilian Market', 'woocommerce-iopay' ); ?></a></p>
		<?php endif; ?>
	<?php endif; ?>

	<p><a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'woocommerce-iopay-hide-notice', 'missing_brazilian_market' ) ) ); ?>" class="notice-dismiss" style="text-decoration:none;"></a></p>
</div>

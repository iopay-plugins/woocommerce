<?php
/**
 * Credit Card - Checkout form.
 *
 * @author  Iopay
 * @package WooCommerce_Iopay/Templates
 * @version 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<fieldset id="iopay-credit-cart-form">
	<p class="form-row">
		<label for="iopay-card-holder-name"><?php esc_html_e( 'Card Holder Name', 'woocommerce-iopay' ); ?><span class="required">*</span></label>
		<input id="iopay-card-holder-name" class="input-text" type="text" autocomplete="off" style="font-size: 1.5em; padding: 8px;" />
	</p>
	<p class="form-row">
		<label for="iopay-card-number"><?php esc_html_e( 'Card Number', 'woocommerce-iopay' ); ?> <span class="required">*</span></label>
		<input id="iopay-card-number" class="input-text wc-credit-card-form-card-number" type="text" maxlength="20" autocomplete="off" placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;" style="font-size: 1.5em; padding: 8px;" />
	</p>
	<div class="clear"></div>
	<p class="form-row form-row-first">
		<label for="iopay-card-expiry"><?php esc_html_e( 'Expiry (MM/YY)', 'woocommerce-iopay' ); ?> <span class="required">*</span></label>
		<input id="iopay-card-expiry" class="input-text wc-credit-card-form-card-expiry" type="text" autocomplete="off" placeholder="<?php esc_html_e( 'MM / YY', 'woocommerce-iopay' ); ?>" style="font-size: 1.5em; padding: 8px;" />
	</p>
	<p class="form-row form-row-last">
		<label for="iopay-card-cvc"><?php esc_html_e( 'Card Code', 'woocommerce-iopay' ); ?> <span class="required">*</span></label>
		<input id="iopay-card-cvc" class="input-text wc-credit-card-form-card-cvc" type="text" autocomplete="off" placeholder="<?php esc_html_e( 'CVC', 'woocommerce-iopay' ); ?>" style="font-size: 1.5em; padding: 8px;" />
	</p>
	<div class="clear"></div>
	<?php if ( apply_filters( 'wc_iopay_allow_credit_card_installments', 1 < $max_installment ) ) : ?>
		<p class="form-row form-row-wide">
			<label for="iopay-card-installments"><?php esc_html_e( 'Installments', 'woocommerce-iopay' ); ?> <span class="required">*</span></label>
			<select name="iopay_installments" id="iopay-installments" style="font-size: 1.5em; padding: 8px; width: 100%;">
				<option value="0"><?php printf( esc_html__( 'Please, select the number of installments', 'woocommerce-iopay' ) ); ?></option>
				<?php
				foreach ( $installments as $number => $installment ) :
					if ( 1 !== $number && $smallest_installment > $installment['installment_amount'] ) {
						break;
					}

					$interest           = ( ( $cart_total * 100 ) < $installment['amount'] ) ? sprintf( __( '(total of %s)', 'woocommerce-iopay' ), strip_tags( wc_price( $installment['amount'] / 100 ) ) ) : __( '(interest-free)', 'woocommerce-iopay' );
					$installment_amount = strip_tags( wc_price( $installment['installment_amount'] / 100 ) );
					?>
				<option value="<?php echo absint( $installment['installment'] ); ?>"><?php printf( esc_html__( '%1$dx of %2$s %3$s', 'woocommerce-iopay' ), absint( $installment['installment'] ), esc_html( $installment_amount ), esc_html( $interest ) ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
	<?php endif; ?>
</fieldset>

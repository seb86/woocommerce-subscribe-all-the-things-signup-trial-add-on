<?php
/**
 * Cart functionality for converting cart items to subscriptions.
 *
 * @class   WCSATT_STT_Cart
 * @version 1.0.0
 */

class WCSATT_STT_Cart extends WCS_ATT_Cart {

	/**
	 * Initialize the cart.
	 *
	 * @access public
	 * @static
	 */
	public static function init() {
		// Overrides the price of the subscription for sign up fee and/or trial on the 'wcsatt_cart_item' filter.
		add_filter( 'wcsatt_cart_item', __CLASS__ . '::update_cart_item_sub_data', 10, 1 );
	}

	/**
	 * Updates the cart item data for a subscription product that
	 * has a sign-up fee and/or trial period applied.
	 *
	 * @access public
	 * @static
	 * @param  array $cart_item
	 * @return array
	 */
	public static function update_cart_item_sub_data( $cart_item ) {
		$active_scheme = WCS_ATT_Schemes::get_active_subscription_scheme( $cart_item );

		$subscription_prices = WCS_ATT_Scheme_Prices::get_active_subscription_scheme_prices( $cart_item, $active_scheme );

		if ( $active_scheme && $cart_item['data']->is_converted_to_sub == 'yes' ) {

			// Subscription Price
			$price = $cart_item['data']->subscription_price;

			// This isn't a child item and there is a sign up fee?
			$sign_up_fee = !isset($cart_item['data']->parent) && isset( $subscription_prices['sign_up_fee'] ) ? $subscription_prices['sign_up_fee'] : '';

			// Put both the subscription price and the sign-up fee together.
			$new_price = $price + $sign_up_fee;

			if ( $sign_up_fee > 0 ) {
				$cart_item['data']->initial_amount = $new_price;
				$cart_item['data']->subscription_sign_up_fee = $sign_up_fee;
			}

			$trial_length = isset( $subscription_prices['trial_length'] ) ? $subscription_prices['trial_length'] : 0;
			$trial_period = isset( $subscription_prices['trial_period'] ) ? $subscription_prices['trial_period'] : '';

			// If a trial length is more than zero then set the conditions for the cart.
			if ( $trial_length > 0 ) {
				$cart_item['data']->subscription_trial_length = $trial_length;
				$cart_item['data']->subscription_trial_period = $trial_period;
			} else {
				$cart_item['data']->subscription_trial_length = 0;
				$cart_item['data']->subscription_trial_period = '';
			}

		}

		return $cart_item;
	} // END update_cart_item_sub_data()

}

WCSATT_STT_Cart::init();

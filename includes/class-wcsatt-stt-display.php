<?php
/**
 * Templating and styling functions.
 *
 * @class WCSATT_STT_Display
 * @since 1.0.0
 */

class WCSATT_STT_Display extends WCS_ATT_Display {

	/**
	 * Initialize the display.
	 *
	 * @access public
	 * @static
	 */
	public static function init() {
		// Adds the sign up fee and trial data to the price html on the 'wcsatt_overridden_subscription_prices_product' filter.
		add_filter( 'wcsatt_overridden_subscription_prices_product', __CLASS__ . '::add_sub_scheme_data_price_html', 10, 3 );

		// Adds the extra subscription scheme data to the product object on the 'wcsatt_converted_product_for_scheme_option' filter.
		add_filter( 'wcsatt_converted_product_for_scheme_option', __CLASS__ . '::sub_product_scheme_option', 10, 2 );

		// Filters the price string to include the sign up fee and/or trial to pass per scheme option on the 'wcsatt_single_product_subscription_scheme_price_html' filter.
		add_filter( 'wcsatt_single_product_subscription_scheme_price_html', __CLASS__ . '::get_price_string', 10, 2 );

		// Filters the lowest price string to include the sign up fee on the 'wcsatt_get_single_product_lowest_price_string' filter.
		add_filter( 'wcsatt_get_single_product_lowest_price_string', __CLASS__ . '::get_lowest_price_string', 10, 2 );

		// Filters the lowest price subscription scheme data on the 'wcsatt_get_lowest_price_sub_scheme_data' filter.
		add_filter( 'wcsatt_get_lowest_price_sub_scheme_data', __CLASS__ . '::get_lowest_price_sub_scheme_data', 10, 2 );

		// Adds the sign-up and/or trial data to the subscription scheme prices on the 'wcsatt_subscription_scheme_prices' filter.
		add_filter( 'wcsatt_subscription_scheme_prices', __CLASS__ . '::add_subscription_scheme_prices', 10, 2 );
	}

	/**
	 * Adds the additional subscription scheme data for products with attached subscription schemes.
	 *
	 * @access public
	 * @static
	 * @param  object     $_product
	 * @param  array      $subscription_scheme
	 * @param  WC_Product $product
	 * @return string
	 */
	public static function add_sub_scheme_data_price_html( $_product, $subscription_scheme, $product ) {
		if ( isset( $subscription_scheme[ 'subscription_sign_up_fee' ] ) ) {
			$_product->subscription_sign_up_fee = $subscription_scheme[ 'subscription_sign_up_fee' ];
		}

		if ( isset( $subscription_scheme[ 'subscription_trial_length' ] ) ) {
			$_product->subscription_trial_length = $subscription_scheme[ 'subscription_trial_length' ];
		}

		if ( isset( $subscription_scheme[ 'subscription_trial_period' ] ) ) {
			$_product->subscription_trial_period = $subscription_scheme[ 'subscription_trial_period' ];
		}

		return $_product;
	} // END add_sub_scheme_data_price_html()

	/**
	 * Adds the extra subscription scheme data to the product object.
	 * This allows the subscription price to change the initial and
	 * recurring subscriptions.
	 *
	 * @access public
	 * @static
	 * @param  object $_cloned
	 * @param  array  $subscription_scheme
	 * @return object
	 */
	public static function sub_product_scheme_option( $_cloned, $subscription_scheme ) {
		if ( isset( $subscription_scheme[ 'subscription_sign_up_fee' ] ) && $subscription_scheme[ 'subscription_sign_up_fee' ] > 0 ) {
			$_cloned->subscription_sign_up_fee = $subscription_scheme[ 'subscription_sign_up_fee' ];
		}

		if ( isset( $subscription_scheme[ 'subscription_trial_length' ] ) && 0 != $subscription_scheme[ 'subscription_trial_length' ] ) {
			$_cloned->subscription_trial_length = $subscription_scheme[ 'subscription_trial_length' ];
			$_cloned->subscription_trial_period = $subscription_scheme[ 'subscription_trial_period' ];
		}

		return $_cloned;
	} // END sub_product_scheme_option()

	/**
	 * Filters the price string to include the sign up fee and/or trial 
	 * to pass per subscription scheme option.
	 *
	 * @access public
	 * @static
	 * @param  array $prices
	 * @param  array $subscription_scheme
	 * @return array
	 */
	public static function get_price_string( $prices, $subscription_scheme ) {
		if ( isset( $subscription_scheme[ 'subscription_sign_up_fee' ] ) && $subscription_scheme[ 'subscription_sign_up_fee' ] > 0 ) {
			$prices[ 'sign_up_fee' ] = $subscription_scheme[ 'subscription_sign_up_fee' ];
		}

		if ( isset( $subscription_scheme[ 'subscription_trial_length' ] ) && 0 != $subscription_scheme[ 'subscription_trial_length' ] ) {
			$prices[ 'trial_length' ] = true;
		}

		return $prices;
	} // END get_price_string()

	/**
	 * Filters the price string to include the sign up 
	 * fee on the lowest subscription scheme.
	 *
	 * @access public
	 * @static
	 * @param  array $prices
	 * @param  array $lowest_subscription_scheme
	 * @return array
	 */
	public static function get_lowest_price_string( $prices,  $lowest_subscription_scheme ) {
		if ( isset( $lowest_subscription_scheme[ 'sign_up_fee' ] ) && $lowest_subscription_scheme[ 'sign_up_fee' ] > 0 ) {
			$prices[ 'sign_up_fee' ] = $lowest_subscription_scheme[ 'sign_up_fee' ];
		}

		return $prices;
	} // END get_lowest_price_string()

	/**
	 * Adds the sign-up fee to the lowest subscription scheme option.
	 *
	 * @access public
	 * @static
	 * @param array $data
	 * @param array $lowest_scheme
	 * @return array
	 */
	public static function get_lowest_price_sub_scheme_data( $data, $lowest_scheme ) {
		if ( isset( $lowest_scheme['subscription_sign_up_fee'] ) && $lowest_scheme['subscription_sign_up_fee'] > 0 ) {
			$data['sign_up_fee'] = $lowest_scheme['subscription_sign_up_fee'];
		}

		return $data;
	} // END get_lowest_price_sub_scheme_data()

	/**
	 * Adds the sign-up and/or trial data to the subscription scheme prices.
	 *
	 * @access public
	 * @static
	 * @param  array $prices
	 * @param  array $subscription_scheme
	 * @return array
	 */
	public static function add_subscription_scheme_prices( $prices, $subscription_scheme ) {
		if ( isset( $subscription_scheme[ 'subscription_sign_up_fee' ] ) ) {
			$prices[ 'sign_up_fee' ] = $subscription_scheme[ 'subscription_sign_up_fee' ];
		}

		if ( isset( $subscription_scheme[ 'subscription_trial_length' ] ) ) {
			$prices[ 'trial_length' ] = $subscription_scheme[ 'subscription_trial_length' ];
		}

		if ( isset( $subscription_scheme[ 'subscription_trial_period' ] ) ) {
			$prices[ 'trial_period' ] = $subscription_scheme[ 'subscription_trial_period' ];
		}

		return $prices;
	} // END add_subscription_scheme_prices()

}

WCSATT_STT_Display::init();

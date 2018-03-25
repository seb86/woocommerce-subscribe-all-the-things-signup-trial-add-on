<?php
/**
 * Templating and styling functions.
 *
 * @author   Sébastien Dumont
 * @category Template/Display
 * @package  WooCommerce Subscribe All the Things: Sign-up and Free Trial Add-on
 * @class    WCSATT_STT_Display
 * @since    1.0.0
 * @version  2.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WCSATT_STT_Display {

	/**
	 * Initialize the display.
	 *
	 * @access public
	 * @static
	 */
	public static function init() {
		// Adds the sign-up and/or trial data to the subscription scheme prices on the 'wcsatt_subscription_scheme_prices' filter.
		add_filter( 'wcsatt_subscription_scheme_prices', array( __CLASS__, 'add_subscription_scheme_prices' ), 10, 2 );
	}

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

} // END class

WCSATT_STT_Display::init();

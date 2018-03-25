<?php
/**
 * Filters the product meta data in the admin for subscription schemes.
 *
 * @author   Sébastien Dumont
 * @category Product Data
 * @package  WooCommerce Subscribe All the Things: Sign-up and Free Trial Add-on
 * @since    2.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Product meta-box data for SATT-enabled product types.
 *
 * @class   WCSATT_STT_Meta_Box_Product_Data
 * @version 2.0.0
 */
class WCSATT_STT_Meta_Box_Product_Data {

	/**
	 * Initialize the product meta.
	 *
	 * @access public
	 * @static
	 */
	public static function init() {
		// Subscription scheme options displayed on the 'wcsatt_subscription_scheme_product_content' action.
		add_action( 'wcsatt_subscription_scheme_product_content', array( __CLASS__, 'wcsatt_stt_fields' ), 15, 3 );

		// Filters the subscription scheme data to process the sign up and trial options on the 'wcsatt_processed_scheme_data' filter.
		add_filter( 'wcsatt_processed_scheme_data', array( __CLASS__, 'wcsatt_stt_processed_scheme_data' ), 10, 2 );
	}

	/**
	 * Adds the trial and sign up fields under the subscription section.
	 *
	 * @access  public
	 * @static
	 * @since   1.0.0
	 * @version 2.0.0
	 * @param   int   $index
	 * @param   array $scheme_data
	 * @param   int   $post_id
	 * @return  void
	 */
	public static function wcsatt_stt_fields( $index, $scheme_data, $post_id ) {
		if ( ! empty( $scheme_data ) ) {
			$subscription_sign_up_fee  = ! empty( $scheme_data[ 'subscription_sign_up_fee' ] ) ? $scheme_data[ 'subscription_sign_up_fee' ] : '';
			$subscription_trial_length = isset( $scheme_data[ 'subscription_trial_length' ] ) ? $scheme_data[ 'subscription_trial_length' ] : 0;
			$subscription_trial_period = isset( $scheme_data[ 'subscription_trial_period' ] ) ? $scheme_data[ 'subscription_trial_period' ] : '';
		} else {
			$subscription_sign_up_fee  = '';
			$subscription_trial_length = 0;
			$subscription_trial_period = '';
		}

		echo '<div class="subscription_sign_up_fee">';

		// Sign-up Fee
		woocommerce_wp_text_input( array(
			'id'          => '_satt_subscription_sign_up_fee',
			'class'       => 'subscription_pricing_method_input',
			// translators: %s is a currency symbol / code
			'label'       => sprintf( __( 'Sign-up Fee (%s)', 'wc-satt-stt' ), get_woocommerce_currency_symbol() ),
			'placeholder' => _x( 'e.g. 9.90', 'example price', 'wc-satt-stt' ),
			'description' => __( 'Optionally include an amount to be charged at the outset of the subscription. The sign-up fee will be charged immediately, even if the product has a free trial or the payment dates are synced.', 'wc-satt-stt' ),
			'desc_tip'    => true,
			'type'        => 'text',
			'custom_attributes' => array(
				'step' => 'any',
				'min'  => '0',
			),
			'name'        => 'wcsatt_schemes[' . $index . '][subscription_sign_up_fee]',
			'value'       => $subscription_sign_up_fee
		) );

		echo '</div>';
		echo '<div class="subscription_trial">' .
		'<p class="form-field _satt_subscription_trial_period_field ">' .
		'<label for="_satt_subscription_trial_' . $index . '">' . esc_html( 'Free Trial', 'wc-satt-stt' ) . '</label>' .
		'<span class="wrap">' .
		'<label class="wcs_hidden_label" for="_satt_subscription_trial_length_' . $index . '">' . esc_html( 'Free Trial Length', 'wc-satt-stt' ) . '</label>' .
		'<input class="subscription_pricing_method_input" style="" name="wcsatt_schemes[' . $index . '][subscription_trial_length]" id="_satt_subscription_trial_length" value="' . $subscription_trial_length . '" placeholder="" type="text">' .
		'<label class="wcs_hidden_label" for="_satt_subscription_trial_period_' . $index . '">' . esc_html( 'Free Trial Period', 'wc-satt-stt' ) . '</label>' .
		'<select id="_satt_subscription_trial_period" name="wcsatt_schemes[' . $index . '][subscription_trial_period]" class="subscription_pricing_method_input" style="">';

		$time_periods = wcs_get_available_time_periods();

		foreach ( $time_periods as $key => $period ) {
			echo '<option value="' . $period . '" '. selected( $subscription_trial_period, $period, false ) . '>' . $period . '</option>';
		}

		echo '</select>' .
		'</span>';

		echo wc_help_tip( sprintf( _x( 'An optional period of time to wait before charging the first recurring payment. Any sign up fee will still be charged at the outset of the subscription. %s', 'Trial period dropdown\'s description in pricing fields', 'wc-satt-stt' ), WC_Subscriptions_Admin::get_trial_period_validation_message() ) );

		echo '</p>' .
		'</div>';
	} // END wcsatt_stt_fields()

	/**
	 * Filters the subscription scheme data to pass the
	 * sign up and trial options when saving.
	 *
	 * @access  public
	 * @static
	 * @since   1.0.0
	 * @version 2.0.0
	 * @param   array      $posted_scheme
	 * @param   WC_Product $product
	 * @return  void
	 */
	public static function wcsatt_stt_processed_scheme_data( $posted_scheme, $product ) {
		// Copy variable type fields.
		if ( $product->is_type( 'variable' ) ) {
			if ( isset( $posted_scheme[ 'subscription_sign_up_fee_variable' ] ) ) {
				$posted_scheme[ 'subscription_sign_up_fee' ] = $posted_scheme[ 'subscription_sign_up_fee_variable' ];
			}
			if ( isset( $posted_scheme[ 'subscription_trial_length_variable' ] ) ) {
				$posted_scheme[ 'subscription_trial_length' ] = $posted_scheme[ 'subscription_trial_length_variable' ];
			}
			if ( isset( $posted_scheme[ 'subscription_trial_period_variable' ] ) ) {
				$posted_scheme[ 'subscription_trial_period' ] = $posted_scheme[ 'subscription_trial_period_variable'];
			}
		}

		// Format subscription sign up fee.
		if ( isset( $posted_scheme[ 'subscription_sign_up_fee' ] ) ) {
			$posted_scheme[ 'subscription_sign_up_fee' ] = ( $posted_scheme[ 'subscription_sign_up_fee' ] === '' ) ? '' : wc_format_decimal( $posted_scheme[ 'subscription_sign_up_fee' ] );
		}

		// Make sure trial period is within allowable range.
		$subscription_ranges = wcs_get_subscription_ranges();
		$max_trial_length = count( $subscription_ranges[ $posted_scheme[ 'subscription_trial_period' ] ] ) - 1;

		// Format subscription trial length.
		if ( isset( $posted_scheme[ 'subscription_trial_length' ] ) && $posted_scheme[ 'subscription_trial_length' ] > $max_trial_length ) {
			$posted_scheme[ 'subscription_trial_length' ] = ( $posted_scheme[ 'subscription_trial_length' ] === '' ) ? '' : absint( $posted_scheme[ 'subscription_trial_length' ] );
		}

		// Format subscription trial period.
		$trial_periods = apply_filters( 'wcsatt_trial_periods', array( 'day', 'week', 'month', 'year' ) );
		if ( isset( $posted_scheme[ 'subscription_trial_period' ] ) && in_array( $posted_scheme[ 'subscription_trial_period' ], $trial_periods ) ) {
			$posted_scheme[ 'subscription_trial_period' ] = trim( $posted_scheme[ 'subscription_trial_period' ] );
		}

		return $posted_scheme;
	} // END wcsatt_stt_processed_scheme_data()

} // END class

WCSATT_STT_Meta_Box_Product_Data::init();

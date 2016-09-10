<?php
/**
 * Filters the product meta data in the admin for subscription schemes.
 *
 * @class WCSATT_STT_Admin
 * @since 1.0.0
 */

class WCSATT_STT_Admin extends WCS_ATT_Admin {

	/**
	 * Initialize the product meta.
	 *
	 * @access public
	 * @static
	 */
	public static function init() {
		// Admin scripts and styles.
		add_action( 'admin_enqueue_scripts', __CLASS__ . '::admin_style' );

		// Adds to the default values for subscriptions schemes content.
		add_filter( 'wcsatt_default_subscription_scheme', __CLASS__ . '::subscription_schemes_content', 10, 1 );

		// Subscription scheme options displayed on the 'wcsatt_subscription_scheme_product_content' action.
		add_action( 'wcsatt_subscription_scheme_product_content', __CLASS__ . '::wcsatt_stt_fields', 15, 3 );

		// Filter the subscription scheme data to process the sign up and trial options on the ''wcsatt_subscription_scheme_process_scheme_data' filter.
		add_filter( 'wcsatt_subscription_scheme_process_scheme_data', __CLASS__ . '::wcsatt_stt_process_scheme_data', 10, 2 );
	}

	/**
	 * Load style.
	 *
	 * @return void
	 */
	public static function admin_style() {
		// Get admin screen id.
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		if ( in_array( $screen_id, array( 'edit-product', 'product' ) ) ) {
			wp_register_style( 'wcsatt_stt_writepanel', WCSATT_STT::plugin_url() . '/assets/css/wcsatt-stt-write-panel.css', array( 'woocommerce_admin_styles' ), WCSATT_STT::VERSION );
			wp_enqueue_style( 'wcsatt_stt_writepanel' );
		}

	} // END admin_style()

	/**
	 * Adds the default values for subscriptions schemes content.
	 *
	 * @access public
	 * @static
	 * @param  array $defaults
	 * @return void
	 */
	public static function subscription_schemes_content( $defaults ) {
		$new_defaults = array(
			'subscription_sign_up_fee'  => '',
			'subscription_trial_length' => '',
			'subscription_trial_period' => ''
		);

		return array_merge( $new_defaults, $defaults );
	} // END subscription_schemes_content()

	/**
	 * Adds the trial and sign up fields under the subscription section.
	 *
	 * @access public
	 * @static
	 * @param  int   $index
	 * @param  array $scheme_data
	 * @param  int   $post_id
	 * @return void
	 */
	public static function wcsatt_stt_fields( $index, $scheme_data, $post_id ) {
		if ( ! empty( $scheme_data ) ) {
			$subscription_sign_up_fee = ! empty( $scheme_data[ 'subscription_sign_up_fee' ] ) ? $scheme_data[ 'subscription_sign_up_fee' ] : '';
			$subscription_trial_length = isset( $scheme_data[ 'subscription_trial_length' ] ) ? $scheme_data[ 'subscription_trial_length' ] : 0;
			$subscription_trial_period = isset( $scheme_data[ 'subscription_trial_period' ] ) ? $scheme_data[ 'subscription_trial_period' ] : '';
		} else {
			$subscription_sign_up_fee = '';
			$subscription_trial_length = 0;
			$subscription_trial_period = '';
		}

		echo '<div class="options_group subscription_scheme_product_data sign_up_trial_scheme">';

		// Sign-up Fee
		woocommerce_wp_text_input( array(
			'id'          => '_subscription_sign_up_fee',
			'class'       => 'wc_input_subscription_intial_price',
			// translators: %s is a currency symbol / code
			'label'       => sprintf( __( 'Sign-up Fee (%s)', WCSATT_STT::TEXT_DOMAIN ), get_woocommerce_currency_symbol() ),
			'placeholder' => _x( 'e.g. 9.90', 'example price', WCSATT_STT::TEXT_DOMAIN ),
			'description' => __( 'Optionally include an amount to be charged at the outset of the subscription. The sign-up fee will be charged immediately, even if the product has a free trial or the payment dates are synced.', WCSATT_STT::TEXT_DOMAIN ),
			'desc_tip'    => true,
			'type'        => 'text',
			'custom_attributes' => array(
				'step' => 'any',
				'min'  => '0',
			),
			'name'        => 'wcsatt_schemes[' . $index . '][subscription_sign_up_fee]',
			'value'       => $subscription_sign_up_fee
		) );

		echo '<div class="subscription_trial">';

		// Trial Length
		woocommerce_wp_text_input( array(
			'id'          => '_subscription_trial_length',
			'class'       => 'wc_input_subscription_trial_length',
			'label'       => __( 'Free Trial', WCSATT_STT::TEXT_DOMAIN ),
			'name'        => 'wcsatt_schemes[' . $index . '][subscription_trial_length]',
			'value'       => $subscription_trial_length
		) );

		// Trial Period
		woocommerce_wp_select( array(
			'id'          => '_subscription_trial_period',
			'class'       => 'wc_input_subscription_trial_period',
			'label'       => __( 'Subscription Trial Period', WCSATT_STT::TEXT_DOMAIN ),
			'options'     => wcs_get_available_time_periods(),
			// translators: placeholder is trial period validation message if passed an invalid value (e.g. "Trial period can not exceed 4 weeks")
			'description' => sprintf( _x( 'An optional period of time to wait before charging the first recurring payment. Any sign up fee will still be charged at the outset of the subscription. %s', 'Trial period dropdown\'s description in pricing fields', WCSATT_STT::TEXT_DOMAIN ), WC_Subscriptions_Admin::get_trial_period_validation_message() ),
			'desc_tip'    => true,
			'value'       => WC_Subscriptions_Product::get_trial_period( $post_id ), // Explicitly set value in to ensure backward compatibility
			'name'        => 'wcsatt_schemes[' . $index . '][subscription_trial_period]',
			'value'       => $subscription_trial_period
	) );

		echo '</div>';

		echo '</div>';
	} // END wcsatt_stt_fields()

	/**
	 * Filters the subscription scheme data to pass the 
	 * sign up and trial options when saving.
	 *
	 * @access public
	 * @static
	 * @param  ini    $posted_scheme
	 * @param  string $product_type
	 * @return void
	 */
	public static function wcsatt_stt_process_scheme_data( $posted_scheme, $product_type ) {
		// Copy variable type fields.
		if ( 'variable' == $product_type ) {
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
		$trial_periods = apply_filters( 'wcsatt_stt_trial_periods', array( 'day', 'week', 'month', 'year' ) );
		if ( isset( $posted_scheme[ 'subscription_trial_period' ] ) && in_array( $posted_scheme[ 'subscription_trial_period' ], $trial_periods ) ) {
			$posted_scheme[ 'subscription_trial_period' ] = trim( $posted_scheme[ 'subscription_trial_period' ] );
		}

		return $posted_scheme;
	} // END wcsatt_stt_process_scheme_data()

}

WCSATT_STT_Admin::init();

<?php
/*
 * Plugin Name: WooCommerce Subscribe to All the Things - Sign up and Trial Addon
 * Plugin URI:  https://github.com/seb86/woocommerce-subscribe-to-all-the-things-signup-trial-add-on
 * Version:     1.0.0 Beta
 * Description: Add a sign up fee and free trial for each subscription scheme. Requires WooCommerce Subscribe to All the Things extension v1.1.1+.
 * Author:      Sebastien Dumont
 * Author URI:  https://sebastiendumont.com
 *
 * Text Domain: wc-satt-stt
 * Domain Path: /languages/
 *
 * Requires at least: 4.1
 * Tested up to: 4.5.3
 *
 * Copyright: © 2016 Sebastien Dumont
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */
if ( ! defined('ABSPATH') ) exit; // Exit if accessed directly.

if ( ! class_exists( 'WCSATT_STT' ) ) {
	class WCSATT_STT {

		/* Plugin version. */
		const VERSION = '1.0.0';

		/* Required WC version. */
		const REQ_WC_VERSION = '2.3.0';

		/* Required WCSATT version */
		const REQ_WCSATT_VERSION = '1.1.1';

		/* Text domain. */
		const TEXT_DOMAIN = 'wc-satt-stt';

		/**
		 * @var WCSATT_STT - the single instance of the class.
		 *
		 * @since 1.0.0
		 */
		protected static $_instance = null;

		/**
		 * Main WCSATT_STT Instance.
		 *
		 * Ensures only one instance of WCSATT_STT is loaded or can be loaded.
		 *
		 * @static
		 * @see WCSATT_STT()
		 * @return WCSATT_STT - Main instance
		 * @since 1.0.0
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Cloning is forbidden.
		 *
		 * @since 1.0.0
		 */
		public function __clone() {
			_doing_it_wrong( __FUNCTION__, __( 'Foul!' ), '1.0.0' );
		}

		/**
		 * Unserializing instances of this class is forbidden.
		 *
		 * @since 1.0.0
		 */
		public function __wakeup() {
			_doing_it_wrong( __FUNCTION__, __( 'Foul!' ), '1.0.0' );
		}

		/**
		 * Load the plugin.
		 */
		public function __construct() {
			add_action( 'plugins_loaded', array( $this, 'load_plugin' ) );
			add_action( 'init', array( $this, 'init_plugin' ) );
			add_action( 'admin_init', array( $this, 'admin_wcsatt_stt_product_meta' ) );
			add_filter( 'plugin_row_meta', array( $this, 'plugin_meta_links' ), 10, 4 );
		}

		public function plugin_path() {
			return untrailingslashit( plugin_dir_path( __FILE__ ) );
		} // END plugin_path()

		/*
		 * Check requirements on activation.
		 */
		public function load_plugin() {
			global $woocommerce;

			// Check that the required WooCommerce is running.
			if ( version_compare( $woocommerce->version, self::REQ_WC_VERSION, '<' ) ) {
				add_action( 'admin_notices', array( $this, 'wcsatt_stt_wc_admin_notice' ) );
				return false;
			}

			// Checks that WooCommerce Subscribe All the Things is running or is less than the required version.
			if ( ! class_exists( 'WCS_ATT' ) || version_compare( WCS_ATT::VERSION, self::REQ_WCSATT_VERSION, '<' ) ) {
				add_action( 'admin_notices', array( $this, 'wcsatt_stt_admin_notice' ) );
				return false;
			}
		} // END load_plugin()

		/**
		 * Display a warning message if minimum version of WooCommerce check fails.
		 *
		 * @return void
		 */
		public function wcsatt_stt_wc_admin_notice() {
			echo '<div class="error"><p>' . sprintf( __( '%1$s requires at least %2$s v%3$s in order to function. Please upgrade %2$s.', 'wc-satt-stt' ), 'Sign up and Trial Options Add-on for WCSATT', 'WooCommerce', self::REQ_WC_VERSION ) . '</p></div>';
		} // END wcsatt_stt_wc_admin_notice()

		/**
		 * Display a warning message if minimum version of WooCommerce Subscribe to All the Things check fails.
		 *
		 * @return void
		 */
		public function wcsatt_stt_admin_notice() {
			echo '<div class="error"><p>' . sprintf( __( '%1$s requires at least %2$s v%3$s in order to function. Please upgrade %2$s.', 'wc-satt-stt' ), 'Sign up and Trial Options Add-on', 'WooCommerce Subscribe to All the Things', self::REQ_WCSATT_VERSION ) . '</p></div>';
		} // END wcsatt_stt_admin_notice()

		/**
		 * Initialize the plugin if ready.
		 *
		 * @return void
		 */
		public function init_plugin() {
			// Load text domain.
			load_plugin_textdomain( 'wc-satt-stt', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

			// Adds the sign up fee and trial data to the price html on the 'wcsatt_overridden_subscription_prices_product' filter.
			add_filter( 'wcsatt_overridden_subscription_prices_product', array( $this, 'add_sub_scheme_data_price_html' ), 10, 3 );

			// Adds the extra subscription scheme data to the product object on the 'wcsatt_sub_product_scheme_option' filter.
			add_filter( 'wcsatt_sub_product_scheme_option', array( $this, 'sub_product_scheme_option' ), 10, 2 );

			// Filters the price string to include the sign up fee and/or trial to pass per scheme option on the 'wcsatt_get_single_product_price_string' filter.
			add_filter( 'wcsatt_get_single_product_price_string', array( $this, 'get_price_string' ), 10, 2 );

			// Filters the lowest price string to include the sign up fee on the 'wcsatt_get_single_product_lowest_price_string' filter.
			add_filter( 'wcsatt_get_single_product_lowest_price_string', array( $this, 'get_lowest_price_string' ), 10, 2 );

			// Filters the lowest price subscription scheme data on the 'wcsatt_get_lowest_price_sub_scheme_data' filter.
			add_filter( 'wcsatt_get_lowest_price_sub_scheme_data', array( $this, 'get_lowest_price_sub_scheme_data' ), 10, 2 );

			// Adds the sign-up and/or trial data to the subscription scheme prices on the 'wcsatt_subscription_scheme_prices' filter.
			add_filter( 'wcsatt_subscription_scheme_prices', array( $this, 'add_subscription_scheme_prices' ), 10, 2 );

			// Overrides the price of the subscription for sign up fee and/or trial on the 'wcsatt_cart_item' filter.
			add_filter( 'wcsatt_cart_item', array( $this, 'update_cart_item_sub_data' ), 10, 1 );
		} // END init_plugin()

		/**
		 * Register the product meta data fields.
		 *
		 * @return void
		 */
		public function admin_wcsatt_stt_product_meta() {
			// Subscription scheme options displayed on the 'wcsatt_subscription_scheme_product_content' action.
			add_action( 'wcsatt_subscription_scheme_product_content', array( $this, 'wcsatt_stt_fields' ), 15, 3 );

			// Filter the subscription scheme data to process the sign up and trial options on the ''wcsatt_subscription_scheme_process_scheme_data' filter.
			add_filter( 'wcsatt_subscription_scheme_process_scheme_data', array( $this, 'wcsatt_stt_process_scheme_data' ), 10, 2 );
		} // END admin_wcsatt_stt_product_meta()

		/**
		 * Show row meta on the plugin screen.
		 *
		 * @param mixed $links Plugin Row Meta
		 * @param mixed $file  Plugin Base file
		 * @return array
		 */
		public function plugin_meta_links( $links, $file, $data, $status ) {
			if ( $file == plugin_basename( __FILE__ ) ) {
				$author1 = '<a href="' . $data[ 'AuthorURI' ] . '">' . $data[ 'Author' ] . '</a>';
				$links[ 1 ] = sprintf( __( 'By %s', WCSATT_STT::TEXT_DOMAIN ), $author1 );
			}

			return $links;
		} // END plugin_meta_links()

		/**
		 * Adds the default values for subscriptions schemes content.
		 *
		 * @param  array $defaults
		 * @return void
		 */
		public static function add_default_subscription_schemes_content( $defaults ) {
			$new_defaults = array(
				'subscription_sign_up_fee'  => '',
				'subscription_trial_length' => '',
				'subscription_trial_period' => ''
			);

			return array_merge( $new_defaults, $defaults );
		} // END add_default_subscription_schemes_content()

		/**
		 * Adds the trial and sign up fields under the subscription section.
		 *
		 * @param  int   $index
		 * @param  array $scheme_data
		 * @param  int   $post_id
		 * @return void
		 */
		public function wcsatt_stt_fields( $index, $scheme_data, $post_id ) {
			if ( ! empty( $scheme_data ) ) {
				$subscription_sign_up_fee = ! empty( $scheme_data[ 'subscription_sign_up_fee' ] ) ? $scheme_data[ 'subscription_sign_up_fee' ] : '';
				$subscription_trial_length = isset( $scheme_data[ 'subscription_trial_length' ] ) ? $scheme_data[ 'subscription_trial_length' ] : 0;
				$subscription_trial_period = isset( $scheme_data[ 'subscription_trial_period' ] ) ? $scheme_data[ 'subscription_trial_period' ] : '';
			} else {
				$subscription_sign_up_fee = '';
				$subscription_trial_length = 0;
				$subscription_trial_period = '';
			}

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
		} // END wcsatt_stt_fields()

		/**
		 * Filters the subscription scheme data to pass the 
		 * sign up and trial options when saving.
		 *
		 * @param  ini $posted_scheme
		 * @param  string $product_type
		 * @return void
		 */
		public function wcsatt_stt_process_scheme_data( $posted_scheme, $product_type ) {
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

		/**
		 * Adds the additional subscription scheme data for products with attached subscription schemes.
		 *
		 * @param  object     $_product
		 * @param  array      $subscription_scheme
		 * @param  WC_Product $product
		 * @return string
		 */
		public function add_sub_scheme_data_price_html( $_product, $subscription_scheme, $product ) {
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
		 * @param  object $_cloned
		 * @param  array  $subscription_scheme
		 * @return object
		 */
		public function sub_product_scheme_option( $_cloned, $subscription_scheme ) {
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
		 * @param  array $prices
		 * @param  array $subscription_scheme
		 * @return array
		 */
		public function get_price_string( $prices, $subscription_scheme ) {
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
		 * @param  array $prices
		 * @param  array $lowest_subscription_scheme
		 * @return array
		 */
		public function get_lowest_price_string( $prices,  $lowest_subscription_scheme ) {
			if ( isset( $lowest_subscription_scheme[ 'sign_up_fee' ] ) && $lowest_subscription_scheme[ 'sign_up_fee' ] > 0 ) {
				$prices[ 'sign_up_fee' ] = $lowest_subscription_scheme[ 'sign_up_fee' ];
			}

			return $prices;
		} // END get_lowest_price_string()

		/**
		 * Adds the sign-up fee to the lowest subscription scheme option.
		 *
		 * @param array $data
		 * @param array $lowest_scheme
		 * @return array
		 */
		public function get_lowest_price_sub_scheme_data( $data, $lowest_scheme ) {
			if ( isset( $lowest_scheme['subscription_sign_up_fee'] ) && $lowest_scheme['subscription_sign_up_fee'] > 0 ) {
				$data['sign_up_fee'] = $lowest_scheme['subscription_sign_up_fee'];
			}

			return $data;
		} // END get_lowest_price_sub_scheme_data()

		/**
		 * Adds the sign-up and/or trial data to the subscription scheme prices.
		 *
		 * @param  array $prices
		 * @param  array $subscription_scheme
		 * @return array
		 */
		public function add_subscription_scheme_prices( $prices, $subscription_scheme ) {
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

		/**
		 * Updates the cart item data for a subscription product that
		 * has a sign-up fee and/or trial period applied.
		 *
		 * @param  array $cart_item
		 * @return array
		 */
		public function update_cart_item_sub_data( $cart_item ) {
			$active_scheme = WCS_ATT_Schemes::get_active_subscription_scheme( $cart_item );

			$subscription_prices = WCS_ATT_Scheme_Prices::get_active_subscription_scheme_prices( $cart_item, $active_scheme );

			if ( $active_scheme && $cart_item['data']->is_converted_to_sub == 'yes' ) {

				// Subscription Price
				$price = $cart_item['data']->subscription_price;

				// Is there a sign up fee?
				$sign_up_fee = isset( $subscription_prices['sign_up_fee'] ) ? $subscription_prices['sign_up_fee'] : '';

				// Put them both together.
				$new_price = $price + $sign_up_fee;

				if ( $sign_up_fee > 0 ) {
					$cart_item['data']->initial_amount = $new_price;
					$cart_item['data']->subscription_sign_up_fee = $sign_up_fee;
				}

				$trial_length = isset( $subscription_prices['trial_length'] ) ? $subscription_prices['trial_length'] : 0;
				$trial_period = isset( $subscription_prices['trial_period'] ) ? $subscription_prices['trial_period'] : '';

				// If a trial length is more than zero then re-adjust the price.
				if ( $trial_length > 0 ) {

					/*$cart_item['data']->price = $new_price;
					$cart_item['data']->subscription_price = $new_price;
					$cart_item['data']->sale_price = $new_price;
					$cart_item['data']->regular_price = $new_price;*/

					$cart_item['data']->subscription_trial_length = $trial_length;
					$cart_item['data']->subscription_trial_period = $trial_period;
				} else {
					$cart_item['data']->subscription_trial_length = 0;
					$cart_item['data']->subscription_trial_period = '';
				}

			}

			return $cart_item;
		} // END update_cart_item_sub_data()

	} // END class

} // END if class exists

return WCSATT_STT::instance();
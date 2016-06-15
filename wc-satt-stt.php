<?php
/*
 * Plugin Name: WooCommerce Subscribe to All the Things - Sign up and Trial Addon
 * Plugin URI:  https://sebastiendumont.com
 * Version:     1.0.0 Alpha
 * Description: Add a sign up fee and free trial for each subscription scheme. Requires WooCommerce Subscribe to All the Things extension v1.1.1+.
 * Author:      Sebastien Dumont
 * Author URI:  https://sebastiendumont.com
 *
 * Text Domain: wc-satt-stt
 * Domain Path: /languages/
 *
 * Requires at least: 4.1
 * Tested up to: 4.5.2
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
			echo '<div class="error"><p>' . sprintf( __( '%1$s requires at least %2$s v%3$s in order to function. Please upgrade %2$s.', 'wc-satt-stt' ), 'Sign up and Trial Options for WCSATT', 'WooCommerce', self::REQ_WC_VERSION ) . '</p></div>';
		} // END wcsatt_stt_wc_admin_notice()

		/**
		 * Display a warning message if minimum version of WooCommerce Subscribe to All the Things check fails.
		 *
		 * @return void
		 */
		public function wcsatt_stt_admin_notice() {
			echo '<div class="error"><p>' . sprintf( __( '%1$s requires at least %2$s v%3$s in order to function. Please upgrade %2$s.', 'wc-satt-stt' ), 'Sign up and Trial Options Addon', 'WooCommerce Subscribe to All the Things', self::REQ_WCSATT_VERSION ) . '</p></div>';
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

			// Filters the suffix price html on the 'wcsatt_suffix_price_html' filter.
			add_filter( 'wcsatt_suffix_price_html', array( $this, 'filter_suffix_price_html' ), 10, 1 );

			// Overrides the price of the subscription for sign up fee and/or trial on the 'woocommerce_add_cart_item' filter.
			add_filter( 'woocommerce_add_cart_item', array( $this, 'add_cart_item' ), 15, 1 );
		} // END init_plugin()

		/**
		 * Register the product meta data fields.
		 *
		 * @return void
		 */
		public function admin_wcsatt_stt_product_meta() {
			// Subscription scheme options displayed on the 'wcsatt_subscription_scheme_content' action.
			//add_action( 'wcsatt_subscription_scheme_content', array( $this, 'wcsatt_stt_fields' ), 15, 3 );

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
		 * Subscriptions schemes admin metaboxes.
		 *
		 * @param  array $defaults
		 * @return void
		 */
		public static function add_default_subscription_schemes_content( $defaults ) {
			$new_defaults = array(
				'subscription_sign_up_fee' => '',
				'subscription_trial_length' => 0,
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
		}

		/**
		 * Filter the suffix price string.
		 *
		 * @param object     $_product
		 * @param array      $subscription_scheme
		 * @param WC_Product $product
		 * @return string
		 */
		public function filter_suffix_price_html( $_product, $subscription_scheme, $product ) {
			$subscription_string = '';

			if ( isset( $_product->subscription_trial_length ) && 0 != $_product->subscription_trial_length ) {
				$trial_string = wcs_get_subscription_trial_period_strings( $_product->subscription_trial_length, $_product->subscription_trial_period );
				// translators: 1$: subscription string (e.g. "$15 on March 15th every 3 years for 6 years"), 2$: trial length (e.g.: "with 4 months free trial")
				$subscription_string = sprintf( __( '%1$s with %2$s free trial', WCSATT_STT::TEXT_DOMAIN ), $subscription_string, $trial_string );
			}

			$sign_up_fee = $_product->subscription_sign_up_fee;

			if ( is_numeric( $sign_up_fee ) ) {
				$sign_up_fee = wc_price( $sign_up_fee );
			}

			if ( isset( $_product->subscription_sign_up_fee ) && $_product->subscription_sign_up_fee > 0 ) {
				// translators: 1$: subscription string (e.g. "$15 on March 15th every 3 years for 6 years with 2 months free trial"), 2$: signup fee price (e.g. "and a $30 sign-up fee")
				$subscription_string = sprintf( __( '%1$s and a %2$s sign-up fee', WCSATT_STT::TEXT_DOMAIN ), $subscription_string, $sign_up_fee );
			}

			return $subscription_string;
		}

		/**
		 * Converts a cart item if it's a subscription with 
		 * a trial subscription or/and has a sign-up fee.
		 *
		 * @param  array $cart_item
		 * @return array
		 */
		public function add_cart_item( $cart_item ) {
			$active_scheme = WCS_ATT_Schemes::get_active_subscription_scheme( $cart_item );

			if ( $active_scheme && $cart_item['data']->is_converted_to_sub == 'yes' ) {

				$sign_up_fee  = $this->get_item_signup_fee( $cart_item[ 'product_id' ], $active_scheme );
				$trial_length = $this->get_item_trial_length( $cart_item[ 'product_id' ], $active_scheme );
				$trial_period = $this->get_item_trial_period( $cart_item[ 'product_id' ], $active_scheme );

				// Subscription Price
				$price = $cart_item['data']->subscription_price;

				// Is there a sign up fee?
				$sign_up_fee = ! empty( $sign_up_fee ) ? $sign_up_fee : '';

				// If a trial length is more than zero then re-adjust the price.
				if ( $trial_length > 0 ) {
					$cart_item['data']->price = $sign_up_fee;
					$cart_item['data']->subscription_price = $sign_up_fee;
					$cart_item['data']->sale_price = $sign_up_fee;
					$cart_item['data']->regular_price = $sign_up_fee;
					$cart_item['data']->initial_amount = $sign_up_fee;
					$cart_item['data']->subscription_sign_up_fee = $sign_up_fee;
					$cart_item['data']->subscription_trial_length = $trial_length;
					$cart_item['data']->subscription_trial_period = $trial_period;
				} else {
					$cart_item['data']->price = $price + $sign_up_fee;
					$cart_item['data']->subscription_price = $price + $sign_up_fee;
					$cart_item['data']->sale_price = $price + $sign_up_fee;
					$cart_item['data']->regular_price = $price + $sign_up_fee;
					$cart_item['data']->initial_amount = $price + $sign_up_fee;
					$cart_item['data']->subscription_sign_up_fee = $sign_up_fee;
					$cart_item['data']->subscription_trial_length = 0;
					$cart_item['data']->subscription_trial_period = '';
				}

			}

			return $cart_item;
		} // END add_cart_item()

		/**
		 * Get the item signup fee from the subscription scheme.
		 *
		 * @param int  $product_id
		 * @param int  $scheme_id
		 * @return int
		 */
		public function get_item_signup_fee( $product_id, $scheme_id ) {
			$product_schemes = get_post_meta( $product_id, '_wcsatt_schemes', true );
			$thescheme = $product_schemes[$scheme_id];

			return $thescheme['subscription_sign_up_fee'];
		} // END get_item_signup_fee()

		/**
		 * Get the item trial length from the subscription scheme.
		 *
		 * @param int  $product_id
		 * @param int  $scheme_id
		 * @return int
		 */
		public function get_item_trial_length( $product_id, $scheme_id ) {
			$product_schemes = get_post_meta( $product_id, '_wcsatt_schemes', true );
			$thescheme = $product_schemes[$scheme_id];

			return $thescheme['subscription_trial_length'];
		} // END get_item_trial_length()

		/**
		 * Get the item trial period from the subscription scheme.
		 *
		 * @param  int    $product_id
		 * @param  int    $scheme_id
		 * @return string
		 */
		public function get_item_trial_period( $product_id, $scheme_id ) {
			$product_schemes = get_post_meta( $product_id, '_wcsatt_schemes', true );
			$thescheme = $product_schemes[$scheme_id];

			return $thescheme['subscription_trial_period'];
		} // END get_item_trial_period()

	} // END class

} // END if class exists

return WCSATT_STT::instance();
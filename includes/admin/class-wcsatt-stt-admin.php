<?php
/**
 * Set default subscription scheme.
 *
 * @author   Sébastien Dumont
 * @category Admin
 * @package  WooCommerce Subscribe All the Things: Sign-up and Free Trial Add-on
 * @class    WCSATT_STT_Admin
 * @since    1.0.0
 * @version  2.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WCSATT_STT_Admin {

	/**
	 * Initialize the admin.
	 *
	 * @access public
	 * @static
	 */
	public static function init() {
		// Adds to the default values for subscriptions schemes content on the 'wcsatt_default_subscription_scheme' filter.
		add_filter( 'wcsatt_default_subscription_scheme', array( __CLASS__, 'wcsatt_default_subscription_scheme' ), 10, 1 );

		/**
		 * Single-Product settings.
		 */

		// Admin scripts and styles.
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_style' ) );
	}

	/**
	 * Adds the default values for subscriptions schemes content.
	 *
	 * @access public
	 * @static
	 * @param  array $defaults
	 * @return void
	 */
	public static function wcsatt_default_subscription_scheme( $defaults ) {
		$new_defaults = array(
			'subscription_sign_up_fee'  => '',
			'subscription_trial_length' => '',
			'subscription_trial_period' => ''
		);

		return array_merge( $new_defaults, $defaults );
	} // END wcsatt_default_subscription_scheme()

	/**
	 * Load style.
	 *
	 * @access public
	 * @static
	 * @return void
	 */
	public static function admin_style() {
		// Get admin screen id.
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		if ( in_array( $screen_id, array( 'edit-product', 'product' ) ) ) {
			wp_register_style( 'wcsatt_stt_writepanel', WCSATT_STT::plugin_url() . '/assets/css/wcsatt-stt-write-panel.css', array( 'woocommerce_admin_styles' ), WCSATT_STT::$version );
			wp_enqueue_style( 'wcsatt_stt_writepanel' );
		}
	} // END admin_style()

} // END class

WCSATT_STT_Admin::init();

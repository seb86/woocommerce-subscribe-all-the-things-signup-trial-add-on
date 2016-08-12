<?php
/*
 * Plugin Name: WooCommerce Subscribe All the Things: Sign-up and Trial Add-on
 * Plugin URI:  https://github.com/seb86/woocommerce-subscribe-to-all-the-things-signup-trial-add-on
 * Version:     1.0.0
 * Description: Add a sign up fee and free trial for each subscription scheme. Requires WooCommerce Subscribe All the Things extension v1.1.0+.
 * Author:      Sébastien Dumont
 * Author URI:  https://sebastiendumont.com
 *
 * Text Domain: wc-satt-stt
 * Domain Path: /languages/
 *
 * Requires at least: 4.1
 * Tested up to: 4.5.3
 *
 * Copyright: © 2016 Sébastien Dumont
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
		const REQ_WCSATT_VERSION = '1.1.0';

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
			add_filter( 'plugin_row_meta', array( $this, 'plugin_meta_links' ), 10, 4 );
		}

		public function plugin_path() {
			return untrailingslashit( plugin_dir_path( __FILE__ ) );
		} // END plugin_path()

		/*
		 * Check requirements on activation.
		 *
		 * @global $woocommerce
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

			require_once( 'includes/class-wcsatt-stt-cart.php' );
			require_once( 'includes/class-wcsatt-stt-display.php' );

			// Admin includes
			if ( is_admin() ) {
				require_once( 'includes/admin/class-wcsatt-stt-admin.php' );
			}

		} // END load_plugin()

		/**
		 * Display a warning message if minimum version of WooCommerce check fails.
		 *
		 * @return void
		 */
		public function wcsatt_stt_wc_admin_notice() {
			echo '<div class="error"><p>' . sprintf( __( '%1$s requires at least %2$s v%3$s in order to function. Please upgrade %2$s.', 'wc-satt-stt' ), 'WooCommerce Subscribe All the Things: Sign-up and Trial Add-on', 'WooCommerce', self::REQ_WC_VERSION ) . '</p></div>';
		} // END wcsatt_stt_wc_admin_notice()

		/**
		 * Display a warning message if minimum version of WooCommerce Subscribe All the Things check fails.
		 *
		 * @return void
		 */
		public function wcsatt_stt_admin_notice() {
			echo '<div class="error"><p>' . sprintf( __( '%1$s requires at least %2$s v%3$s in order to function. Please upgrade %2$s.', 'wc-satt-stt' ), 'WooCommerce Subscribe All the Things: Sign-up and Trial Add-on', 'WooCommerce Subscribe All the Things', self::REQ_WCSATT_VERSION ) . '</p></div>';
		} // END wcsatt_stt_admin_notice()

		/**
		 * Initialize the plugin if ready.
		 *
		 * @return void
		 */
		public function init_plugin() {
			// Load text domain.
			load_plugin_textdomain( 'wc-satt-stt', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		} // END init_plugin()

		/**
		 * Show row meta on the plugin screen.
		 *
		 * @param  mixed $links Plugin Row Meta
		 * @param  mixed $file  Plugin Base file
		 * @param  array $data  Plugin Data
		 * @return array
		 */
		public function plugin_meta_links( $links, $file, $data, $status ) {
			if ( $file == plugin_basename( __FILE__ ) ) {
				$author1 = '<a href="' . $data[ 'AuthorURI' ] . '">' . $data[ 'Author' ] . '</a>';
				$author2 = '<a href="http://www.subscriptiongroup.co.uk/">Subscription Group Limited</a>';
				$links[ 1 ] = sprintf( __( 'By %s', self::TEXT_DOMAIN ), sprintf( __( '%s and %s', self::TEXT_DOMAIN ), $author1, $author2 ) );
			}

			return $links;
		} // END plugin_meta_links()

	} // END class

} // END if class exists

return WCSATT_STT::instance();
<?php
/**
 * Plugin Name:     Easy Digital Downloads - Simplify Commerce Gateway
 * Plugin URI:      https://easydigitaldownloads.com/extensions/simplify-commerce-gateway
 * Description:     Adds a payment gateway for Simplify Commerce to Easy Digital Downloads
 * Version:         1.0.2
 * Author:          Daniel J Griffiths
 * Author URI:      http://section214.com
 * Text Domain:     edd-simplify-commerce
 *
 * @package         EDD\Gateway\SimplifyCommerce
 * @author          Daniel J Griffiths <dgriffiths@section214.com>
 * @copyright       Copyright (c) 2014, Daniel J Griffiths
 */


// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;


if( !class_exists( 'EDD_Simplify_Commerce' ) ) {


    /**
     * Main EDD_Simplify_Commerce class
     *
     * @since       1.0.0
     */
    class EDD_Simplify_Commerce {

        /**
         * @var         EDD_Simplify_Commerce $instance The one true EDD_Simplify_Commerce
         * @since       1.0.0
         */
        private static $instance;


        /**
         * Get active instance
         *
         * @access      public
         * @since       1.0.0
         * @return      self::$instance The one true EDD_Simplify_Commerce
         */
        public static function instance() {
            if( !self::$instance ) {
                self::$instance = new EDD_Simplify_Commerce();
                self::$instance->setup_constants();
                self::$instance->includes();
                self::$instance->load_textdomain();
                self::$instance->hooks();
            }

            return self::$instance;
        }


        /**
         * Setup plugin constants
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         */
        private function setup_constants() {
            // Plugin version
            define( 'EDD_SIMPLIFY_COMMERCE_VERSION', '1.0.2' );

            // Plugin path
            define( 'EDD_SIMPLIFY_COMMERCE_DIR', plugin_dir_path( __FILE__ ) );

            // Plugin URL
            define( 'EDD_SIMPLIFY_COMMERCE_URL', plugin_dir_url( __FILE__ ) );
        }


        /**
         * Include necessary files
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         */
		private function includes() {
			// Include the Simplify Commerce library
			require_once( EDD_SIMPLIFY_COMMERCE_DIR . '/includes/libraries/Simplify/Simplify.php' );
        }


        /**
         * Run action and filter hooks
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         */
        private function hooks() {
            // Handle licensing
            if( class_exists( 'EDD_License' ) ) {
                $license = new EDD_License( __FILE__, 'Simplify Commerce Gateway', EDD_SIMPLIFY_COMMERCE_VERSION, 'Daniel J Griffiths' );
            }

			// Register settings
			add_filter( 'edd_settings_gateways', array( $this, 'settings' ), 1 );

			// Add the gateway
			add_filter( 'edd_payment_gateways', array( $this, 'register_gateway' ) );

			// Process payment
			add_action( 'edd_gateway_simplify', array( $this, 'process_payment' ) );

			// Display errors
			add_action( 'edd_after_cc_fields', array( $this, 'errors_div' ), 999 );
        }


        /**
         * Internationalization
         *
         * @access      public
         * @since       1.0.0
         * @return      void
         */
        public function load_textdomain() {
            // Set filter for language directory
            $lang_dir = dirname( plugin_basename( __FILE__ ) ) . '/languages/';
            $lang_dir = apply_filters( 'edd_simplify_commerce_language_directory', $lang_dir );

            // Traditional WordPress plugin locale filter
            $locale     = apply_filters( 'plugin_locale', get_locale(), '' );
            $mofile     = sprintf( '%1$s-%2$s.mo', 'edd-simplify-commerce', $locale );

            // Setup paths to current locale file
            $mofile_local   = $lang_dir . $mofile;
            $mofile_global  = WP_LANG_DIR . '/edd-simplify-commerce/' . $mofile;

            if( file_exists( $mofile_global ) ) {
                // Look in global /wp-content/languages/edd-simplify-commerce/ folder
                load_textdomain( 'edd-simplify-commerce', $mofile_global );
            } elseif( file_exists( $mofile_local ) ) {
                // Look in local /wp-content/plugins/edd-simplify-commerce/languages/ folder
                load_textdomain( 'edd-simplify-commerce', $mofile_local );
            } else {
                // Load the default language files
                load_plugin_textdomain( 'edd-simplify-commerce', false, $lang_dir );
            }
        }


		/**
		 * Register settings
		 *
		 * @since		1.0.0
		 * @access		public
		 * @param		array $settings The existing plugin settings
		 * @param		array The modified plugin settings array
		 */
		public function settings( $settings ) {
			$new_settings = array(
				array(
					'id'	=> 'edd_simplify_commerce_settings',
					'name'	=> '<strong>' . __( 'Simplify Commerce Settings', 'edd-simplify-commerce' ) . '</strong>',
					'desc'	=> __( 'Configure your Simplify Commerce settings', 'edd-simplify-commerce' ),
					'type'	=> 'header'

				),
				array(
					'id'	=> 'edd_simplify_commerce_public_key',
					'name'	=> __( 'API Public Key', 'edd-simplify-commerce' ),
					'desc'	=> __( 'Enter your Simplify Commerce API Public Key (found <a href="https://www.simplify.com/commerce/app#/account/apiKeys" target="_blank">here</a>)', 'edd-simplify-gateway' ),
					'type'	=> 'text'
				),
				array(
					'id'	=> 'edd_simplify_commerce_private_key',
					'name'	=> __( 'API Private Key', 'edd-simplify-commerce' ),
					'desc'	=> __( 'Enter your Simplify Commerce API Private Key (found <a href="https://www.simplify.com/commerce/app#/account/apiKeys" target="_blank">here</a>)', 'edd-simplify-gateway' ),
					'type'	=> 'text'
				)
			);

			return array_merge( $settings, $new_settings );
		}


		/**
		 * Register our new gateway
		 *
		 * @since		1.0.0
		 * @access		public
		 * @param		array $gateways The current gateway list
		 * @return		array $gateways The updated gateway list
		 */
		public function register_gateway( $gateways ) {
			$gateways['simplify'] = array(
				'admin_label'		=> 'Simplify Commerce',
				'checkout_label'	=> __( 'Credit Card', 'edd-simplify-commerce' )
			);

			return $gateways;
		}


		/**
		 * Process payment submission
		 *
		 * @since		1.0.0
		 * @access		public
		 * @param		array $purchase_data The data for a specific purchase
		 * @return		void
		 */
		public function process_payment( $purchase_data ) {
			$errors = edd_get_errors();

			if( !$errors ) {
				Simplify::$publicKey = trim( edd_get_option( 'edd_simplify_commerce_public_key', '' ) );
				Simplify::$privateKey = trim( edd_get_option( 'edd_simplify_commerce_private_key', '' ) );

				try{
					$amount = number_format( $purchase_data['price'] * 100, 0 );

					$result = Simplify_Payment::createPayment( array(
						'card'			=> array(
							'number'		=> str_replace( ' ', '', $purchase_data['card_info']['card_number'] ),
							'expMonth'		=> $purchase_data['card_info']['card_exp_month'],
							'expYear'		=> substr( $purchase_data['card_info']['card_exp_year'], -2 ),
							'cvc'			=> $purchase_data['card_info']['card_cvc'],
							'addressLine1'	=> ( isset( $purchase_data['card_info']['card_address'] ) ? $purchase_data['card_info']['card_address'] : '' ),
							'addressLine2'	=> ( isset( $purchase_data['card_info']['card_address_2'] ) ? $purchase_data['card_info']['card_address_2'] : '' ),
							'addressCity'	=> ( isset( $purchase_data['card_info']['card_city'] ) ? $purchase_data['card_info']['card_city'] : '' ),
							'addressState'	=> ( isset( $purchase_data['card_info']['card_state'] ) ? $purchase_data['card_info']['card_state'] : '' ),
							'addressZip'	=> ( isset( $purchase_data['card_info']['card_zip'] ) ? $purchase_data['card_info']['card_zip'] : '' ),
							'name'			=> ( isset( $purchase_data['card_info']['card_name'] ) ? $purchase_data['card_info']['card_name'] : '' ),
						),
						'amount'		=> edd_sanitize_amount( $amount ),
						'currency'		=> edd_get_option( 'currency', 'USD' )
					) );
				} catch( Exception $e ) {
					edd_record_gateway_error( __( 'Simplify Commerce Error', 'edd-simplify-commerce' ), print_r( $e, true ), 0 );
					edd_set_error( 'card_declined', __( 'Your card was declined!', 'edd-balanced-gateway' ) );
					edd_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['edd-gateway'] );
				}

				if( $result->paymentStatus == 'APPROVED' ) {
					$payment_data = array(
						'price'			=> $purchase_data['price'],
						'date'			=> $purchase_data['date'],
						'user_email'	=> $purchase_data['user_email'],
						'purchase_key'	=> $purchase_data['purchase_key'],
						'currency'		=> edd_get_option( 'currency', 'USD' ),
						'downloads'		=> $purchase_data['downloads'],
						'cart_details'	=> $purchase_data['cart_details'],
						'user_info'		=> $purchase_data['user_info'],
						'status'		=> 'pending'
					);

					$payment = edd_insert_payment( $payment_data );

					if( $payment ) {
						edd_insert_payment_note( $payment, sprintf( __( 'Simplify Commerce Transaction ID: %s', 'edd-simplify-commerce' ), $result->id ) );
						edd_update_payment_status( $payment, 'publish' );
						edd_send_to_success_page();
					} else {
						edd_set_error( 'authorize_error', __( 'Error: Your payment could not be recorded. Please try again.', 'edd-simplify-commerce' ) );
						edd_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['edd-gateway'] );
					}
				} else {
					wp_die( $result->paymentStatus );
				}
			} else {
				edd_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['edd-gateway'] );
			}
		}


		/**
		 * Output form errors
		 *
		 * @since		1.0.0
		 * @access		public
		 * @return		void
		 */
		public function errors_div() {
			echo '<div id="edd-simplify-errors"></div>';
		}
    }
}


/**
 * The main function responsible for returning the one true EDD_Simplify_Commerce
 * instance to functions everywhere
 *
 * @since       1.0.0
 * @return      EDD_Simplify_Commerce The one true EDD_Simplify_Commerce
 */
function EDD_Simplify_Commerce_load() {
	if( !class_exists( 'Easy_Digital_Downloads' ) ) {
        if( !class_exists( 'S214_EDD_Activation' ) ) {
            require_once( 'includes/class.s214-edd-activation.php' );
        }

        $activation = new S214_EDD_Activation( plugin_dir_path( __FILE__ ), basename( __FILE__ ) );
        $activation = $activation->run();
	    return EDD_Simplify_Commerce::instance();
	} else {
	    return EDD_Simplify_Commerce::instance();
	}
}
add_action( 'plugins_loaded', 'EDD_Simplify_Commerce_load' );

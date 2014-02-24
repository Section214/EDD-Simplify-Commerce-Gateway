<?php
/**
 * Plugin Name:     Easy Digital Downloads - Adyen Gateway
 * Plugin URI:      https://easydigitaldownloads.com/extensions/adyen-gateway
 * Description:     Adds a payment gateway for Adyen to Easy Digital Downloads
 * Version:         1.0.0
 * Author:          Daniel J Griffiths
 * Author URI:      http://section214.com
 * Text Domain:     edd-adyen-gateway
 *
 * @package         EDD\Gateway\Adyen
 * @author          Daniel J Griffiths <dgriffiths@section214.com>
 * @copyright       Copyright (c) 2014, Daniel J Griffiths
 */


// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;


if( !class_exists( 'EDD_Adyen_Gateway' ) ) {


    /**
     * Main EDD_Adyen_Gateway class
     *
     * @since       1.0.0
     */
    class EDD_Adyen_Gateway {

        /**
         * @var         EDD_Adyen_Gateway $instance The one true EDD_Adyen_Gateway
         * @since       1.0.0
         */
        private static $instance;


        /**
         * Get active instance
         *
         * @access      public
         * @since       1.0.0
         * @return      self::$instance The one true EDD_Adyen_Gateway
         */
        public static function instance() {
            if( !self::$instance ) {
                self::$instance = new EDD_Adyen_Gateway();
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
            define( 'EDD_ADYEN_GATEWAY_VERSION', '1.0.0' );

            // Plugin path
            define( 'EDD_ADYEN_GATEWAY_DIR', plugin_dir_path( __FILE__ ) );

            // Plugin URL
            define( 'EDD_ADYEN_GATEWAY_URL', plugin_dir_url( __FILE__ ) );
        }


        /**
         * Include necessary files
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         */
		private function includes() {
			// Load our custom updater
			if( !class_exists( 'EDD_LICENSE' ) ) {
				include( EDD_SIMPLIFY_COMMERCE_DIR . '/includes/libraries/EDD_SL/EDD_License_Handler.php' );
			}
        }


        /**
         * Run action and filter hooks
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         */
        private function hooks() {
            // Edit plugin metalinks
			add_filter( 'plugin_row_meta', array( $this, 'plugin_metalinks' ), null, 2 );

			// Handle licensing
			$license = new EDD_License( __FILE__, 'Adyen Gateway', EDD_ADYEN_GATEWAY_VERSION, 'Daniel J Griffiths' );

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
            $lang_dir = apply_filters( 'EDD_Adyen_Gateway_language_directory', $lang_dir );

            // Traditional WordPress plugin locale filter
            $locale     = apply_filters( 'plugin_locale', get_locale(), '' );
            $mofile     = sprintf( '%1$s-%2$s.mo', 'edd-adyen-gateway', $locale );

            // Setup paths to current locale file
            $mofile_local   = $lang_dir . $mofile;
            $mofile_global  = WP_LANG_DIR . '/edd-adyen-gateway/' . $mofile;

            if( file_exists( $mofile_global ) ) {
                // Look in global /wp-content/languages/edd-adyen-gateway/ folder
                load_textdomain( 'edd-adyen-gateway', $mofile_global );
            } elseif( file_exists( $mofile_local ) ) {
                // Look in local /wp-content/plugins/edd-adyen-gateway/languages/ folder
                load_textdomain( 'edd-adyen-gateway', $mofile_local );
            } else {
                // Load the default language files
                load_plugin_textdomain( 'edd-adyen-gateway', false, $lang_dir );
            }
        }


        /**
         * Modify plugin metalinks
         *
         * @access      public
         * @since       1.0.0
         * @param       array $links The current links array
         * @param       string $file A specific plugin table entry
         * @return      array $links The modified links array
         */
        public function plugin_metalinks( $links, $file ) {
            if( $file == plugin_basename( __FILE__ ) ) {
                $help_link = array(
                    '<a href="http://support.ghost1227.com/forums/forum/plugin-support/edd-adyen-gateway/" target="_blank">' . __( 'Support Forum', 'edd-adyen-gateway' ) . '</a>'
                );

                $docs_link = array(
                    '<a href="http://support.ghost1227.com/section/edd-adyen-gateway/" target="_blank">' . __( 'Docs', 'edd-adyen-gateway' ) . '</a>'
                );

                $links = array_merge( $links, $help_link, $docs_link );
            }

            return $links;
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
					'id'	=> 'edd_adyen_gateway_settings',
					'name'	=> '<strong>' . __( 'Adyen Gateway Settings', 'edd-adyen-gateway' ) . '</strong>',
					'desc'	=> __( 'Configure your Adyen Gateway settings', 'edd-adyen-gateway' ),
					'type'	=> 'header'

				),
				array(
					'id'	=> 'edd_adyen_gateway_public_key',
					'name'	=> __( 'API Public Key', 'edd-adyen-gateway' ),
					'desc'	=> __( 'Enter your Adyen Gateway API Public Key (found <a href="https://www.simplify.com/commerce/app#/account/apiKeys" target="_blank">here</a>)', 'edd-adyen-gateway' ),
					'type'	=> 'text'
				),
				array(
					'id'	=> 'edd_adyen_gateway_private_key',
					'name'	=> __( 'API Private Key', 'edd-adyen-gateway' ),
					'desc'	=> __( 'Enter your Adyen Gateway API Private Key (found <a href="https://www.simplify.com/commerce/app#/account/apiKeys" target="_blank">here</a>)', 'edd-adyen-gateway' ),
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
			$gateways['adyen'] = array(
				'admin_label'		=> 'Adyen',
				'checkout_label'	=> __( 'Credit Card', 'edd-adyen-gateway' )
			);

			return $gateways;
		}


		/**
		 * Process payment submission
		 *
		 * @since		1.0.0
		 * @access		public
		 * @param		array $purchase_data The data for a specific purchase
		 * @global		array $edd_options The EDD options array
		 * @return		void
		 */
		public function process_payment( $purchase_data ) {
			global $edd_options;

			$errors = edd_get_errors();

			if( !$errors ) {

				try{
					$amount = number_format( $purchase_data['price'] * 100, 0 );

					$result = Simplify_Payment::createPayment( array(
						'card'			=> array(
							'number'		=> $purchase_data['card_info']['card_number'],
							'expMonth'		=> $purchase_data['card_info']['card_exp_month'],
							'expYear'		=> date( 'y', $purchase_data['card_info']['card_exp_year'] ),
							'cvc'			=> $purchase_data['card_info']['card_cvc'],
							'addressLine1'	=> ( isset( $purchase_data['card_info']['card_address'] ) ? $purchase_data['card_info']['card_address'] : '' ),
							'addressLine2'	=> ( isset( $purchase_data['card_info']['card_address_2'] ) ? $purchase_data['card_info']['card_address_2'] : '' ),
							'addressCity'	=> ( isset( $purchase_data['card_info']['card_city'] ) ? $purchase_data['card_info']['card_city'] : '' ),
							'addressState'	=> ( isset( $purchase_data['card_info']['card_state'] ) ? $purchase_data['card_info']['card_state'] : '' ),
							'addressZip'	=> ( isset( $purchase_data['card_info']['card_zip'] ) ? $purchase_data['card_info']['card_zip'] : '' ),
							'name'			=> ( isset( $purchase_data['card_info']['card_name'] ) ? $purchase_data['card_info']['card_name'] : '' ),
						),
						'amount'		=> edd_sanitize_amount( $amount ),
						'currency'		=> $edd_options['currency']
					) );
				} catch( Exception $e ) {
					edd_record_gateway_error( __( 'Adyen Gateway Error', 'edd-adyen-gateway' ), print_r( $e, true ), 0 );
					edd_set_error( 'card_declined', __( 'Your card was declined!', 'edd-adyen-gateway' ) );
					edd_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['edd-gateway'] );
				}

				if( $result->paymentStatus == 'APPROVED' ) {
					$payment_data = array(
						'price'			=> $purchase_data['price'],
						'date'			=> $purchase_data['date'],
						'user_email'	=> $purchase_data['user_email'],
						'purchase_key'	=> $purchase_data['purchase_key'],
						'currency'		=> $edd_options['currency'],
						'downloads'		=> $purchase_data['downloads'],
						'cart_details'	=> $purchase_data['cart_details'],
						'user_info'		=> $purchase_data['user_info'],
						'status'		=> 'pending'
					);

					$payment = edd_insert_payment( $payment_data );

					if( $payment ) {
						edd_insert_payment_note( $payment, sprintf( __( 'Adyen Gateway Transaction ID: %s', 'edd-adyen-gateway' ), $result->id ) );
						edd_update_payment_status( $payment, 'publish' );
						edd_send_to_success_page();
					} else {
						edd_set_error( 'authorize_error', __( 'Error: Your payment could not be recorded. Please try again.', 'edd-adyen-gateway' ) );
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
			echo '<div id="edd-adyen-errors"></div>';
		}
    }
}


/**
 * The main function responsible for returning the one true EDD_Adyen_Gateway
 * instance to functions everywhere
 *
 * @since       1.0.0
 * @return      EDD_Adyen_Gateway The one true EDD_Adyen_Gateway
 */
function EDD_Adyen_Gateway_load() {
	if( !class_exists( 'Easy_Digital_Downloads' ) ) {
        if( !class_exists( 'S214_EDD_Activation' ) ) {
            require_once( 'includes/class.s214-edd-activation.php' );
        }

        $activation = new S214_EDD_Activation( plugin_dir_path( __FILE__ ), basename( __FILE__ ) );
        $activation = $activation->run();
	} else {
	    return EDD_Adyen_Gateway::instance();
	}
}
add_action( 'plugins_loaded', 'EDD_Adyen_Gateway_load' );

<?php
/**
 * Plugin Name:     Easy Digital Downloads - Simplify Commerce Gateway
 * Plugin URI:      https://easydigitaldownloads.com/extensions/simplify-commerce-gateway
 * Description:     Adds a payment gateway for Simplify Commerce to Easy Digital Downloads
 * Version:         1.0.0
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
            define( 'EDD_SIMPLIFY_COMMERCE_VERSION', '1.0.0' );

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
            // Nothing to see here, folks!
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
            $lang_dir = apply_filters( 'EDD_Simplify_Commerce_language_directory', $lang_dir );

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
                    '<a href="http://support.ghost1227.com/forums/forum/plugin-support/edd-simplify-commerce/" target="_blank">' . __( 'Support Forum', 'edd-simplify-commerce' ) . '</a>'
                );

                $docs_link = array(
                    '<a href="http://support.ghost1227.com/section/edd-simplify-commerce/" target="_blank">' . __( 'Docs', 'edd-simplify-commerce' ) . '</a>'
                );

                $links = array_merge( $links, $help_link, $docs_link );
            }

            return $links;
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
    return EDD_Simplify_Commerce::instance();
}


// Off we go!
EDD_Simplify_Commerce_load();

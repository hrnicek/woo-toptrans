<?php
 
/**
 * Plugin Name: Toptrans shipping
 * Plugin URI: https://zondy.cz/
 * Description: Toptrans Shipping Method for WooCommerce
 * Version: 1.0.0
 * Author: Jakub Hrnčíř
 * Author URI: https://www.zondy.cz
 * License: GPL-3.0+
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Domain Path: /lang
 * Text Domain: toptrans
 */

const TOPTRANSLUG = 'toptrans';
const API_TOPTRANS = 'https://zp.toptrans.cz/api/json/order/price/';

function toptrans_shipping_method_init() {
    if ( ! class_exists( 'WC_Toptrans_Shipping_Method' ) ) {
        class WC_Toptrans_Shipping_Method extends WC_Shipping_Method {
            /**
             * Constructor for your shipping class
             *
             * @access public
             * @return void
             */
            public function __construct($instance_id = 0) {
                $this->id                 = 'toptrans';
                $this->instance_id        = absint( $instance_id );
                $this->title       = 'Toptrans';
                $this->method_title       = 'Toptrans';
                $this->method_description = 'Toptrans API shipping method';
                $this->supports           = array(
                    'shipping-zones',
                    'instance-settings',
                    'instance-settings-modal',
                );
                $this->init();
            }

            /**
             * Init your settings
             *
             * @access public
             * @return void
             */
            function init() {
                // Load the settings.
		        $this->init_form_fields();
		        $this->init_settings();
                // Save settings in admin if you have any defined
                add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
            }

            /**
             * Init form fields.
             */
            public function init_form_fields() {
                $this->instance_form_fields = array(
                    'enabled'          => array(
                        'title'   => 'Povolit Toptrans',
                        'type'    => 'checkbox',
                        'label'   => 'Povolit tento způsob dopravy',
                        'default' => 'yes',
                    ),
                    'toptrans_nakladka'          => array(
                        'title'   => 'PSČ nakládky',
                        'type'    => 'text',
                        'label'   => 'Zadejte systémové přihl. jméno.',
                        'required' => true
                    ),
                    'toptrans_username'          => array(
                        'title'   => 'Uživatelské jméno',
                        'type'    => 'text',
                        'label'   => 'Zadejte systémové přihl. jméno.',
                        'required' => true
                    ),
                    'toptrans_password'          => array(
                        'title'   => 'Heslo',
                        'type'    => 'text',
                        'label'   => 'Zadejte systémové heslo.',
                        'required' => true
                    ),
                );
            }

            /**
             * calculate_shipping function.
             *
             * @access public
             * @param mixed $package
             * @return void
             */
            public function calculate_shipping( $package = array() ) {

                $zip = $package[ 'destination' ][ 'postcode' ];
                $totalWeight = WC()->cart->cart_contents_weight;

                $body = [
                    'loading_address_zip' => $this->get_option('toptrans_nakladka') ?? '77900',
                    'discharge_address_zip' => $zip,
                    'kg' => $totalWeight,
                    'oversize' => 0
                ];

                $body = wp_json_encode( $body );
                
                $params = array(
                    'method'      => 'POST',
                    'timeout'     => 30,
                    'headers' => array(
                        'Authorization' => 'Basic ' . base64_encode($this->get_option( 'toptrans_username' ). ':'.$this->get_option( 'toptrans_password' ))
                    ), 
                    'body' => $body 
                );

                $response = wp_remote_retrieve_body(wp_remote_post(API_TOPTRANS, $params));

                $result = json_decode($response, true);

                $fee = $result['data']['PRICE'];

                $rate = array(
                    'id'       => $this->id,
                    'label'    => $this->method_title,
                    'cost'     => round($fee),
                    'calc_tax' => 'per_order',
                    'package' => $package
                );

                $this->add_rate( $rate );
            }
        }
    }
}



add_action( 'woocommerce_shipping_init', 'toptrans_shipping_method_init' );

function add_toptrans_shipping_method( $methods ) {
    $methods['toptrans'] = 'WC_Toptrans_Shipping_Method'; 
    return $methods;
}

add_filter( 'woocommerce_shipping_methods', 'add_toptrans_shipping_method' );



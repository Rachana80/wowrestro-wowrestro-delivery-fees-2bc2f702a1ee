<?php
/**
* Plugin Name: WOWRestro - Delivery Fee
* Description: Add custom delivery fee based on Zip / Location for your store.
* Version: 1.0
* Author: MagniGenie
* Text Domain: wwro-delivery-fee
* Domain Path: languages
*
* @package delivery Fee
*/

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WWRO_DELIVERY_FEE_FILE' ) ) {
  define( 'WWRO_DELIVERY_FEE_FILE', __FILE__ );
}

// Include the main class WWRO_Delivery_Fee.
if ( ! class_exists( 'WWRO_Delivery_Fee_Loader', false ) ) {
  include_once dirname( __FILE__ ) . '/includes/class-wowrestro-delivery-fee-loader.php';
}

/**
 * Returns the main instance of WWRO_Delivery_Fee.
 *
 * @return WWRO_Delivery_Fee
 */
function WOWRESTRO_Delivery_Fee() {
  return WWRO_Delivery_Fee_Loader::instance();
}

WOWRESTRO_Delivery_Fee();
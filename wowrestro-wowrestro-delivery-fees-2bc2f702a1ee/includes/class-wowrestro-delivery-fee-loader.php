<?php
/**
 * WWRO_Delivery_Fee
 *
 * @package WWRO_Delivery_Fee
 * @since 1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main WWRO_Delivery_Fee Class.
 *
 * @class WWRO_Delivery_Fee
 */

class WWRO_Delivery_Fee_Loader {

  /**
   * WWRO_Delivery_Fee version.
   *
   * @var string
   */
  public $version = '1.0';


  /**
   * The single instance of the class.
   *
   * @var WWRO_Delivery_Fee
   * @since 1.0
   */
  protected static $_instance = null;


  /**
   * Main WWRO_Delivery_Fee Instance.
   *
   * Ensures only one instance of WWRO_Delivery_Fee is loaded or can be loaded.
   *
   * @since 1.0
   * @static
   * @return WWRO_Delivery_Fee - Main instance.
   */
  public static function instance() {

    if ( is_null( self::$_instance ) ) {

      self::$_instance = new self();
    }
    return self::$_instance;

  }

  /**
   * WWRO_Delivery_Fee Constructor.
   */
  public function __construct() {

    $this->define_constants();
    $this->includes();
    $this->init_hooks();

  }

  /**
   * Define constant if not already set.
   *
   * @param string      $name  Constant name.
   * @param string|bool $value Constant value.
   */
  private function define( $name, $value ) {

    if ( ! defined( $name ) ) {
      define( $name, $value );
    }

  }

  /**
   * Define Constants
   */
  private function define_constants() {

    $this->define( 'WWRO_DELIVERY_FEE_VERSION', $this->version );
    $this->define( 'WWRO_DELIVERY_FEE_PLUGIN_DIR', plugin_dir_path( WWRO_DELIVERY_FEE_FILE ) );
    $this->define( 'WWRO_DELIVERY_FEE_PLUGIN_URL', plugin_dir_url( WWRO_DELIVERY_FEE_FILE ) );
    $this->define( 'WWRO_DELIVERY_FEE_BASE', plugin_basename( WWRO_DELIVERY_FEE_FILE ) );

  }

  /**
   * Hook into actions and filters.
   *
   * @since 1.0
   */
  private function init_hooks() {

    add_action( 'admin_notices', array( $this, 'delivery_fee_required_plugins' ) );

    add_filter( 'plugin_action_links_' . WWRO_DELIVERY_FEE_BASE, array( $this, 'delivery_fee_settings_link' ) );

    add_action( 'plugins_loaded', array( $this, 'delivery_fee_load_textdomain' ) );

    add_filter( 'wowrestro_get_settings_pages', array( $this, 'wowrestro_get_delivery_fee_settings_page' ), 10, 1 );

  }

  /**
  * Include template file
  *
  * @since  1.0
  * @param  string file name which would be included
  */
  public static function wowrestro_get_template_part( $template, $data = '' ) {

    if ( ! empty( $template ) ) {
      require WWRO_DELIVERY_FEE_PLUGIN_DIR . 'includes/templates/' . $template . '.php';
    }

  }


  /**
   * Include setting page
   * 
   * @since 1.0
   */
  public function wowrestro_get_delivery_fee_settings_page( $settings ) {

    $settings[] = include 'admin/settings/class-wowrestro-settings-delivery-fee.php';

    return $settings;

  }

  /**
   * Check plugin dependency
   *
   * @since 1.0
   */
  public function delivery_fee_required_plugins() {

    if ( ! is_plugin_active( 'wowrestro/wowrestro.php' ) ) {
      $plugin_link = 'https://wordpress.org/plugins/wowrestro/';

      /* translators: %1$s: plugin link for wowrestro */
      echo '<div id="notice" class="error"><p>' . sprintf( __( 'Delivery Fee requires <a href="%1$s" target="_blank"> WOWRestro </a> plugin to be installed. Please install and activate it', 'wwro-delivery-fee' ), esc_url( $plugin_link ) ).  '</p></div>';

      deactivate_plugins( '/wowrestro-delivery-fee/wowrestro-delivery-fee.php' );
    }

  }

  /**
   * Add settings link for the plugin
   *
   * @since 1.0
   */
  public function delivery_fee_settings_link( $links ) {

    $link = admin_url( 'admin.php?page=wowrestro-settings&tab=delivery_fee' );
    /* translators: %1$s: settings page link */
    $settings_link = sprintf( __( '<a href="%1$s">Settings</a>', 'wwro-delivery-fee' ), esc_url( $link ) );
    array_unshift( $links, $settings_link );
    return $links;

  }

  /**
   * Include required files for settings
   *
   * @since 1.0
   */
  private function includes() {

    require_once WWRO_DELIVERY_FEE_PLUGIN_DIR . 'includes/class-wowrestro-delivery-fee.php';
    
  }

  /**
   * Load text domain
   *
   * @since 1.0
   */
  public function delivery_fee_load_textdomain() {

    load_plugin_textdomain( 'wwro-delivery-fee', false, dirname( plugin_basename( WWRO_DELIVERY_FEE_FILE ) ) . '/languages/' );

  }

}

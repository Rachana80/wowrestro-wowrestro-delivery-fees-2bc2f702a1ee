<?php
/**
 * WOWRestro Delivery_Fee Settings
 *
 * @package WOWRestro/Admin
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WWRO_Settings_Delivery_Fee', false ) ) {
  return new WWRO_Settings_Delivery_Fee();
}

/**
 * WWRO_Settings_Delivery_Fee.
 */
class WWRO_Settings_Delivery_Fee extends WWRO_Settings_Page {

  /**
   * Constructor.
   */
  public function __construct() {
    
    $this->id    = 'delivery_fee';
    $this->label = __( 'Delivery Fee', 'wwro-delivery-fee' );

    add_action( 'wowrestro_admin_field_map', array( $this, 'wowrestro_admin_field_map_html' ), 10, 1 );
    add_action( 'wowrestro_admin_field_button', array( $this, 'wowrestro_admin_field_button_html' ), 10, 1 );
    add_action( 'wowrestro_admin_field_delivery_fees', array( $this, 'wowrestro_admin_field_delivery_fees_html' ), 10, 1 );
    add_action( 'wowrestro_admin_field_location_delivery_fees', array( $this, 'wowrestro_admin_field_location_delivery_fees_html' ), 10, 1 );

    add_action( 'wowrestro_update_option_delivery_fees', array( $this, 'save_delivery_fees_options' ), 10, 1 );
    add_action( 'wowrestro_update_option_location_delivery_fees', array( $this, 'save_location_delivery_fees_options' ), 10, 1 );
    
    add_action( 'admin_enqueue_scripts', array( $this, 'wowrestro_delivery_fee_admin_script' ) );

    parent::__construct();
  }

  /**
   * 
   */
  public function save_location_delivery_fees_options( $options ) {

    if ( $options['type'] == 'location_delivery_fees' ) {
      $wowrestro_delivery_fee = !empty( $_POST['_wowrestro_location_delivery_fee'] ) ? $_POST['_wowrestro_location_delivery_fee'] : '';
      update_option( '_wowrestro_location_delivery_fee', $wowrestro_delivery_fee );
    }

  }

  /**
   * 
   */
  public function save_delivery_fees_options( $options ) {

    if ( $options['type'] == 'delivery_fees' ) {
      $wowrestro_delivery_fee = !empty( $_POST['_wowrestro_delivery_fee'] ) ? $_POST['_wowrestro_delivery_fee'] : '';
      update_option( '_wowrestro_delivery_fee', $wowrestro_delivery_fee );
    }

  }

  /**
   * 
   */
  public function wowrestro_admin_field_location_delivery_fees_html( $value ) {

    ?>
    <tr valign="top" class="<?php echo $value['row_class'] . ' ' . $value['row_hide_class'] ?>">
      <th scope="row" class="titledesc">
        <label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
      </th>
      <td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
        <div class="settings-row delivery-fee-table location-based-settings">
          <table id="wowrestro_delivery_location_fees" class="wp-list-table widefat fixed posts striped wowrestro_delivery_location_fees">
            <thead>
              <tr>
                <td>
                  <strong>
                    <?php esc_html_e( 'Delivery Fee', 'wwro-delivery-fee' ); ?>
                  </strong>
                  <span class="help-tag"><?php esc_html_e( 'Fee amount for the distance range.', 'wwro-delivery-fee' ); ?></span>
                  </td>
                <td>
                  <strong>
                    <?php esc_html_e( 'Distance in KM', 'wwro-delivery-fee' ); ?>
                  </strong>
                    <span class="help-tag"><?php esc_html_e( 'Enter distance range within which the delivery fee would be applicable. eg: 5-20', 'wwro-delivery-fee' ); ?></span>
                  </td>
                <td>
                  <strong>
                    <?php esc_html_e( 'Free Delivery Fee', 'wwro-delivery-fee' ); ?>
                  </strong>
                    <span class="help-tag"><?php esc_html_e( 'Enter minimum order amount below which the delivery fee would be applicable. If no amount has been set then the default amount would be taken into consideration.', 'wwro-delivery-fee' ); ?></span>
                  </td>
                <td>
                  <strong>
                  <?php esc_html_e( 'Min Order Amount', 'wwro-delivery-fee' ); ?>
                  </strong>
                  <span class="help-tag"><?php esc_html_e( 'Enter minimum order amount below which the customer could not able to place the order.', 'wwro-delivery-fee' ); ?></span>
                </td>
                <td>
                  <strong>
                    <?php esc_html_e( 'Action', 'wwro-delivery-fee' ); ?>
                  </strong>
                  </td>
              </tr>
            </thead>
            <tbody>
            <?php
              $delivery_table = get_option( '_wowrestro_location_delivery_fee' );
              if ( isset( $delivery_table['delivery_location_fee'] )
              && !empty( $delivery_table['delivery_location_fee'] ) ) :


                if ( is_array( $delivery_table['delivery_location_fee'] ) ) :

                  foreach( $delivery_table['delivery_location_fee'] as $key => $delivery_fee_data ) :

                    $fee_amount = isset( $delivery_fee_data['fee_amount'] ) ? $delivery_fee_data['fee_amount'] : '';

                    $distance = isset( $delivery_fee_data['distance'] ) ? $delivery_fee_data['distance'] : '';

                    $min_order_amount = isset( $delivery_fee_data['order_amount'] ) ? $delivery_fee_data['order_amount'] : '';

                    $set_min_order_amount = isset( $delivery_fee_data['set_min_order_amount'] ) ? $delivery_fee_data['set_min_order_amount'] : '';

              ?>
              <tr data-row-id="">
                <td>
                  <input type="text" value="<?php echo $fee_amount; ?>" name="_wowrestro_location_delivery_fee[delivery_location_fee][<?php echo $key; ?>][fee_amount]">
                </td>
                <td>
                  <input type="text" value="<?php echo $distance;  ?>" name="_wowrestro_location_delivery_fee[delivery_location_fee][<?php echo $key; ?>][distance]">
                </td>
                <td>
                  <input type="text" value="<?php echo $min_order_amount;  ?>" name="_wowrestro_location_delivery_fee[delivery_location_fee][<?php echo $key; ?>][order_amount]">
                </td>
                <td>
                  <input type="text" value="<?php echo $set_min_order_amount;  ?>" name="_wowrestro_location_delivery_fee[delivery_location_fee][<?php echo $key; ?>][set_min_order_amount]">
                </td>
                <td>
                  <a href="void(0)" data-row-id="<?php echo $key; ?>" class="wwro-delivery-fee-remove"></a>
                </td>
              </tr>
            <?php
                endforeach;
              endif;
            endif;
            ?>
            </tbody>
            <tfoot>
              <tr>
                <td colspan="5" class="delivery-table-footer">
                  <button class="button button-primary add-delivery-location wwro-pull-right"><?php esc_html_e( 'Add New Fee', 'wwro-delivery-fee' ); ?></button>
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      </tr>
    <?php

  }

  /**
   * 
   */
  public function wowrestro_admin_field_delivery_fees_html( $value ) {
    
    ?>
    <tr valign="top" class="<?php echo $value['row_class'] . ' ' . $value['row_hide_class'] ?>">
      <th scope="row" class="titledesc">
        <label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
      </th>
      <td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
        <div class="settings-row delivery-fee-table zip-based-settings">
          <table id="wowrestro_delivery_fees" class="wp-list-table widefat fixed posts striped wowrestro_delivery_fees">
            <thead>
              <tr>
                <td>
                  <strong>
                    <?php esc_html_e( 'Delivery Fee', 'wwro-delivery-fee' ); ?>
                  </strong>
                    <span class="help-tag"><?php esc_html_e( 'Fee amount for the zip/postal codes.', 'wwro-delivery-fee' ); ?></span>
                  </td>
                <td>
                  <strong>
                    <?php esc_html_e( 'ZIP/Postal Codes', 'wwro-delivery-fee' ); ?>
                  </strong>
                    <span class="help-tag"><?php esc_html_e( 'Enter zip/postal codes separated by comma(,)', 'wwro-delivery-fee' ); ?></span>
                  </td>
                  <td>
                    <strong>
                    <?php esc_html_e( 'Free Delivery Amount', 'wwro-delivery-fee' ); ?>
                    </strong>
                    <span class="help-tag"><?php esc_html_e( 'Enter order amount below which the delivery fee would be applicable.', 'wwro-delivery-fee' ); ?></span>
                  </td>
                  <td>
                    <strong>
                    <?php esc_html_e( 'Min Order Amount', 'wwro-delivery-fee' ); ?>
                    </strong>
                    <span class="help-tag"><?php esc_html_e( 'Enter minimum order amount below which the customer could not able to place the order. If no amount has been set then the default amount would be taken into consideration.', 'wwro-delivery-fee' ); ?></span>
                  </td>
                  <td>
                    <strong>
                      <?php esc_html_e( 'Action', 'wwro-delivery-fee' ); ?>
                    </strong>
                  </td>
                </tr>
            </thead>
            <tbody>
              <?php
                $delivery_table = get_option( '_wowrestro_delivery_fee' );
                if ( !empty( $delivery_table['delivery_fee'] ) ) :

                  if ( is_array( $delivery_table['delivery_fee'] ) ) :

                    foreach( $delivery_table['delivery_fee'] as $key => $delivery_fee_data ) :

                      $fee_amount = isset( $delivery_fee_data['fee_amount'] ) ? $delivery_fee_data['fee_amount'] : '';

                      $zip_code = isset( $delivery_fee_data['zip_code'] ) ? $delivery_fee_data['zip_code'] : '';

                      $min_order_amount = isset( $delivery_fee_data['order_amount'] ) ? $delivery_fee_data['order_amount'] : '';

                      $set_min_order_amount = isset( $delivery_fee_data['set_min_order_amount'] ) ? $delivery_fee_data['set_min_order_amount'] : '';


                      ?>
                      <tr data-row-id="<?php echo $key; ?>">
                        <td>
                          <input type="text" value="<?php echo $fee_amount; ?>" name="_wowrestro_delivery_fee[delivery_fee][<?php echo $key; ?>][fee_amount]">
                        </td>
                        <td>
                          <input type="text" value="<?php echo $zip_code; ?>" name="_wowrestro_delivery_fee[delivery_fee][<?php echo $key; ?>][zip_code]">
                        </td>
                        <td>
                          <input type="text" value="<?php echo $min_order_amount;  ?>" name="_wowrestro_delivery_fee[delivery_fee][<?php echo $key; ?>][order_amount]">
                        </td>
                        <td>
                          <input type="text" value="<?php echo $set_min_order_amount;  ?>" name="_wowrestro_delivery_fee[delivery_fee][<?php echo $key; ?>][set_min_order_amount]">
                        </td>
                        <td>
                          <a href="void(0)" data-row-id="<?php echo $key; ?>" class="wwro-delivery-fee-remove"></a>
                        </td>
                      </tr>
                      <?php
                    endforeach;
                  endif;

                endif;
              ?>
            </tbody>

            <tfoot>
              <tr>
                <td colspan="5" class="delivery-table-footer">
                  <button class="button button-primary wwro-pull-right wowrestro-add-delivery-fee-data"><?php esc_html_e( 'Add New Fee', 'wwro-delivery-fee' ); ?></button>
                </td>
              </tr>
            </tfoot>

          </table>
        </div>
      </td>
    </tr>
    <?php

  }

  /**
   * 
   */
  public function wowrestro_delivery_fee_admin_script() {

    if ( isset( $_GET['page'] ) 
      && $_GET['page'] == 'wowrestro-settings' 
      && isset( $_GET['tab'] ) 
      && $_GET['tab'] == 'delivery_fee' ) {

      $google_map_api   = ( get_option( '_wowrestro_google_map_api' ) ) ? get_option( '_wowrestro_google_map_api' ) : '';
      $store_location   = ( get_option( '_wowrestro_store_location' ) ) ? get_option( '_wowrestro_store_location' ) : 'Bhubaneswar, India';
      $store_latlng     = ( get_option( '_wowrestro_store_latlng' ) ) ? get_option( '_wowrestro_store_latlng' ) : '20.296059,85.824539';
      $delivery_method  = ( get_option( '_wowrestro_delivery_method' ) ) ? get_option( '_wowrestro_delivery_method' ) : 'zip_based';
      
      wp_register_script( 'wwro-admin-gmap-api', "https://maps.googleapis.com/maps/api/js?key=$google_map_api&libraries=places&callback=initMap", array(), '', true );
      wp_enqueue_script( 'wwro-admin-gmap-api' );

      wp_register_script( 'wwro-admin-delivery-fee', WWRO_DELIVERY_FEE_PLUGIN_URL . 'assets/js/wowrestro-admin-delivery-fee.js', array( 'jquery' ), WWRO_DELIVERY_FEE_VERSION );
      wp_enqueue_script( 'wwro-admin-delivery-fee' );

      wp_register_style( 'wwro-admin-delivery-fee', WWRO_DELIVERY_FEE_PLUGIN_URL . 'assets/css/wowrestro-admin-delivery-fee.css', array(), WWRO_DELIVERY_FEE_VERSION );
      wp_enqueue_style( 'wwro-admin-delivery-fee' );

      $store_latlng = explode( ',', $store_latlng );

      $store_lat_position = isset( $store_latlng[0] ) ? $store_latlng[0] : '20.296059';
      
      $store_lng_position = isset( $store_latlng[1] ) ? $store_latlng[1] : '85.824539';

      $params = array(
        'delivery_fee_method' => $delivery_method,
        'distance_example'    => __( 'eg: 5-20', 'wwro-delivery-fee' ),
        'remove'              => __( 'Remove', 'wwro-delivery-fee' ),
        'store_location'      => $store_location,
        'store_lat_position'  => $store_lat_position,
        'store_lng_position'  => $store_lng_position,
      );

      wp_localize_script( 'wwro-admin-delivery-fee', 'wwroDeliveryFee', $params );

    }

  }

  /**
   * 
   */
  public function wowrestro_admin_field_map_html( $value ) {

    ?>
      <tr valign="top" class="<?php echo $value['row_class'] . ' ' . $value['row_hide_class'] ?>">
        <th scope="row" class="titledesc">
          <label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
        </th>
        <td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
          <div id="<?php echo esc_attr( $value['id'] ); ?>"></div>
          <div id="current"></div>
        </td>
      </tr>
    <?php

  }

  /**
   * 
   */
  public function wowrestro_admin_field_button_html( $value ) {

    ?>
      <tr valign="top" class="<?php echo $value['row_class'] . ' ' . $value['row_hide_class'] ?>">
        <th scope="row" class="titledesc">
        </th>
        <td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
          <input id="<?php echo esc_attr( $value['id'] ); ?>" type="<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>" value="<?php echo esc_html( $value['title'] ); ?>">
        </td>
      </tr>
    <?php

  }


  /**
   * Get settings array.
   *
   * @param string $current_section Current section name.
   * @return array
   */
  public function get_settings( $current_section = '' ) {

      $hidden_class = ( get_option( '_wowrestro_delivery_method' ) != 'location_based' ) ? 'wwro-d-none' : '';

      $settings = apply_filters(
          'wowrestro_delivery_fee_settings',
          array(

            array(
              'title'   => __( 'Delivery Fee Settings', 'wwro-delivery-fee' ),
              'type'    => 'title',
              'desc'    => '',
              'id'      => 'delivery_fee_options',
            ),

            array(
              'title'     => __( 'Enable Delivery Fee', 'wwro-delivery-fee' ),
              'type'      => 'checkbox',
              'id'        => '_enable_wowrestro_delievery_fee',
              'default'   => 'yes',
            ),

          

            array(
              'title'   => __( 'Error message for unavailable zip/postal code/distance', 'wwro-delivery-fee' ),
              'id'      => '_wowrestro_unavailable_message',
              'type'    => 'textarea',
            ),
            array(
              'title'   => __( 'Error message for mimimum order amount', 'wwro-delivery-fee' ),
              'id'      => '_wowrestro_unavailable_order_amount',
              'type'    => 'textarea',
            ),

            array(
              'title'     => __( 'Select delivery fee method', 'wwro-delivery-fee' ),
              'desc'      => __( 'Please select how would you like to charge the delivery fee.', 'wwro-delivery-fee' ),
              'id'        => '_wowrestro_delivery_method',
              'default'   => 'zip_based',
              'type'      => 'radio',
              'options'   => array(
                'zip_based'       => __( 'Zip Based', 'wwro-delivery-fee' ),
                'location_based'  => __( 'Location Based', 'wwro-delivery-fee' ),
              ),
              'desc_tip'  => true,
              'class'     => 'wowrestro_delivery_method'
            ),

            array(
              'title'       => __( 'Zip/Postal based Delivery Fee options', 'wwro-delivery-fee' ),
              'id'          => 'wowrestro_delivery_fees',
              'type'        => 'delivery_fees',
              'row_class'   => 'wwro-zip-based-options',
              'row_hide_class' => ( get_option( '_wowrestro_delivery_method' ) == 'location_based' ) ? 'wwro-d-none' : '',
            ),

            array(
              'title'       => __( 'Location based Delivery Fee options', 'wwro-delivery-fee' ),
              'id'          => 'wowrestro_location_delivery_fees',
              'type'        => 'location_delivery_fees',
              'row_class'   => 'wwro-location-based-options',
              'row_hide_class' => $hidden_class,
            ),

            array(
              'title'       => __( 'Google Map API Key', 'wwro-delivery-fee' ),
              'desc'        => __( 'Enter google map api key. You can get your google map api from here <a href="https://developers.google.com/maps/documentation/javascript/get-api-key" target="_blank">https://developers.google.com/maps/documentation/javascript/get-api-key</a>', 'wwro-delivery-fee' ),
              'id'          => '_wowrestro_google_map_api',
              'type'        => 'text',
              'row_class'   => 'wwro-location-based-options',
              'row_hide_class' => $hidden_class,
            ),

            array(
              'title'       => __( 'Enter Checkout Full address field placeholder.', 'wwro-delivery-fee' ),
              'desc'        => __( 'Enter the placeholder for autocomplete address field on checkout page.', 'wwro-delivery-fee' ),
              'id'          => '_wowrestro_full_address_placeholder',
              'type'        => 'text',
              'default'     => __( 'Enter your full address', 'wwro-delivery-fee' ),
              'row_class'   => 'wwro-location-based-options',
              'row_hide_class' => $hidden_class,
            ),

            array(
              'title'       => __( 'Store Location', 'wwro-delivery-fee' ),
              'desc'        => __( 'You can drag the marker to set your exact location', 'wwro-delivery-fee' ),
              'id'          => '_wowrestro_store_address',
              'type'        => 'text',
              'row_class'   => 'wwro-location-based-options',
              'row_hide_class' => $hidden_class,
            ),

            array(
              'title'       => __( 'Set Location', 'wwro-delivery-fee' ),
              'id'          => '_wowrestro_location_submit',
              'type'        => 'button',
              'row_class'   => 'wwro-location-based-options',
              'row_hide_class' => $hidden_class,
            ),

            array(
              'title'       => __( 'Location', 'wwro-delivery-fee' ),
              'id'          => 'wowrestro_map_canvas',
              'type'        => 'map',
              'row_class'   => 'wwro-location-based-options',
              'row_hide_class' => $hidden_class,
            ),

            array(
              'title'       => __( 'Store Lat-Long', 'wwro-delivery-fee' ),
              'id'          => '_wowrestro_store_latlng',
              'type'        => 'text',
              'row_class'   => '',
              'row_hide_class' => 'wwro-d-none',
            ),

            array(
              'title'     => __( 'Distance unit', 'wwro-delivery-fee' ),
              'desc'      => __( 'Select distance unit what you want to use for distance units.', 'wwro-delivery-fee' ),
              'id'        => '_wowrestro_distance_unit',
              'type'      => 'select',
              'desc_tip'  =>  true,
              'options'   => array(
                'km'      => __( 'KM', 'wwro-delivery-fee' ),
                'miles'  => __( 'Miles', 'wwro-delivery-fee' ),
              ),
              'class'     => 'wc-enhanced-select',
            ),

            // ,

            array(
              'title'     => __( 'Free Delivery Amount', 'wwro-delivery-fee' ),
              'desc'      => __( 'Enter the total amount above which the customer should get free delivery.', 'wwro-delivery-fee' ),
              'id'        => '_wowrestro_free_delivery_amount',
              'type'      => 'number',
              'desc_tip'  =>  true,
            ),

            array(
              'type' => 'sectionend',
              'id'   => 'delivery_fee_options',
            ),

          )
        );

    return apply_filters( 'wowrestro_get_settings_' . $this->id, $settings, $current_section );
  }
}

return new WWRO_Settings_Delivery_Fee();
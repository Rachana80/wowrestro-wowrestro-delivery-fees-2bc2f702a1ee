<?php

/**
 * WWRO_Delivery_Fee_Functions
 *
 * @package WWRO_Delivery_Fee_Functions
 * @since 1.0
 */

defined('ABSPATH') || exit;

class WWRO_Delivery_Fee
{

  public function __construct()
  {

    add_action('wp_enqueue_scripts', array($this, 'enqueue_wowrestro_delivery_fee_script'), 10, 1);

    add_action('woocommerce_checkout_update_order_review', array($this,  'wowrestor_delivery_fee_set_address_session'), 10, 1);

    add_action('woocommerce_cart_calculate_fees', array($this, 'wowrestro_delivery_fee_check_fees'), 10, 1);

    add_action('woocommerce_checkout_process', array($this, 'wowrestro_process_checkout_delivery_fields'), 10, 1);

    $delivery_fee_method  = get_option('_wowrestro_delivery_method');
    if ($delivery_fee_method == 'location_based') {
      add_action('woocommerce_before_checkout_billing_form', array($this, 'wowrestro_delivery_fee_location_based_checkout_field'), 10, 1);
      add_action('wowrestro_before_delivery_address', array($this, 'wowrestro_delivery_address_wrap'), 10, 1);
      add_action('wowrestro_after_delivery_address', array($this, 'wowrestro_delivery_address_wrap_end'), 10, 1);
    }

    add_action('woocommerce_checkout_update_order_meta', array($this, 'wowrestro_delivery_fee_save_services_meta'), 10, 1);

    add_action('woocommerce_admin_order_data_after_shipping_address', array($this, 'wowrestro_admin_delivery_meta'));
  }

  /**
   * Add Script file
   *
   * @since 1.0
   */
  public function enqueue_wowrestro_delivery_fee_script()
  {

    $google_map_api       = get_option('_wowrestro_google_map_api');

    $delivery_fee_method  = get_option('_wowrestro_delivery_method');

    wp_register_style('wwro-delivery-fee', WWRO_DELIVERY_FEE_PLUGIN_URL . 'assets/css/wowrestro-delivery-fee.css', array(), WWRO_DELIVERY_FEE_VERSION);

    wp_enqueue_style('wwro-delivery-fee');

    wp_register_script('wwro-delivery-fee', WWRO_DELIVERY_FEE_PLUGIN_URL . 'assets/js/wowrestro-delivery-fee.js', array('jquery'), WWRO_DELIVERY_FEE_VERSION);

    wp_register_script('wwro-google-map-api', "https://maps.googleapis.com/maps/api/js?key=$google_map_api&libraries=places&callback=initAutocomplete", array(), '', true);

    wp_enqueue_script('wwro-delivery-fee');

    if ($delivery_fee_method == 'location_based') {
      wp_enqueue_script('wwro-google-map-api');
    }

    $store_country = get_option('woocommerce_default_country');

    if (strpos($store_country, ':')) {
      $country = explode(':', $store_country);
      $store_country = current($country);
    }

    $params = array(
      'fee'                 => __('Delivery Fee', 'wwro-delivery-fee'),
      'delivery_fee_method' => $delivery_fee_method,
      'store_country'       => $store_country,
      'geo_code_error'      => __("Can't access your location", 'wwro-delivery-fee'),
    );

    wp_localize_script('wwro-delivery-fee', 'deliveryFeeVars', $params);
  }

  /**
   * Set Zip / Location based session value on checkout
   *
   * @since 1.0
   * 
   * @param sting $post_data
   * 
   * @return null
   */
  public function wowrestor_delivery_fee_set_address_session($post_data)
  {

    // Parsing posted data on checkout
    $vars = explode('&', $post_data);
    $post = array();
    foreach ($vars as $k => $value) {
      $v = explode('=', urldecode($value));
      $post[$v[0]] = $v[1];
    }

    $delivery_fee_method  = get_option('_wowrestro_delivery_method', 'zip_based');
    $zip_code             = isset($post['billing_postcode']) ? sanitize_text_field($post['billing_postcode']) : null;
    $latllng              = isset($post['wwro_delivery_latllng']) ? sanitize_text_field($post['wwro_delivery_latllng']) : null;

    if (!empty($zip_code) && $delivery_fee_method == 'zip_based') {
      wowrestro_set_session('delivery_zip', $zip_code);
    } else if (!empty($latllng) && $delivery_fee_method == 'location_based') {
      wowrestro_set_session('delivery_latlng', $latllng);
    } else {
      wowrestro_remove_session('delivery_zip');
      wowrestro_remove_session('delivery_latlng');
    }
  }

  /**
   * Calculate cart calculate fees
   *
   * @since 1.0
   */
  public function wowrestro_delivery_fee_check_fees($cartdata)
  {

    $delivery_fee = $this->wowrestro_get_delivery_fee();

    if (get_option('_enable_wowrestro_delievery_fee', 'yes') == 'yes' && !empty($delivery_fee)) {
      WC()->cart->add_fee('Delivery Fee', $delivery_fee, false);
    }
  }

  /**
   * Validate delivery fee fileds on checkout 
   *
   * @since 1.0
   */
  public function wowrestro_process_checkout_delivery_fields()
  {
    $delivery_fee_method  = get_option('_wowrestro_delivery_method', 'zip_based');
    $service_type         = wowrestro_get_session('service_type');
    $delivery_zip         = isset($_POST['billing_postcode']) ? sanitize_text_field($_POST['billing_postcode']) : null;
    $delivery_location    = isset($_POST['wwro_delivery_latllng']) ? sanitize_text_field($_POST['wwro_delivery_latllng']) : null;
    $delivery_settings = get_option('_wowrestro_delivery_fee');
    $delivery_location_setting = get_option('_wowrestro_location_delivery_fee');
    $cart = WC()->cart;
    $total_amount = $cart->cart_contents_total;


    if (get_option('_enable_wowrestro_delievery_fee', 'yes') == 'yes' && $service_type == 'delivery' && (($delivery_fee_method == 'zip_based' && empty($delivery_zip)) || ($delivery_fee_method == 'location_based' && empty($delivery_location)))) {
      wc_add_notice(__('Please check your address.', 'wwro-delivery-fee'), 'error');
    }


    // Check if delivery fee is not aplicable on the area by zip code
    if (get_option('_enable_wowrestro_delievery_fee', 'yes') == 'yes' && $delivery_fee_method == 'zip_based' && !empty($delivery_zip) && $service_type == 'delivery') {
      $is_zip_code_valid = $this->wowrestro_validate_delivery_zip_code($delivery_zip);
      if (!$is_zip_code_valid) {
        wc_add_notice(get_option('_wowrestro_unavailable_message'), 'error');
        // wc_add_notice(     $total_amount, 'error' );
      }
    }
    // Check if delivery mimimum amount of order is not aplicable on the area by zip code


    if (get_option('_enable_wowrestro_delievery_fee', 'yes') == 'yes' && $delivery_fee_method == 'zip_based' && !empty($delivery_zip) && $service_type == 'delivery') {
      if (isset($delivery_settings['delivery_fee'])) {
        $delivery_zones = $delivery_settings['delivery_fee'];

        foreach ($delivery_zones as $key => $delivery_zone) {
          $zipcodes = $delivery_zone['zip_code'];
          $zipcodes = explode(',', $zipcodes);
          if (in_array($delivery_zip, $zipcodes)) {
            $min_amount = $delivery_zone['set_min_order_amount'];
            if (!empty($min_amount) && $total_amount <= $min_amount) {
              wc_add_notice(get_option('_wowrestro_unavailable_order_amount'), 'error');
            }
          }
        }
      }
    }

    // Check if delivery fee is not aplicable on the area by location based
    if (get_option('_enable_wowrestro_delievery_fee', 'yes') == 'yes' && $delivery_fee_method == 'location_based' && !empty($delivery_location) && $service_type == 'delivery') {
      $is_location_valid = $this->wowrestro_validate_delivery_address($delivery_location);
      if (!$is_location_valid) {
        wc_add_notice(get_option('_wowrestro_unavailable_message'), 'error');
      }
    }
    // Check if delivery mimimum amount of order is not aplicable on the area by location based
    if (get_option('_enable_wowrestro_delievery_fee', 'yes') == 'yes' && $delivery_fee_method == 'location_based' && !empty($delivery_location) && $service_type == 'delivery') {

      if (isset($delivery_location_setting['delivery_location_fee'])) {

        $delivery_zone_locations = $delivery_location_setting['delivery_location_fee'];
     
        $store_position     = get_option('_wowrestro_store_latlng', '');
        
        $store_position     = explode(',', $store_position);
     
        $store_lat          = isset($store_position[0]) ? $store_position[0] : '';
       
        $store_lng          = isset($store_position[1]) ? $store_position[1] : '';
       
        $delivery_latlng    = trim($delivery_location);

        $delivery_latlng    = explode(',', $delivery_latlng);
    
        $delivery_pos_lat   = isset($delivery_latlng[0]) ? trim($delivery_latlng[0]) : '';
       
        $delivery_pos_lng   = isset($delivery_latlng[1]) ? trim($delivery_latlng[1]) : '';
      

        $distance           = $this->get_distance_by_latlng($store_lat, $store_lng, $delivery_pos_lat, $delivery_pos_lng, 'km');
      

        foreach ($delivery_zone_locations as $key =>  $delivery_zone_location) {
        

          $distance_limit = isset($delivery_zone_location['distance']) ? $delivery_zone_location['distance'] : '';
       

          if (!empty($distance_limit)) {
        
            $distance_limit = explode('-', $distance_limit);
         

            $distance_from = isset($distance_limit[0]) ? $distance_limit[0] : 0;
         
            $distance_to   = isset($distance_limit[1]) ? $distance_limit[1] : 0;
       
         
              if ($distance_from <=  $distance &&  $distance_to >= $distance) {

                $min_amount = $delivery_zone_location['set_min_order_amount'];
                
       
                if (!empty($min_amount) && $total_amount <= $min_amount) {
                
                  wc_add_notice(get_option('_wowrestro_unavailable_order_amount'), 'error');

                }
              }
       
          }
        }


      }
    }
  }





  /**
   * validate if delivery fee exists for zip code
   */
  function wowrestro_validate_delivery_zip_code($delivery_zip)
  {

    $delivery_zip_code = trim($delivery_zip);
    $delivery_zip_code = strtolower($delivery_zip_code);

    $delivery_zip_code = str_replace(' ', '', $delivery_zip_code);
    $delivery_zip_code = str_replace('+', '', $delivery_zip_code);

    $delivery_settings = get_option('_wowrestro_delivery_fee');


    if (isset($delivery_settings['delivery_fee'])) {

      $delivery_zones = $delivery_settings['delivery_fee'];

      $all_zip_code = [];
      foreach ($delivery_zones as $key => $delivery_zone) {
        $zip_code             = !empty($delivery_zone['zip_code']) ? $delivery_zone['zip_code'] : '';
        $min_order_amount     = !empty($delivery_zone['order_amount']) ? $delivery_zone['order_amount'] : $free_delivery_amount;
        $set_min_order_amount = !empty($delivery_zone['set_min_order_amount']) ? $delivery_zone['set_min_order_amount'] : 0;

        if (!empty($zip_code)) {
          $zip_code = explode(',', preg_replace('/\s+/', '', strtolower($zip_code)));
          $all_zip_code = array_merge($all_zip_code, $zip_code);
        }
      }

      if (in_array($delivery_zip_code, $all_zip_code)) {
        return true;
      } else {
        foreach ($all_zip_code as $k => $zip) {
          if (strpos($zip, "*") !== false) {
            if (substr($zip, 0, -1) == substr($delivery_zip_code, 0, strlen($zip) - 1)) {
              return true;
            }
          }
        }
        return false;
      }
    }
  }

  /**
   * validate is delivery fee exist for location
   */
  function wowrestro_validate_delivery_address($location)
  {

    $distance_unit      = get_option('_wowrestro_distance_unit', 'km');

    $store_position     = get_option('_wowrestro_store_latlng', '');

    $store_position     = explode(',', $store_position);

    $store_lat          = isset($store_position[0]) ? $store_position[0] : '';
    $store_lng          = isset($store_position[1]) ? $store_position[1] : '';
    $response           = array();

    $delivery_latlng    = trim($location);
    $delivery_latlng    = explode(',', $delivery_latlng);
    $delivery_pos_lat   = isset($delivery_latlng[0]) ? trim($delivery_latlng[0]) : '';
    $delivery_pos_lng   = isset($delivery_latlng[1]) ? trim($delivery_latlng[1]) : '';

    $distance           = $this->get_distance_by_latlng($store_lat, $store_lng, $delivery_pos_lat, $delivery_pos_lng, $distance_unit);

    $response           = $this->check_fee_by_distance($distance);

    return $response;
  }

  function check_fee_by_distance($distance)
  {

    $delivery_settings  = get_option('_wowrestro_location_delivery_fee');;
    $delivery_locations = $delivery_settings['delivery_location_fee'];
    $error_message      = get_option('_wowrestro_unavailable_message');
    $service_type       = wowrestro_get_session('service_type');
    $cart_totals        = WC()->cart->get_totals();
    $cart_subtotal      = $cart_totals['subtotal'];

    $free_delivery_amount = get_option('_wowrestro_free_delivery_amount', 0);

    if (is_array($delivery_locations) && !empty($delivery_locations)) {

      foreach ($delivery_locations as $key => $delivery_location) {

        $distance_limit = isset($delivery_location['distance']) ? $delivery_location['distance'] : '';

        $min_order_amount  = !empty($delivery_location['order_amount']) ? $delivery_location['order_amount'] : $free_delivery_amount;

        $set_min_order_amount = !empty($delivery_location['set_min_order_amount']) ? $delivery_location['set_min_order_amount'] : 0;

        if (!empty($distance_limit)) {

          $distance_limit = explode('-', $distance_limit);

          $distance_from = isset($distance_limit[0]) ? $distance_limit[0] : 0;
          $distance_to   = isset($distance_limit[1]) ? $distance_limit[1] : 0;

          if ((($distance == $distance_from)
            || (($distance > $distance_from) && ($distance < $distance_to)))) {
            return true;
          }
        }
      }
    }

    return false;
  }

  /**
   * Creating custom field and icon for autocomplete
   *
   * @param mixed
   * @return empty
   *
   */
  public function wowrestro_delivery_fee_location_based_checkout_field($checkout)
  {

    $label = get_option('_wowrestro_full_address_placeholder');

    do_action('wowrestro_before_delivery_address', $checkout);

    woocommerce_form_field('wowrestro_checkout_full_address_field', array(
      'type'          => 'text',
      'class'         => array('wowrestro-checkout-full-address form-row-wide'),
      'placeholder'   => $label,
    ), $checkout->get_value('wowrestro_checkout_full_address_field'));


    do_action('wowrestro_after_delivery_address', $checkout);
  }

  /**
   * Delivery Fee wrap
   */
  public function wowrestro_delivery_address_wrap()
  {

    $html = '<div id="wowrestro_delivery_checkout_full_address">';
    echo $html;
  }

  /**
   * Delivery fee wrap end
   */
  public function wowrestro_delivery_address_wrap_end($checkout)
  {

    $checkout_latlng = $checkout->get_value('_wowrestro_checkout_latlng');

    // Hidden field for latlon
    $html = '<input type="hidden" class="wwro_delivery_latllng" name="wwro_delivery_latllng" value="' . $checkout_latlng . '" >';

    // select current location
    $html .= '<a href="#" class="wowrestro-get-geo-location"><i class="fa fa-map-marker"></i></a>';

    $html .= '</div>';

    echo $html;
  }

  /**
   * Save delivery fee meta to order and user data
   */
  public function wowrestro_delivery_fee_save_services_meta($order_id)
  {


    if (!empty($_POST['wowrestro_checkout_full_address_field'])) {
      update_post_meta($order_id, '_wowrestro_checkout_full_address_field', sanitize_text_field($_POST['wowrestro_checkout_full_address_field']));
      update_user_meta(get_current_user_id(), '_wowrestro_user_checkout_full_address_field', sanitize_text_field($_POST['wowrestro_checkout_full_address_field']));
    }

    if (!empty($_POST['wwro_delivery_latllng'])) {
      update_post_meta($order_id, '_wowrestro_checkout_latlng', sanitize_text_field($_POST['wwro_delivery_latllng']));
      update_user_meta(get_current_user_id(), '_wowrestro_user_checkout_latlng', sanitize_text_field($_POST['wwro_delivery_latllng']));
    }
  }

  /**
   * Show delivery data on order details 
   */
  public function wowrestro_admin_delivery_meta($order)
  {

    $order_id   = version_compare(WC_VERSION, '3.0.0', '<') ? $order->id : $order->get_id();
    $delivery_fee_method  = get_option('_wowrestro_delivery_method');
    if ($delivery_fee_method == 'location_based') {
      $wowrestro_checkout_full_address_field  = get_post_meta($order_id, '_wowrestro_checkout_full_address_field', true);
      $wowrestro_checkout_latlng              = get_post_meta($order_id, '_wowrestro_checkout_latlng', true);

      $checkout_latlng = explode(',', $wowrestro_checkout_latlng);
      $lat = current($checkout_latlng);
      $lng = end($checkout_latlng);
      $map_link = 'https://maps.google.com/maps?z=12&t=m&q=loc:' . $lat . '+' . $lng;

      echo '<p> <strong>' . __('Delivery Address', 'wwro-delivery-fee') . ' : </strong> <br>' . $wowrestro_checkout_full_address_field . '</p>';
      echo '<p> <a target="_blank" href="' . $map_link . '"> <strong>' . __('Get Directions', 'wwro-delivery-fee') . ' </strong></a></p>';
    }
  }

  /**
   * Get delivery fee based on the zone
   *
   * @return array delivery response
   * @since 1.0
   */
  public function get_delivery_fee_by_zip($delivery_zip_code)
  {

    $delivery_settings = get_option('_wowrestro_delivery_fee');

    $response = array();

    $response['fee']    = 0;
    $response['status'] = 'error';
    $response['msg']    = '';

    $cart_totals        = WC()->cart->get_totals();
    $delivery_zip_code  = trim($delivery_zip_code);
    $new_cart_subtotal  = $cart_totals['total'];
    $service_type       = wowrestro_get_session('service_type');

    $free_delivery_amount = get_option('_wowrestro_free_delivery_amount', 0);

    if (empty($delivery_zip_code)) {
      $response['msg']  = __('Please enter your zip/postal code', 'wwro-delivery-fee');
      return $response;
    }

    if (isset($delivery_settings['delivery_fee']) && $service_type == 'delivery') {

      $response['msg']   = get_option('_wowrestro_unavailable_message', esc_html('Sorry! we don\'t deliver to this zip/postal code', 'wwro-delivery-fee'));

      $delivery_zones    = $delivery_settings['delivery_fee'];

      $delivery_zip_code = str_replace(' ', '', $delivery_zip_code);
      $delivery_zip_code = str_replace('+', '', $delivery_zip_code);

      foreach ($delivery_zones as $key => $delivery_zone) {
        $zip_code             = !empty($delivery_zone['zip_code']) ? $delivery_zone['zip_code'] : '';
        $min_order_amount     = !empty($delivery_zone['order_amount']) ? $delivery_zone['order_amount'] : $free_delivery_amount;
        $set_min_order_amount = !empty($delivery_zone['set_min_order_amount']) ? $delivery_zone['set_min_order_amount'] : 0;

        if (!empty($zip_code)) {
          $zip_code = explode(',', preg_replace('/\s+/', '', strtolower($zip_code)));

          $delivery_zip_code = strtolower($delivery_zip_code);

          if (in_array(strtolower($delivery_zip_code), $zip_code)) {

            $fee = $delivery_zones[$key]['fee_amount'];

            $response['status']               = 'success';
            $response['fee']                  = $fee;
            $response['msg']                  = '';
            $response['min_order_amount']     = $min_order_amount;
            $response['set_min_order_amount'] = $set_min_order_amount;
          } else {
            foreach ($zip_code as $k => $zip) {
              if (strpos($zip, "*") !== false) {
                if (substr($zip, 0, -1) == substr($delivery_zip_code, 0, strlen($zip) - 1)) {

                  $response['status']               = 'success';
                  $response['fee']                  = $delivery_zones[$key]['fee_amount'];
                  $response['msg']                  = '';
                  $response['min_order_amount']     = $min_order_amount;
                  $response['set_min_order_amount'] = $set_min_order_amount;
                }
              }
            }
          }
        }
      }
    }

    return $response;
  }


  /**
   * Get delivery fee based on the latlng
   *
   * @return array delivery response
   * @since 1.0
   */
  public function get_delivery_fee_by_location($delivery_latlng)
  {

    $response = array();

    if (empty($delivery_latlng)) {
      $response['fee']    = 0;
      $response['status'] = 'error';
      $response['msg']  = __('Please enter your location', 'wwro-delivery-fee');
      return $response;
    }

    $distance_unit      = get_option('_wowrestro_distance_unit', 'km');

    $store_position     = get_option('_wowrestro_store_latlng', '');

    $store_position     = explode(',', $store_position);

    $store_lat          = isset($store_position[0]) ? $store_position[0] : '';
    $store_lng          = isset($store_position[1]) ? $store_position[1] : '';
    $response           = array();

    $delivery_latlng    = trim($delivery_latlng);
    $delivery_latlng    = explode(',', $delivery_latlng);
    $delivery_pos_lat   = isset($delivery_latlng[0]) ? trim($delivery_latlng[0]) : '';
    $delivery_pos_lng   = isset($delivery_latlng[1]) ? trim($delivery_latlng[1]) : '';

    $distance           = $this->get_distance_by_latlng($store_lat, $store_lng, $delivery_pos_lat, $delivery_pos_lng, $distance_unit);

    $response           = self::get_fee_by_matching_distance($distance);

    return $response;
  }

  /**
   * Calculate distance between source and target lat,lng values
   *
   * @return string
   * @since 1.0
   */
  public function get_distance_by_latlng($source_lat, $source_lon, $target_lat, $target_lon, $unit)
  {

    $source_lat = floatval($source_lat);
    $source_lon = floatval($source_lon);

    $target_lat = floatval($target_lat);
    $target_lon = floatval($target_lon);


    if (($source_lat == $target_lat) && ($source_lon == $target_lon)) {
      return 0;
    } else {
      $theta  = $source_lon - $target_lon;
      $dist   = sin(deg2rad($source_lat)) * sin(deg2rad($target_lat)) +  cos(deg2rad($source_lat)) * cos(deg2rad($target_lat)) * cos(deg2rad($theta));
      $dist   = acos($dist);
      $dist   = rad2deg($dist);
      $miles  = $dist * 60 * 1.1515;

      if ($unit == "km") {
        return ($miles * 1.609344);
      } else {
        return $miles;
      }
    }
  }

  /**
   * Get matching fee based on the distance range limit
   *
   * @return array
   * @since 1.0
   */
  public static function get_fee_by_matching_distance($distance)
  {

    $delivery_settings  = get_option('_wowrestro_location_delivery_fee');;
    $delivery_locations = $delivery_settings['delivery_location_fee'];
    $error_message      = get_option('_wowrestro_unavailable_message');
    $service_type       = wowrestro_get_session('service_type');
    $cart_totals        = WC()->cart->get_totals();
    $cart_subtotal      = $cart_totals['subtotal'];
    if ($service_type == 'delivery') {

      $response = array();

      $response['status']   = 'error';
      $response['msg']      = $error_message;
      $free_delivery_amount = get_option('_wowrestro_free_delivery_amount', 0);

      if (is_array($delivery_locations) && !empty($delivery_locations)) {

        foreach ($delivery_locations as $key => $delivery_location) {

          $distance_limit = isset($delivery_location['distance']) ? $delivery_location['distance'] : '';

          $min_order_amount  = !empty($delivery_location['order_amount']) ? $delivery_location['order_amount'] : $free_delivery_amount;

          $set_min_order_amount = !empty($delivery_location['set_min_order_amount']) ? $delivery_location['set_min_order_amount'] : 0;

          if (!empty($distance_limit)) {

            $distance_limit = explode('-', $distance_limit);

            $distance_from = isset($distance_limit[0]) ? $distance_limit[0] : 0;
            $distance_to   = isset($distance_limit[1]) ? $distance_limit[1] : 0;

            if ((($distance == $distance_from)
              || (($distance > $distance_from) && ($distance < $distance_to)))) {
              $response['status'] = 'success';
              $response['fee']    = $delivery_locations[$key]['fee_amount'];
              $response['msg']    = '';
              $response['min_order_amount'] = $min_order_amount;
              $response['set_min_order_amount'] = $set_min_order_amount;
            }
          }
        }
      }
      return $response;
    }
  }

  /**
   * Set delivery fee
   *
   * @return delivery fee
   * @since 1.0
   */
  public function wowrestro_get_delivery_fee()
  {

    if (wowrestro_get_session('service_type') == 'pickup') return 0;

    $zip_code         = !empty(wowrestro_get_session('delivery_zip')) ? wowrestro_get_session('delivery_zip') : '';
    $delivery_latlng  = !empty(wowrestro_get_session('delivery_latlng')) ? wowrestro_get_session('delivery_latlng') : '';

    $delivery_fee_method = get_option('_wowrestro_delivery_method', 'zip_based');

    $fee = 0;
    $cart_totals      = WC()->cart->get_totals();
    $cart_subtotal    = $cart_totals['subtotal'];

    $free_delivery_amount = get_option('_wowrestro_free_delivery_amount', 0);

    if ($free_delivery_amount > 0 && $cart_subtotal > $free_delivery_amount) {
      $fee = 0;
    }

    if ('location_based' === $delivery_fee_method) {
      if (!empty($delivery_latlng)) {
        $response = $this->get_delivery_fee_by_location($delivery_latlng);
      }
    } else {
      $response = $this->get_delivery_fee_by_zip($zip_code);
    }

    $fee = isset($response['fee']) ? $response['fee'] : 0;
    $min_order_amount = isset($response['min_order_amount']) ? $response['min_order_amount'] : 0;

    if ($cart_subtotal >= $min_order_amount) {
      $fee = 0;
    }

    if (isset($response['status']) &&  $response['status'] == 'success' && $fee > 0) {
      return $fee;
    }

    return $fee;
  }
}

new WWRO_Delivery_Fee();

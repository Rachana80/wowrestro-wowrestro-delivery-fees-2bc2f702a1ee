jQuery(document).ready(function($) {

  // call checkout update cart on billing zip changed
  $(document).on('change', '#billing_postcode', function(event) {
    event.preventDefault();
    // Updating cart
    jQuery(document.body).trigger("update_checkout");
  });
  
  //Setup ajax with our variable when delivery location field is visible
  if ( $('#wowrestro').is( ':visible' ) ) {
    $( document ).ajaxSend(function( ev, xhr, settings ) {
      if ( settings.data ) {
        if( settings.data.indexOf( 'update_service_time') != -1 ) {
          var delivery_zip;
          var delivery_location;
          var delivery_latlng;

          if ( deliveryFeeVars.delivery_fee_method == 'location_based' ) {
            delivery_zip = wowrestro_fee_getCookie( 'delivery_zip' );
          }
          else {
            delivery_zip = $('#wwro_delivery_zone').val();
          }

          settings.data += '&' + $.param({
            delivery_zip      : delivery_zip,
            delivery_location : $('#wwro_delivery_location').val(),
            delivery_latlng   : $('#wwro_delivery_latllng').val(),
          });
        }
      }
    });
  }

  //Set cookie value for delivery location modal is open
  if ( $('#wowrestro').is( ':visible' ) ) {
    getZipCode          = wowrestro_fee_getCookie( 'delivery_zip' );
    getLocationAddress  = wowrestro_fee_getCookie( 'delivery_location' );
    getLocationLatLng   = wowrestro_fee_getCookie( 'delivery_latlng' );

    if ( deliveryFeeVars.delivery_fee_method == 'location_based' ) {

      initDeliveryAddress( 'wwro_delivery_location' );
  
      if ( getLocationAddress !== '' ) {
        var LocationAddress = getLocationAddress.replace(/[+]+/g, " ").trim();
            LocationAddress = LocationAddress.replace(/%2C/g,",");
            LocationAddress = LocationAddress.replace(/%20/g,",");
        $('input#wwro_delivery_location').val( LocationAddress ); 
      }

      if ( getLocationLatLng !== '' ) {
        var LocationLatLng = getLocationLatLng.replace(/[+]+/g, " ").trim();
          LocationLatLng = LocationLatLng.replace(/%2C/g,",");
          LocationLatLng = LocationLatLng.replace(/%20/g,",");
        $('input#wwro_delivery_latllng').val( LocationLatLng );
      }
    }
    else {
      if ( getZipCode !== '' ) {
        var zip = getZipCode.replace(/[+]+/g, " ").trim();
        $('input#wwro_delivery_zone').val( zip );
      }
    }
  }

});

// Set cookie for delivery fee
function wowrestro_fee_setCookie(cname, cvalue, exdays) {
  var d = new Date();
  d.setTime(d.getTime() + (exdays*24*60*60*1000));
  var expires = "expires="+d.toUTCString();
  document.cookie = cname + "=" + cvalue + "; " + expires + ";path=/";
}

// Get cookie value for delivery fee
function wowrestro_fee_getCookie(cname) {
  var name = cname + "=";
  var ca = document.cookie.split(';');
  for(var i=0; i<ca.length; i++) {
    var c = ca[i];
    while (c.charAt(0)==' ') c = c.substring(1);
    if (c.indexOf(name) != -1) return c.substring(name.length,c.length);
  }
  return "";
}

// Deleting cookies value 
const wowrestro_fee_delete_cookie = (cnames = Array()) =>{
  for (var i = 0; i < cnames.length; i++) {
    let cname = cnames[i];
     document.cookie = `${cname}='';expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;` ;
  }
 
}

//Init delivery field
function initDeliveryAddress( $selector ) {
  
  delivery_fee_location = new google.maps.places.Autocomplete(document.getElementById($selector));

  var zip_code = '';
  delivery_fee_location.addListener('place_changed', function() {
   
    let selectedPlace = document.getElementById($selector).value;
    
    if( selectedPlace !== '' ) {
      wowrestro_fee_setCookie( 'delivery_location', selectedPlace, 1 );
    }


    let place = this.getPlace();
    var lat = place.geometry.location.lat();
    var lng = place.geometry.location.lng();

    /**
    *Deleting cookies and flush the previous value
    **/
    wowrestro_fee_delete_cookie(['delivery_zip', 'city', 'street_address', 'flat']);

    jQuery('input#wowrestro-postcode').val('');
    jQuery('input#wowrestro-city').val('');
    jQuery('input#wowrestro-street-address').val('');
    jQuery('input#wowrestro-apt-suite').val('');

    /**
    *Set the cookies  value and input value 
    **/
    for ( var i = 0; i < place.address_components.length; i++ ) {
      var addressType = place.address_components[i].types[0];

      //Set zip code
      if( addressType == 'postal_code' ){
        zip_code = place.address_components[i]['long_name'];
        jQuery('input#wowrestro-postcode').val(zip_code);
        wowrestro_fee_setCookie( 'delivery_zip', zip_code, 1 ); 
      }

      //Set city
      if( addressType == 'locality' ){
        var City = place.address_components[i]['long_name'];
        if ( City !== '' ) {
          jQuery('input#wowrestro-city').val( City );
          wowrestro_fee_setCookie( 'city', City, 1 );
        }
      }

      var streetAddress;
      
      if( addressType == 'route' ){
        streetAddress = place.address_components[i]['long_name'];
        if( typeof streetAddress !== 'undefined' ) {
          jQuery('input#wowrestro-street-address').val( streetAddress );
          wowrestro_fee_setCookie( 'street_address', streetAddress, 1 );
        }
      }


      if( addressType == 'street_number' && streetAddress == '' ){
        streetAddress = place.address_components[i]['long_name'];
        if( typeof streetAddress !== 'undefined' ) {
          jQuery('input#wowrestro-street-address').val( streetAddress );
          wowrestro_fee_setCookie( 'street_address', streetAddress, 1 );
        }
      }
      if( addressType == 'street_number' ){
        streetAddress = place.address_components[i]['long_name'];
        if( typeof streetAddress !== 'undefined' ) {
          jQuery('input#wowrestro-apt-suite').val( streetAddress );
          wowrestro_fee_setCookie( 'flat', streetAddress, 1 );
        }
      }
    }

    var position = lat + ',' + lng;

    if( position !== '' ) {
      jQuery('#wwro_delivery_latllng').val(position);
      wowrestro_fee_setCookie( 'delivery_latlng', position, 1 );
    }

  });

}


// Checkout Getting data from autocomplete field
var autofill, place;

function initAutocomplete() {

  autofill = new google.maps.places.Autocomplete(document.getElementById('wowrestro_checkout_full_address_field'));

  if (deliveryFeeVars.store_country.length > 0 && deliveryFeeVars.store_country !== undefined) {
    autofill.setComponentRestrictions({
      'country': deliveryFeeVars.store_country
    });
  }

  autofill.addListener('place_changed', fillInBillingAddress);
}

//Auto Fill the Billing address
function fillInBillingAddress() {
  place = autofill.getPlace();
  jQuery('#billing_postcode').val('');
  jQuery('#billing_address_2').val('');
  jQuery('#billing_address_1').val('');
  jQuery('#billing_city').val('');

  var lat = place.geometry.location.lat();
  var lng = place.geometry.location.lng();

  var position = lat + ',' + lng;

  if( position !== '' ) {
    jQuery('.wwro_delivery_latllng').val(position);
  }

  const addressComponent = autoFillParseAddress(place.address_components);

  jQuery('#billing_country').val(addressComponent.country);
  jQuery('#billing_country').trigger('change');
  jQuery('#billing_address_1').val(place.name + ', ' +addressComponent.complete_address_1);
  jQuery('#billing_address_2').val(addressComponent.complete_address_2);
  jQuery('#billing_city').val(addressComponent.district);
  jQuery('#billing_postcode').val(addressComponent.postal_code);
  setTimeout(function() {
    jQuery('#billing_state').val(addressComponent.state);
    jQuery('#billing_state').trigger('change');
  }, 1500);

  if (place.hasOwnProperty("international_phone_number") && place.international_phone_number) {
    jQuery('#billing_phone').val(place.international_phone_number);
  }

}

// Checkout autocomplete field get geo location
jQuery(document).on('click', '.wowrestro-get-geo-location', function(event) {
  event.preventDefault();
  /* Act on the event */
  billing_geolocate();
});

// Getting for geolocation support
function billing_geolocate() {
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(billing_geoSuccess, geoError);
  } else {
    alert("Geolocation is not supported by this browser.");
  }
}

// Function for success and getting coordinates
function billing_geoSuccess(position) {
  var lat = position.coords.latitude;
  var lng = position.coords.longitude;
  billing_codeLatLng(lat, lng);
}

// Funtion for error
function geoError() {
  alert( deliveryFeeVars.geo_code_error );
}

//function to parse the autofill value
function autoFillParseAddress(address) {
  let contents = {};
  let address_type = '';
  address.forEach((it, ind) => {
    address_type = it.types[0];
    if (address_type == 'country') {
      contents.country = it.short_name;
      contents.country_long = it.long_name;
    }
    if (address_type == 'premise') {
      contents.premise = it.long_name;
    }
    if (address_type == 'street_number') {
      contents.street_number = it.long_name;
    }
    if (it.types.includes('sublocality')) {
      if (it.types.includes('sublocality_level_1')) {
        contents.sublocality_level_1 = it.long_name;
      } else if (it.types.includes('sublocality_level_2')) {
        contents.sublocality_level_2 = it.long_name;
      } else if (it.types.includes('sublocality_level_3')) {
        contents.sublocality_level_3 = it.long_name;
      } else if (it.types.includes('neighborhood')) {
        contents.neighborhood = it.long_name;
      }
    }
    if (address_type == 'route') {
      contents.route = it.long_name;
    }
    if (address_type == 'administrative_area_level_1') {
      contents.state = it.short_name;
      contents.state_long = it.long_name;
    }
    if (address_type == 'administrative_area_level_2') {
      contents.district = it.long_name;
    }
    if (address_type == 'neighborhood') {
      contents.neighborhood = it.long_name;
    }
    if (address_type == 'locality') {
      contents.city = it.long_name;
    }
    if (address_type == 'postal_code') {
      contents.postal_code = it.long_name;
    }
  });

  let address1 = [];
  if (contents.hasOwnProperty("premise")) {
    address1.push(contents.premise);
  }
  if (contents.hasOwnProperty("street_number")) {
    address1.push(contents.street_number);
  }
  if (contents.hasOwnProperty("neighborhood")) {
    address1.push(contents.neighborhood);
  }
  if (contents.hasOwnProperty("sublocality_level_3")) {
    address1.push(contents.sublocality_level_3);
  }
  if (contents.hasOwnProperty("sublocality_level_2")) {
    address1.push(contents.sublocality_level_2);
  }
  if (contents.hasOwnProperty("sublocality_level_1")) {
    address1.push(contents.sublocality_level_1);
  }

  const complete_address_1 = address1.join(", ");

  let address2 = [];
  if (contents.hasOwnProperty("route")) {
    address2.push(contents.route);
  }
  if (contents.hasOwnProperty("city")) {
    address2.push(contents.city);
  }
  const complete_address_2 = address2.join(", ");

  let returnAddress = {};
  returnAddress.complete_address_1 = complete_address_1;
  returnAddress.complete_address_2 = complete_address_2;
  returnAddress.district = contents.hasOwnProperty("district") ? contents.district : "";
  returnAddress.state = contents.hasOwnProperty("state") ? contents.state : "";
  returnAddress.state_long = contents.hasOwnProperty("state_long") ? contents.state_long : "";
  returnAddress.country = contents.hasOwnProperty("country") ? contents.country : "";
  returnAddress.country_long = contents.hasOwnProperty("country_long") ? contents.country_long : "";
  returnAddress.postal_code = contents.hasOwnProperty("postal_code") ? contents.postal_code : "";
 
  return returnAddress;
}
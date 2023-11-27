function initStoreAddress( $selector ) {
  store_address = new google.maps.places.Autocomplete(document.getElementById($selector));

  store_address.addListener('place_changed', function() {
    
    let selectedPlace = document.getElementById($selector).value;
    document.getElementById('_wowrestro_store_address').value = selectedPlace;
    
    let place = this.getPlace();
    var lat = place.geometry.location.lat();
    var lng = place.geometry.location.lng();

    document.getElementById('_wowrestro_store_latlng').value = lat+','+lng;
  });
}

var geocoder;
var map;
var marker;
var markers = [];
var infowindow;


//Init GoogleMap
function initMap() {

  var lat_postion = Number(wwroDeliveryFee.store_lat_position);

  var lng_position = Number(wwroDeliveryFee.store_lng_position);

  var default_location = { lat: lat_postion, lng: lng_position };

  map = new google.maps.Map(
    document.getElementById('wowrestro_map_canvas'), {zoom: 16, center: default_location }
  );

  infowindow = new google.maps.InfoWindow({ content: '' });

  marker = new google.maps.Marker({
    position    : default_location, 
    map         : map,
    draggable   : true
  });

  markers.push(marker);
  
  google.maps.event.addListener( marker, 'dragend', function (evt) {
    document.getElementById('_wowrestro_store_latlng').value = evt.latLng.lat() + ',' + evt.latLng.lng();;
    geocodePosition( marker.getPosition() );
  });
  
  geocoder = new google.maps.Geocoder();

  document.getElementById('_wowrestro_location_submit').addEventListener('click', function() {
  	geocodeAddress(geocoder, map);
  });
  
}

function geocodePosition(pos) {
  geocoder.geocode( { latLng: pos }, function(responses) {
    if ( responses && responses.length > 0 ) {
      marker.formatted_address = responses[0].formatted_address;
    } 
    else {
      marker.formatted_address = 'Cannot determine address at this location.';
    }

    infowindow.setContent(marker.formatted_address+"<br>coordinates: "+marker.getPosition().toUrlValue(6));
    infowindow.open(map, marker);

  });
}


function geocodeAddress(geocoder, resultsMap) {

  deleteMarkers();

	var address = document.getElementById('_wowrestro_store_address').value;
  
  geocoder.geocode({'address': address}, function(results, status) {
  	if ( status === 'OK' ) {
  		resultsMap.setCenter( results[0].geometry.location );
            
	  	var marker = new google.maps.Marker({
	    	map 			: resultsMap,
	    	position	: results[0].geometry.location,
	    	draggable : true
	  	});

      markers.push(marker);

    	google.maps.event.addListener(marker, 'dragend', function (evt) {
    		document.getElementById('_wowrestro_store_latlng').value = evt.latLng.lat() + ',' + evt.latLng.lng();
        //geocodePosition( marker.getPosition() );
    	});

  	} 
  	else {
  		alert( 'Geocode was not successful for the following reason: ' + status );
  	}
  });

}

// Sets the map on all markers in the array.
function setMapOnAll(map) {
  for (var i = 0; i < markers.length; i++) {
    markers[i].setMap(map);
  }
}

// Removes the markers from the map, but keeps them in the array.
function clearMarkers() {
  setMapOnAll(null);
}

// Shows any markers currently in the array.
function showMarkers() {
  setMapOnAll(map);
}

// Deletes all markers in the array by removing references to them.
function deleteMarkers() {
  clearMarkers();
  markers = [];
}

jQuery( function($) {

  function toggle_method_settings( method ) {
    
    if( method == 'location_based' ) {
      $('.wwro-location-based-options').show();
      $('.wwro-zip-based-options').hide();
      $('.wwro-location-based-options').removeClass('wwro-d-none');
    }
    else {
      $('.wwro-location-based-options').hide();
      $('.wwro-zip-based-options').show();
      $('.wwro-zip-based-options').removeClass('wwro-d-none');
    }
  }

  $('body').on( 'change', '.wowrestro_delivery_method', function() {
    var method = $(this).val();
    toggle_method_settings(method);
  });

  var selected_method = wwroDeliveryFee.delivery_fee_method;

  toggle_method_settings( selected_method );

  if ( jQuery('#_wowrestro_store_address').is(':visible') ) {
    initStoreAddress('_wowrestro_store_address');
  }

  $( 'body' ).on( 'click', '.add-delivery-location', function(e) {
    e.preventDefault();
    var unix_time = Date.now();

    var CustomHtml = '<tr data-row-id="'+unix_time+'">';
        CustomHtml += '<td>';
        CustomHtml += '<input type="text" name="_wowrestro_location_delivery_fee[delivery_location_fee]['+unix_time+'][fee_amount]">';
        CustomHtml += '</td>';
        CustomHtml += '<td>';
        CustomHtml += '<input type="text" name="_wowrestro_location_delivery_fee[delivery_location_fee]['+unix_time+'][distance]" placeholder="'+wwroDeliveryFee.distance_example+'">';
        CustomHtml += '</td>';
          CustomHtml += '<td>';
          CustomHtml += '<input type="text" name="_wowrestro_location_delivery_fee[delivery_location_fee]['+unix_time+'][order_amount]">';
          CustomHtml += '</td>';
          CustomHtml += '<td>';
          CustomHtml += '<input type="text" name="_wowrestro_location_delivery_fee[delivery_location_fee]['+unix_time+'][set_min_order_amount]">';
          CustomHtml += '</td>';
        CustomHtml += '<td>';
        CustomHtml += '<a href="void(0)" data-row-id="'+unix_time+'" class="wwro-delivery-fee-remove"></a>';
        CustomHtml += '</td>';
        CustomHtml += '</tr>';


    var Selected = $(this);

    var tableBody = Selected.parents('table#wowrestro_delivery_location_fees').find('tbody');

    if ( tableBody.children().length == 0 ) {
      $( tableBody ).append( CustomHtml );
    }
    else {
      Selected.parents('table#wowrestro_delivery_location_fees').find('tbody tr').last().after( CustomHtml );
    }

  });

  $( 'body' ).on( 'click', '.wowrestro-add-delivery-fee-data', function(e) {

    e.preventDefault();

    var unix_time = Date.now();

    var CustomHtml = '<tr data-row-id="'+unix_time+'">';
        CustomHtml += '<td>';
        CustomHtml += '<input type="text" name="_wowrestro_delivery_fee[delivery_fee]['+unix_time+'][fee_amount]">';
        CustomHtml += '</td>';
        CustomHtml += '<td>';
        CustomHtml += '<input type="text" name="_wowrestro_delivery_fee[delivery_fee]['+unix_time+'][zip_code]">';
        CustomHtml += '</td>';
            CustomHtml += '<td>';
            CustomHtml += '<input type="text" name="_wowrestro_delivery_fee[delivery_fee]['+unix_time+'][order_amount]">';
            CustomHtml += '</td>';
            CustomHtml += '<td>';
            CustomHtml += '<input type="text" name="_wowrestro_delivery_fee[delivery_fee]['+unix_time+'][set_min_order_amount]">';
            CustomHtml += '</td>';
        CustomHtml += '<td>';
        CustomHtml += '<a href="void(0)" data-row-id="'+unix_time+'" class="wwro-delivery-fee-remove"></a>';
        CustomHtml += '</td>';
        CustomHtml += '</tr>';


    var Selected = $(this);

    var tableBody = Selected.parents('table#wowrestro_delivery_fees').find('tbody');

    if ( tableBody.children().length == 0 ) {
      $( tableBody ).append( CustomHtml );
    }
    else {
      Selected.parents('table#wowrestro_delivery_fees').find('tbody tr').last().after( CustomHtml );
    }

  });

  $('table#wowrestro_delivery_fees tr').each( function() {
    $( 'body' ).on( 'click', '.wwro-delivery-fee-remove',  function(e) {
      e.preventDefault();
      $(this).parent().parent().remove();
    });
  })

} );
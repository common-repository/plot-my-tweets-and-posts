jQuery(document).ready(function($) {
	plotmypostsandtweets.filter_init();
});

var plotmypostsandtweets = {
	previous_location:'',
	map:null,
	marker:null,
	
	save_item:function(item_type){
		
		data = jQuery('#edit_tweet').serialize();
		
		jQuery.getJSON(ajaxurl+'?action=save_'+item_type, data, function(response) {
			if (response.status=='-1') {
				alert('Error saving item: '+response.status);
				return;
			}
			
			id = response.id;
			html = response.html;
			
			jQuery('#tweetrow'+id).html(html);
			tb_remove();
			
		});
	},
	
	location_focus:function(){
		plotmypostsandtweets.previous_location = jQuery('#txt_location').val();
	},
	
	location_blur:function(){
		var place = jQuery('#txt_location').val();
		
		// initialise map
		if (plotmypostsandtweets.map==null) { plotmypostsandtweets.loadmap(); }
		
		// no change
		if (place == plotmypostsandtweets.previous_location && jQuery('#txt_lat').val()!='' && jQuery('#txt_lon').val()!='') { return false; }
		
		// no place
		if (place=='') {
			jQuery('#txt_lat').val('');
			jQuery('#txt_lon').val('');
			return false;
		}
		
		place = ucwords(place);
		jQuery('#txt_location').val(place);
		plotmypostsandtweets.lookup_location(place);

	},
	
	lookup_location:function(place){
		var url = 'http://maps.googleapis.com/maps/api/geocode/json?address='+place+'&sensor=false';
		
		var geocoder = new google.maps.Geocoder();
		geocoder.geocode( { 'address': place}, function(results, status) {
			if (status == google.maps.GeocoderStatus.OK) {
				
				var latLon = results[0].geometry.location;
				
				plotmypostsandtweets.addMarker(place, latLon);

				plotmypostsandtweets.updateLatLon();
				
			} else {
				alert("Unable to find address");
			}
		});

	},
	
	loadmap:function(){
		var lat = jQuery('#txt_lat').val();
		var lon = jQuery('#txt_lon').val();
		var latLon = new google.maps.LatLng(lat, lon);
		
		var myOptions = {
			center: latLon,
			zoom: 8,
			mapTypeId: google.maps.MapTypeId.ROADMAP
		};
		
        plotmypostsandtweets.map = new google.maps.Map(document.getElementById("plotmypostsandtweets_canvas"), myOptions);
		
		if (jQuery('#txt_location').val()!='' || (lat!='' && lon!='')) {
			plotmypostsandtweets.addMarker(jQuery('#txt_location').val(), latLon);
		}
		
		if (lat=='' && lon=='' && jQuery('#txt_location').val()!='') {
			plotmypostsandtweets.lookup_location(jQuery('#txt_location').val());
		}
	},
	
	updateLatLon:function(){
		jQuery('#txt_lat').val(plotmypostsandtweets.marker.position.lat());
		jQuery('#txt_lon').val(plotmypostsandtweets.marker.position.lng());
		jQuery('#chk_show').prop("checked", true);
	},
	
	addMarker:function(place, latLon){
		// remove previous marker
		if (plotmypostsandtweets.marker!=null) {
			plotmypostsandtweets.marker.setMap(null);
		}
				
		// drop pin
		plotmypostsandtweets.marker = new google.maps.Marker({
			position: latLon, 
			map: plotmypostsandtweets.map,
			draggable:true,
			title:place
		});
		
		// add pin drag listener
		google.maps.event.addListener(
			plotmypostsandtweets.marker,
			'drag',
			function() {
				plotmypostsandtweets.updateLatLon();
			}
		);
		
		// pan to pin
		plotmypostsandtweets.map.panTo(latLon);
	},
	
	filter_init:function(){

		if (jQuery('ul#plotmypostsandtweets_filter').length == 0) { return; }	
		
		jQuery('ul#plotmypostsandtweets_filter a').click(function(){
			rel = jQuery(this).attr('rel');
			jQuery('ul#plotmypostsandtweets_filter a').removeClass('current');
			jQuery(this).addClass('current');

			if (rel=='everything') { 
				jQuery('table#plotmypostsandtweets_list tbody tr').fadeIn();
				return false; 
			}
			jQuery('table#plotmypostsandtweets_list tbody tr').css('display', 'none');
			jQuery('table#plotmypostsandtweets_list tbody tr.'+rel).fadeIn();
			
			return false;
		});
	}
}

function ucwords (str) {
    return (str + '').replace(/^([a-z])|\s+([a-z])/g, function ($1) {
        return $1.toUpperCase();
    });
}

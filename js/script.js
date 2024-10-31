jQuery(document).ready(function(){
	plotmypostsandtweets.init();
});

var plotmypostsandtweets = {
	map:null,
	
	init:function(){
		if (jQuery('#plotmypostsandtweets_canvas').length == 0) { return false; }
		
		// create map		
		var myOptions = {
			center: new google.maps.LatLng(0,0),
			zoom: 8,
			mapTypeId: google.maps.MapTypeId.ROADMAP
		};
        plotmypostsandtweets.map = new google.maps.Map(document.getElementById("plotmypostsandtweets_canvas"), myOptions);
		
		// check if any points have been set in body
		if (!plotmypostsandtweets_points) { return; }
		
		var bounds = new google.maps.LatLngBounds();
		var checkpoints = new Array();
		var markers = new Array();
		var infoWindow = new google.maps.InfoWindow;
		var icon_tweet = new google.maps.MarkerImage(plotmypostsandtweets_dir+'images/pin_tweet_small.png',
      											new google.maps.Size(18, 16),
      											new google.maps.Point(0,0),
     											new google.maps.Point(5, 16));
		var icon_twitpic = new google.maps.MarkerImage(plotmypostsandtweets_dir+'images/pin_twitpic_small.png',
      											new google.maps.Size(18, 16),
      											new google.maps.Point(0,0),
     											new google.maps.Point(5, 16));
		var icon_post = new google.maps.MarkerImage(plotmypostsandtweets_dir+'images/pin_post_small.png',
      											new google.maps.Size(18, 16),
      											new google.maps.Point(0,0),
     											new google.maps.Point(5, 16));
  		var shadow = new google.maps.MarkerImage(plotmypostsandtweets_dir+'images/pin_shadow_small.png',
												new google.maps.Size(18, 16),
												new google.maps.Point(0,0),
												new google.maps.Point(5, 16));
		var shape = {
		  coord: [1, 1, 1, 16, 18, 16, 18 , 1],
		  type: 'poly'
		};

		for (i in plotmypostsandtweets_points) {
			point = plotmypostsandtweets_points[i];
			if (point.lat!=-1 && point.lon!=-1) {
				checkpoint = new google.maps.LatLng(parseFloat(point.lat), parseFloat(point.lon));
				checkpoints.push(checkpoint);
				bounds.extend(checkpoint);

				title = point.text;
				html = '<div class="plotmypostsandtweets_popup" >';
				if (point.image!='') {
					html += '<a href="'+point.image+'" class="thickbox">';
					html += '<img src="'+plotmypostsandtweets_dir+'timthumb.php?w=100&h=100&zc=1&src='+point.image+'" alt="Loading image..." />';
					html += '</a>';
				}
				cssclass = point.image==''?'':' class="with_img"';
				html += '<b'+cssclass+'>'+addslashes(title)+'</b>';
				html += '<p>';
				html += 'Posted '+point.date+' &nbsp; | &nbsp; ';
				
				if (point.type=='post') {
					html += '<a href="'+point.link+'">Read More</a>';
				}
				else if (point.image!='') {
					html += '<a href="'+point.image+'" target="_blank" class="thickbox">View image</a>';
				}
				else {
					html += '<a href="http://twitter.com/#!/'+plotmypostsandtweets_twitter_name+'" target="_blank">See more tweets</a>';
				};
				
				html += '</p>';
				html += '</div>';

				if (point.type=='tweet' && point.image!='') { icon = icon_twitpic; }
				else if (point.type=='tweet') { icon = icon_tweet; }
				else { icon = icon_post; };
				
			 	marker = new google.maps.Marker({
						map: plotmypostsandtweets.map,
						title: title,
						position: checkpoint,
						shadow:shadow,
						icon:icon,
						shape:shape
				});
				markers.push(marker);
				plotmypostsandtweets.bindInfoWindow(marker, infoWindow, html);
			}
		}
		
		plotmypostsandtweets.map.fitBounds(bounds);
		plotmypostsandtweets.map.setCenter(bounds.getCenter());
		
		if (plotmypostsandtweets_joined_up) {
			var route = new google.maps.Polyline({
						path: checkpoints,
						strokeColor: plotmypostsandtweets_line_colour,
						strokeOpacity: 0.9,
						strokeWeight: 2
			});
			route.setMap(plotmypostsandtweets.map);
		}
		
		if (plotmypostsandtweets_marker_cluster) {  
			mc = new MarkerClusterer(plotmypostsandtweets.map, markers);
		}
		 
	},
	
	bindInfoWindow:function(marker, infoWindow, html) {
        google.maps.event.addListener(marker, "click", function() {
			infoWindow.setContent(html);
			infoWindow.open(plotmypostsandtweets.map, marker);
        });
   }
}

function addslashes(str){
	return str.replace(/\"/, '\"');
}
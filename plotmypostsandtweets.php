<?php
/*
Plugin Name: Plot My Posts and Tweets
Plugin URI: http://sandjam.co.uk/sandjam/2012/03/plot-my-posts-and-tweets/
Description: Create a map page with your tweets and posts plotted across it
Version: 1.1
Author: Peter Smith
Author URI: http://www.sandjam.co.uk
License: GPL2
Installation:
Place this file in your /wp-content/plugins/ directory, then activate through the administration panel. 
*/

define('DATEFORMAT','d/m/y H:i');
define('GOOGLE_MAPS_URL', 'http://maps.googleapis.com/maps/api/js?v=3.5&sensor=false');
 
// ------------------------------------------------------------
// INSTALLATION
// ------------------------------------------------------------

register_activation_hook(__FILE__, 'install');
register_uninstall_hook( __FILE__, 'uninstall');

function install() {
   global $wpdb;

   $table_name = $wpdb->prefix . "plotmypostsandtweets";
	  
   $sql = "CREATE TABLE `$table_name` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `twitter_id` varchar(50) NOT NULL,
  `tweet_xml` text NOT NULL,
  `title` varchar(255) NOT NULL,
  `date` datetime NOT NULL,
  `text` varchar(255) NOT NULL,
  `image` varchar(255) NOT NULL,
  `lat` varchar(20) NOT NULL,
  `lon` varchar(20) NOT NULL,
  `location` varchar(100) NOT NULL,
  `show` tinyint(4) NOT NULL,
  KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

   require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
   dbDelta($sql);
   
   // set initial options values
   $options = array(
					'twitter_name'=>'hittheroadtweet',
					'map_width'=>'600',
					'map_height'=>'400',
					'line_colour'=>'#FF0000',
					'joined_up'=>'1',
					'key'=>'',
					'tweets_from'=>'',
					'tweets_to'=>'',
					'num_tweets'=>10,
					'marker_cluster'=>'0'
					);
   add_option('pmpat_options', $options);
   add_option('pmpat_hash', '0');
}

function uninstall(){
	global $wpdb;
	
	$table_name = $wpdb->prefix . "plotmypostsandtweets";
	
	$wpdb->query("DROP TABLE IF EXISTS $table_name");
	
	delete_option('pmpat_options');
}

// ------------------------------------------------------------
// ADMIN OPTIONS PAGES 
// ------------------------------------------------------------

// add the admin options page
add_action('admin_menu', 'pmpat_admin_add_page');
function pmpat_admin_add_page() {
	add_options_page('Tweet Map Settings', 'Posts/Tweets Map', 'manage_options', 'plotmypostsandtweets_settings', 'plotmypostsandtweets_settings_page');
	add_submenu_page('edit.php', 'Tweet Map Posts', 'Posts/Tweets Map', 'manage_options', 'plotmypostsandtweets', 'pmpat_posts_page');
}

// list posts and tweets under Posts menu
function pmpat_posts_page() {
	?>
	<div>
	<h2>Your Tweets and Posts</h2>
	<?php
	$tweets = get_cached_tweets();
	$posts = get_location_posts();
	
	$combined = combine($tweets, $posts);
	
	if (sizeof($combined)==0) { echo 'No tweets or posts have been found yet. Make sure you have set up the plugin in <a href="options-general.php?page=plotmypostsandtweets_settings">Settings</a>'; }
	else {
		?>
		<ul class="subsubsub" id="plotmypostsandtweets_filter">
			<li>View:</li>
			<li><a class="current" rel="everything" href="#" >Everything</a> | </li>
			<li><a class="" rel="tweet" href="#">Tweets</a> | </li>
			<li><a class="" rel="post" href="#">Posts</a></li>
		</ul>
		
		<table id="plotmypostsandtweets_list" class="wp-list-table widefat" cellspacing="0">
			<thead>
			<tr>
				<th>&nbsp;</th>
				<th>Title</th>
				<th width="85px">Date</th>
				<th>Location</th>
				<th>On Map</th>
				<th>&nbsp;</th>
			</tr>
			</thead>
			<tbody>
			<?php
			foreach ($combined as $item) {
				echo '<tr class="'.$item->type.'" id="tweetrow'.$item->id.'">';
				echo item_row_html($item);
				echo '</tr>';
			}
			?>
			</tbody>
		</table>
		<?php
	};
}

// display the admin options page
function plotmypostsandtweets_settings_page() {
	$options = get_option('pmpat_options');
	$hash = get_option('pmpat_hash');
	
	// force update of tweets
	if (isset($_GET['fetchtweets'])) {
		$page = isset($_GET['pg'])?$_GET['pg']:-1;
		$response = check_tweets(100, true, $page);
		if ($response) {
			?>
			<div class="updated"><p><strong>Tweets have been updated</strong></p></div>
			<?php
		}
	}
	
	// delete tweets
	if (isset($_GET['deletetweets'])) {
		delete_all_tweets();
		?><div class="updated"><p><strong>Locally cached tweets have been deleted</strong></p></div><?php
	}
	
	// validate key
	if ($options['key'] != $options['previous_key']) {
		$url = 'http://sandjam.co.uk/register/?product=plotmyposts&key='.$options['key'].'&domain='.urlencode($_SERVER['HTTP_HOST']);
		$response = @file_get_contents($url);
		if (!$response) {
			?><div class="error"><p><strong>Error checking registration key</strong></p></div><?php
			$hash = '0';
		}
		if ($response!='ok') {
			?><div class="error"><p><strong>Error: Registration key does not seem to be valid</strong></p></div><?php
			$hash = '0';
		}
		else {
			?><div class="updated"><p><strong>Registration key is valid</strong></p></div><?php
			$hash = 'yes';
		};
		
	}
	$options['previous_key'] = $options['key'];
	update_option('pmpat_options', $options);
	update_option('pmpat_hash', $hash);
	?>
	<div>
		<h2>Plot My Posts and Tweets Settings</h2>
		
		<p>Plot your blog posts and/or tweets on a Google Map in date order with a path tracing your route.</p>
		<h3>Tips</h3>
		<ol>
			<li>Use the shorttag [plotmypostsandtweets] in the page you would like the map to appear in</li>
			<li>Turn on geo-tagging in your twitter account to automatically place your tweets</li>
			<li>Alternatively, end your post with "at location" e.g. "Visiting John today at San Francisco, CA" or enter your location in square brackets e.g. "Visiting John today. [San Francisco, CA]"</li>
			<li>Add a custom field called 'Location' to the posts you would like to appear on the map, and enter the town or city you are posting from</li>
			<li>You can amend the locations of any tweets and posts from the Tweet Map page in the 'Posts' menu</li>
			<li>The 10 most recent Tweets are included by default. If you donate (see below) then you can change this option.</li>
		</ol>
		
		<form action="options.php" method="post">
			<?php settings_fields('pmpat_options'); ?>
			<?php do_settings_sections('plotmypostsandtweets'); ?>
			<p><input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes'); ?>" /></p>
		</form>
		
		<p>&nbsp;</p>
		
		<p>
			<?php
				if (!isset($options['last_updated']) || $options['last_updated']=='') { echo 'Tweets not yet cached'; }
				else { echo 'Tweets last updated '.date(DATEFORMAT, $options['last_updated']); };
			?>. <a href="?page=plotmypostsandtweets_settings&fetchtweets">Update Now</a><br />
			<a href="?page=plotmypostsandtweets_settings&fetchtweets&pg=2">Fetch older tweets</a> <a href="?page=plotmypostsandtweets_settings&fetchtweets&pg=3">Fetch even older tweets</a>
		</p>
		<p>
			<a href="?page=plotmypostsandtweets_settings&deletetweets" class="button" onclick="return(confirm('Are you sure you want to delete all cached tweets? Any amendments you have made to them in the Wordpress admin area will be lost.'))">Delete all cached tweets</a>
		</p>
	</div>
	<?php
}

// generate html for item table row
function item_row_html($item){
	$html = '';
	$html .= '<td><img src="'.plugin_dir_url( __FILE__ ).'images/icon_'.$item->type.'.png" alt="'.$item->type.'"/></td>';
	$html .= '<td>'.$item->text.'</td>';
	$html .= '<td>'.date(DATEFORMAT, strtotime($item->date)).'</td>';
	$html .= '<td>'.$item->location.'</td>';
	$html .= '<td>'.($item->show?'Yes':'No').'</td>';
	$html .= '<td><a class="thickbox" href="admin-ajax.php?action=edit_'.$item->type.'&id='.$item->id.'&height=500&width=600" title="Edit '.ucfirst($item->type).'">Edit</a></td>';
	return $html;
}

// add the admin settings and such
add_action('admin_init', 'pmpat_admin_init');
function pmpat_admin_init(){
	register_setting('pmpat_options', 'pmpat_options', 'pmpat_options_validate' );
	add_settings_section('pmpat_twitter', 'Twitter', 'pmpat_twitter_section_text', 'plotmypostsandtweets');
	add_settings_section('pmpat_map', 'Map Display', 'pmpat_map_section_text', 'plotmypostsandtweets');
	add_settings_section('pmpat_register', 'Register', 'pmpat_register_text', 'plotmypostsandtweets');
	
	add_settings_field('twitter_name', 'Twitter username @', 'twitter_name_setting', 'plotmypostsandtweets', 'pmpat_twitter');
	add_settings_field('tweets_from', 'Only show tweets since (dd/mm/yy)', 'tweets_from_setting', 'plotmypostsandtweets', 'pmpat_twitter');
	add_settings_field('tweets_to', 'Only show tweets until (dd/mm/yy)', 'tweets_to_setting', 'plotmypostsandtweets', 'pmpat_twitter');
	add_settings_field('num_tweets', 'Only show this many recent tweets', 'num_tweets_setting', 'plotmypostsandtweets', 'pmpat_twitter');
	
	add_settings_field('map_width', 'Map width (px)', 'map_width_setting', 'plotmypostsandtweets', 'pmpat_map');
	add_settings_field('map_height', 'Map height (px)', 'map_height_setting', 'plotmypostsandtweets', 'pmpat_map');
	add_settings_field('line_colour', 'Map line colour (hex)', 'map_line_colour', 'plotmypostsandtweets', 'pmpat_map');
	add_settings_field('marker_cluster', 'Cluster map markers', 'map_marker_cluster', 'plotmypostsandtweets', 'pmpat_map');
	
	add_settings_field('joined_up', 'Join map markers with a line?', 'map_joined_up', 'plotmypostsandtweets', 'pmpat_map');
	add_settings_field('key', 'Registration key:', 'map_key', 'plotmypostsandtweets', 'pmpat_register');
	add_settings_field('previous_key', '', 'map_previous_key', 'plotmypostsandtweets', 'pmpat_register');
	
	// include thickbox styles and scripts
	wp_enqueue_style('thickbox');
	wp_enqueue_script('thickbox', '', 'jquery');
	
	// include plugin admin css and js
	wp_enqueue_style('pluginadmin', plugin_dir_url( __FILE__ ) . 'css/admin.css');
	wp_enqueue_script('my-ajax-request', plugin_dir_url( __FILE__ ) . 'js/ajax.js');
	
	// include google maps
	wp_enqueue_script('googlemaps', GOOGLE_MAPS_URL);
}	

// admin settings section callback
function pmpat_twitter_section_text() {
	echo '';
}
function pmpat_map_section_text() {
	echo '';
}
function pmpat_register_text() {
	echo 'If you <a href="http://sandjam.co.uk/sandjam/2012/03/plot-my-posts-and-tweets/">donate &pound;5 for this plugin</a>, the plugin title below the map will be removed and you can use the advanced Twitter options above. Once you have donated, you will receive a registration key which you can enter here:';
}

// Render twitter name field
function twitter_name_setting() {
	$options = get_option('pmpat_options');
	$hash = get_option('pmpat_hash');
	echo "<input id='twitter_name' name='pmpat_options[twitter_name]' size='40' type='text' value='{$options['twitter_name']}' />";
	if ($hash!='yes') {
		echo '<br />Once you have donated, these options will be available:';
	}
}
function tweets_from_setting() {
	$options = get_option('pmpat_options');
	$hash = get_option('pmpat_hash');
	if ($hash=='yes'){
		echo "<input id='tweets_from' name='pmpat_options[tweets_from]' size='20' type='text' value='{$options['tweets_from']}'  />";
	}
	else {
		echo "<input id='tweets_from' name='pmpat_options[tweets_from]' size='20' type='hidden' value='{$options['tweets_from']}'  />";
		echo $options['tweets_from'];
	};
}
function tweets_to_setting() {
	$options = get_option('pmpat_options');
	$hash = get_option('pmpat_hash');
	if ($hash=='yes'){
		echo "<input id='tweets_to' name='pmpat_options[tweets_to]' size='20' type='text' value='{$options['tweets_to']}' $locked  />";
	}
	else {
		echo "<input id='tweets_to' name='pmpat_options[tweets_to]' size='20' type='hidden' value='{$options['tweets_to']}' $locked  />";
		echo $options['tweets_to'];
	};
}
function num_tweets_setting() {
	$options = get_option('pmpat_options');
	$hash = get_option('pmpat_hash');
	if ($hash=='yes'){
		echo "<input id='num_tweets' name='pmpat_options[num_tweets]' size='8' type='text' value='{$options['num_tweets']}' />";
	}
	else {
		echo "<input id='num_tweets' name='pmpat_options[num_tweets]' size='8' type='hidden' value='{$options['num_tweets']}' />";
		echo $options['num_tweets'];
	};
}
function map_width_setting() {
	$options = get_option('pmpat_options');
	echo "<input id='map_width' name='pmpat_options[map_width]' size='8' type='text' value='{$options['map_width']}' />";
}
function map_height_setting() {
	$options = get_option('pmpat_options');
	echo "<input id='map_height' name='pmpat_options[map_height]' size='8' type='text' value='{$options['map_height']}' />";
}
function map_line_colour() {
	$options = get_option('pmpat_options');
	echo "<input id='line_colour' name='pmpat_options[line_colour]' size='10' type='text' value='{$options['line_colour']}' />";
}
function map_joined_up() {
	$options = get_option('pmpat_options');
	echo '<input name="pmpat_options[joined_up]" id="joined_up" type="checkbox" value="1" class="code" ' . checked( 1, $options['joined_up'], false ) . ' />';
}
function map_marker_cluster() {
	$options = get_option('pmpat_options');
	echo '<input name="pmpat_options[marker_cluster]" id="marker_cluster" type="checkbox" value="1" class="code" ' . checked( 1, $options['marker_cluster'], false ) . ' />';
}
function map_key() {
	$options = get_option('pmpat_options');
	echo "<input id='key' name='pmpat_options[key]' size='80' type='text' value='{$options['key']}' />";
}
function map_previous_key() {
	$options = get_option('pmpat_options');
	echo "<input id='previous_key' name='pmpat_options[previous_key]' type='hidden' value='{$options['key']}' />";
}

// validate our options
function pmpat_options_validate($input) {
	$newinput['twitter_name'] = trim($input['twitter_name']);
	return $input;
}

// ------------------------------------------------------------
// EDIT TWEET AJAX DIALOG
// ------------------------------------------------------------

function edit_tweet_dialog(){
	//check_ajax_referer('crop_apply_header_image', 'security'); 
	
	$id = (int) $_GET['id'];
	if ($id==0) { die('Error: Tweet id not found'); }
	
	// fetch tweet
	$item = get_cached_tweets($id);
	if (!$item) { die('Error: Tweet not found'); }
	?>
	<form action="" method="POST" id="edit_tweet">
		<ul>
			<li class="hdn_id">
				<input type="hidden" name="hdn_id" id="hdn_id" value="<?=$item->id?>" />
			</li>
			<li class="txt_tweet">
				<label for="txt_tweet">Tweet</label>
				<input type="text" name="txt_tweet" id="txt_tweet" value="<?=stripslashes($item->text)?>" />
			</li>
			<li class="txt_date">
				<label for="txt_date">Date</label>
				<input type="text" name="txt_date" id="txt_date" value="<?=stripslashes($item->date)?>" />
			</li>
			<li class="txt_image">
				<label for="txt_image">Image</label>
				<input type="text" name="txt_image" id="txt_image" value="<?=stripslashes($item->image)?>" />
				<?php
				if ($item->image!='') { echo '<img src="'.plugin_dir_url( __FILE__ ).'timthumb.php?zc=1&w=50&h=50&src='.urlencode($item->image).'" alt="Loading image..."/>'; }
				?>
			</li>
			<li class="txt_location">
				<label for="txt_location">Location</label>
				<input type="text" name="txt_location" id="txt_location" value="<?=stripslashes($item->location)?>" onfocus="plotmypostsandtweets.location_focus();" onblur="plotmypostsandtweets.location_blur();" />
				<a href="#" onclick="plotmypostsandtweets.loadmap(); return false;">View Map</a>
			</li>
			<li class="plotmypostsandtweets_canvas">
				<div id="plotmypostsandtweets_canvas"></div>
			</li>
			<li class="txt_lat">
				<label for="txt_lat">Lat</label>
				<input type="text" name="txt_lat" id="txt_lat" value="<?=$item->lat?>" />
			</li>
			<li class="txt_lon">
				<label for="txt_lon">Lon</label>
				<input type="text" name="txt_lon" id="txt_lon" value="<?=$item->lon?>" />
			</li>
			<li class="chk_show">
				<label for="chk_show">Show on Map</label>
				<input type="checkbox" name="chk_show" id="chk_show" value="1" <?=$item->show?'checked="checked"':''?> />
			</li>
			<li class="btn_cancel">
				<input type="button" class="button-primary" onclick="plotmypostsandtweets.save_item('tweet');" name="btn_savetweet" id="btn_savetweet" value="Save Changes" />
				<input type="button" class="button" onclick="tb_remove();"  name="btn_cancel" id="btn_cancel" value="Cancel" />
			</li>
		</ul>
	</form>
	<?php
	die();
}
add_action('wp_ajax_edit_tweet', 'edit_tweet_dialog');

// ajax call to save tweet
function save_tweet_callback(){
	global $wpdb;
	$table =  $wpdb->prefix . "plotmypostsandtweets";
	
	$id = $_GET['hdn_id'];
	
	foreach($_GET as $k=>$v){
		$_GET[$k] = stripslashes($v);	
	}
	
	$data = array(
		'text' => $_GET['txt_tweet'],
		'date' 	=> date("Y:m:d H:i:s", strtotime($_GET['txt_date'])),
		'image' => $_GET['txt_image'],
		'location' => $_GET['txt_location'],
		'lat' 	=> $_GET['txt_lat'],
		'lon' 	=> $_GET['txt_lon'],
		'show' 	=> $_GET['chk_show']
		);
	
	$where = array('id'=>$id);
	
	$response = new stdClass;
	$wpdb->update($table, $data, $where);
	$response->status = 1;
	$response->id = $id;
	
	$item = get_cached_tweets($id);
	
	if (!$item) { $response->status = -1; }
	else {
		$response->html = item_row_html($item);
	};
	
	echo json_encode($response);
	
	die();
}
add_action('wp_ajax_save_tweet', 'save_tweet_callback');

// ------------------------------------------------------------
// EDIT POST AJAX DIALOG
// ------------------------------------------------------------

function edit_post_dialog(){
	//check_ajax_referer('crop_apply_header_image', 'security'); 
	
	$id = (int) $_GET['id'];
	if ($id==0) { die('Error: id not found'); }
	
	// fetch tweet
	$item = get_location_posts($id);
	if (!$item) { die('Error: Post not found'); }
	?>
	<form action="" method="POST" id="edit_tweet">
		<ul>
			<li class="hdn_id">
				<input type="hidden" name="hdn_id" id="hdn_id" value="<?=$item->id?>" />
			</li>
			<li class="txt_title">
				Title: <?=$item->title?>
			</li>
			<li class="txt_date">
				Date: <?=$item->date?>
			</li>
			<li class="txt_location">
				<label for="txt_location">Location</label>
				<input type="text" name="txt_location" id="txt_location" value="<?=stripslashes($item->location)?>" onfocus="plotmypostsandtweets.location_focus();" onblur="plotmypostsandtweets.location_blur();" />
				<a href="#" onclick="plotmypostsandtweets.loadmap(); return false;">View Map</a>
			</li>
			<li class="plotmypostsandtweets_canvas">
				<div id="plotmypostsandtweets_canvas"></div>
			</li>
			<li class="txt_lat">
				<label for="txt_lat">Lat</label>
				<input type="text" name="txt_lat" id="txt_lat" value="<?=$item->lat?>" />
			</li>
			<li class="txt_lon">
				<label for="txt_lon">Lon</label>
				<input type="text" name="txt_lon" id="txt_lon" value="<?=$item->lon?>" />
			</li>
			<li class="chk_show">
				<label for="chk_show">Show on Map</label>
				<input type="checkbox" name="chk_show" id="chk_show" value="1" <?=$item->show?'checked="checked"':''?> />
			</li>
			<li class="btn_cancel">
				<input type="button" class="button-primary" onclick="plotmypostsandtweets.save_item('post');" name="btn_savetweet" id="btn_savetweet" value="Save Changes" />
				<input type="button" class="button" onclick="tb_remove();"  name="btn_cancel" id="btn_cancel" value="Cancel" />
			</li>
		</ul>
	</form>
	<?php
	die();
}
add_action('wp_ajax_edit_post', 'edit_post_dialog');

// ajax call to save post
function save_post_callback(){
	
	$id = (int) $_GET['hdn_id'];
	
	foreach($_GET as $k=>$v){
		$_GET[$k] = stripslashes($v);	
	}
	
	update_post_meta($id, 'location', $_GET['txt_location']);
	update_post_meta($id, 'lat', $_GET['txt_lat']);
	update_post_meta($id, 'lon', $_GET['txt_lon']);
	update_post_meta($id, 'show', $_GET['chk_show']);

	$response = new stdClass;
	$response->status = 1;
	$response->id = $id;
	
	$item = get_location_posts($id);
	
	if (!$item) { $response->status = -1; }
	else {
		$response->html = item_row_html($item);
	};
	
	echo json_encode($response);
	die();
}
add_action('wp_ajax_save_post', 'save_post_callback');

// ------------------------------------------------------------
// GEOCODE POSTS WHEN SAVED WITH THE CUSTOM FIELD 'LOCATION'
// ------------------------------------------------------------

function geocode_saved_post($id) {
	if (wp_is_post_revision($id)) { return; }

	$location = get_post_meta($id, 'location', true); 

	if ($location=='') {
		delete_post_meta($id, 'location');
		delete_post_meta($id, 'lat');
		delete_post_meta($id, 'lon');
		delete_post_meta($id, 'show');
		return;
	}
	
	// already geocoded
	$lat = get_post_meta($id, 'lat', true); 
	$lon = get_post_meta($id, 'lon', true); 
	if ($lat!='' && $lon!='') {
		return;
	}
	
	// attempt to geocode location
	$geo = geocode($location);
	$lat = $geo['lat'];
	$lon = $geo['lon'];
	
	// geocode failed
	if ($lat==-1 || $lon==-1) {
		return;
	}
	
	update_post_meta($id, 'lat', $lat);
	update_post_meta($id, 'lon', $lon);
	update_post_meta($id, 'show', '1');
}
add_action('save_post', 'geocode_saved_post');

// ------------------------------------------------------------
// USE TAG TO RENDER MAP ON PAGE 
// ------------------------------------------------------------

add_shortcode('plotmypostsandtweets', 'plotmypostsandtweets_show_map');

function plotmypostsandtweets_show_map() {
	$options = get_option('pmpat_options');
	
	// load jquery
	wp_enqueue_script('jquery');
	
	wp_enqueue_style('thickbox');
	wp_enqueue_script('thickbox');
	
	// load google maps and markercluster
	wp_enqueue_script('googlemaps', GOOGLE_MAPS_URL);
	if ($options['marker_cluster']) {
		wp_enqueue_script('markercluster', plugin_dir_url( __FILE__ ) . 'js/markerclusterer.js');
	}
	
	// load plugin scripts and style
	wp_enqueue_style('plotmypostsandtweets_css_front', plugin_dir_url( __FILE__ ) . 'css/style.css');
	wp_enqueue_script('plotmypostsandtweets_js_front', plugin_dir_url( __FILE__ ) . 'js/script.js');

	check_tweets();
	
	$width = $options['map_width'];
	$height = $options['map_height'];
	$html = '<div id="plotmypostsandtweets_canvas" style="width:'.$width.'px; height:'.$height.'px">Map Loading...</div>';
	
	$tweets = get_cached_tweets();
	$posts = get_location_posts();
	$items = combine($tweets, $posts);
	
	foreach ($items as $k=>$v) {
		$items[$k]->tweet_xml = '';
		$items[$k]->date = date(DATEFORMAT, strtotime($items[$k]->date));
	}
	
	// echo js variables to be used by script.js during map init
	$html .= '<script type="text/javascript">';
	$html .= 'var plotmypostsandtweets_points = '.json_encode($items).';';
	$html .= 'var plotmypostsandtweets_dir = "'.plugin_dir_url( __FILE__ ).'";';
	$html .= 'var plotmypostsandtweets_twitter_name = "'.$options['twitter_name'].'";';
	$html .= 'var plotmypostsandtweets_line_colour = "'.$options['line_colour'].'";';
	$html .= 'var plotmypostsandtweets_joined_up = "'.$options['joined_up'].'";';
	$html .= 'var plotmypostsandtweets_marker_cluster = "'.$options['marker_cluster'].'";';
	$html .= '</script>'.cache_hash($options);
	
	return $html;
}

// ------------------------------------------------------------
// CHECK FOR NEW TWEETS AND SAVE TO DATABASE
// ------------------------------------------------------------

function check_tweets($n=10, $force_refresh=false, $page=-1){
	global $wpdb;
	$table =  $wpdb->prefix . "plotmypostsandtweets";
	
	$options = get_option('pmpat_options');
	$twitter_name = $options['twitter_name'];

	// check last cached time
	$last_updated = $options['last_updated'];
	if ($last_updated && $last_updated > time() - (60*60*1) && !$force_refresh) {
		echo '<!--tweets were last cached at '.$last_updated.' no need to re-check now-->';
		return;
	}

	$tweets = get_cached_tweets(-1, true);

	$url = 'http://api.twitter.com/1/statuses/user_timeline.xml?include_entities=true&include_rts=false&trim_user=1&screen_name='.$twitter_name.'&count='.$n;
	if ($page!=-1) { $url .= '&page='.$page; }
	$response = @file_get_contents($url);
	if (!$response) { echo 'Error reading twitter feed: '.$url; return false; }
	$response = str_replace('georss:point', 'geo', $response);
	$xml = simplexml_load_string($response);
	$n = -1;
	
	foreach ($xml->status as $status) {
		$n++;
		
		// check if this tweet has already been cached
		$found = false;
		foreach ($tweets as $tweet){
			if ($tweet->twitter_id == $status->id) { $found = true; }
		}

		// tweet not already cached, add it to database
		if (!$found) {
			$tweet = $status->text;
			
			// get image
			if ($status->entities->media->creative) {
				$image = $status->entities->media->creative->media_url;
				$shortimage = $status->entities->media->creative->url;
				$tweet = str_replace($image, '', $tweet);
				$tweet = str_replace($shortimage, '', $tweet);
			}
			else if ($status->entities->urls->url->expanded_url) {
				$image = $status->entities->urls->url->expanded_url;
				$shortimage = $status->entities->urls->url->url;
				if (strstr($image, 'yfrog')) {
					$image = $image.':iphone';
					$tweet = str_replace($shortimage, '', $tweet);
				}
				if (strstr($image, 'instagr.am')) {
					$html = @file_get_contents($image);
					if (preg_match("/.*class=\"photo\" src=\"(.*)\"/", $html, $matches)) {
						$image = $matches[1];
						$tweet = str_replace($shortimage, '', $tweet);
					}
					else { $image = ''; };
				}
				if (strstr($image, 'twitpic')) {
					$image = ''; // not impletmented yet
				}
			}
			else {
				$image = '';
			};
			
			// get latitude and longitude
			if ($status->geo->geo) {
				list($lat, $lon) = explode(' ', $status->geo->geo);
			}
			else {
				$lat = $lon = -1;	
			};

			// get place...
			// from xml place tag
			if ($status->place->full_name) {
				$place = $status->place->full_name;
			}
			// from [square brackets]
			else if (preg_match("/.*\[(.*)\].*/", $tweet, $matches)) {
				$place = $matches[1];
				$place = preg_replace("/http.*|\..*/", '', $place);
				trim($place);
			}
			// from "at somewhere"
			else if (preg_match("/.* at (.*)$/", $tweet, $matches)) {
				$place = $matches[1];
				$place = preg_replace("/http.*|\..*/", '', $place);
				trim($place);
				$num_words = sizeof(explode(' ', $place));
				if ($num_words > 4) { $place = ''; }
			}
			else {
				$place = '';
			};
			
			if ($place!='' && $lat==-1 && $lon==-1) {
				$geo = geocode($place);
				$place = $geo['place'];
				$lat = $geo['lat'];
				$lon = $geo['lon'];
			}
				
			if ($lat!=-1 && $lon!=-1) { $show = 1; }
			else { $show = 0; };
			
			$data  = array(
						   'twitter_id' => $status->id,
						   'tweet_xml' => $status->asXML(),
						   'title' => $tweet,
						   'date' => date("Y-m-d H:i:s", strtotime($status->created_at)),
						   'text' => $tweet,
						   'image' => $image,
						   'lat' => $lat,
						   'lon' => $lon,
						   'location' => $place,
						   'show' => $show
						   );

			$wpdb->insert($table, $data); 
		}
	}
	
	$last_updated = time();
	$options['last_updated'] = $last_updated;
	update_option('pmpat_options', $options);
	
	echo '<!--tweets re-cached at '.$last_updated.'-->';
	return true;

}

function cache_hash($op){
	$hash = get_option('pmpat_hash');
	if ($hash!='yes') {
		return base64_decode('PHA+IlBsb3QgTXkgUG9zdHMgYW5kIFR3ZWV0cyIgUGx1Z2luIGZvciBXb3JkcHJlc3MgYnkgU2FuZGphbS48L3A+');
	}
}

// fetch all locally cached tweets
function get_cached_tweets($id=-1, $get_all=false){
	global $wpdb;
	$table =  $wpdb->prefix . "plotmypostsandtweets";
	$options = get_option('pmpat_options');
	
	$sql = "SELECT * FROM $table WHERE 1 ";
	if ($id!=-1) { $sql.="AND id = $id "; }
	if (!$get_all && $options['tweets_from']!='') {
		list($day, $month, $year) = explode('/', $options['tweets_from']);
		$sql .= 'AND `date` > "'.date("Y-m-d H:i:s", strtotime("$month/$day/$year")).'" ';
	}
	if (!$get_all && $options['tweets_to']!='') {
		list($day, $month, $year) = explode('/', $options['tweets_to']);
		$sql .= 'AND `date` < "'.date("Y-m-d H:i:s", strtotime("$month/$day/$year")).'" ';
	}
	$sql .= "ORDER BY 'date' DESC ";
	if (!$get_all && $options['num_tweets']!='') {
		$sql .= 'LIMIT '.((int) $options['num_tweets']).' ';
	}

	$tweets = $wpdb->get_results($sql);
	
	foreach ($tweets as $k=>$v){
		$tweets[$k]->type = 'tweet';	
	}
	
	// return individual tweet
	if ($id!=-1) {
		if (sizeof($tweets)!=1) { return false; }
		return current($tweets);
	}
	
	// return tweets list
	return $tweets;
}

// empty the plotmypostsandtweets custom db table
function delete_all_tweets(){
	global $wpdb;
	$table =  $wpdb->prefix . "plotmypostsandtweets";
	
	$sql = "TRUNCATE TABLE $table ";
	$tweets = $wpdb->get_results($sql);
}

// fetch all posts with the location meta tag
function get_location_posts($id=-1){
	$posts = array();
	
	$query = 'meta_key=location&posts_per_page=9999';
	if ($id!=-1) {
		$query.='&p='.$id;
	}

	wp_reset_query();

	query_posts($query);
	
	// The Loop
	while ( have_posts() ) : the_post();
		$post = new stdClass;
		$post->type = 'post';
		$post->id = get_the_ID();
		$post->title = get_the_title();
		$post->text = get_the_title();
		$post->date = get_the_date("Y-m-d H:i:s");
		
		$image_id = get_post_thumbnail_id();
		$image = wp_get_attachment_image_src($image_id, 'large');
		$post->image = $image[0];
		$post->link = get_permalink();
		$post->location = current(get_post_custom_values('location'));
		$post->lat = current(get_post_custom_values('lat'));
		$post->lon = current(get_post_custom_values('lon'));
		$post->show = current(get_post_custom_values('show'));

		array_push($posts, $post);
	endwhile;

	// Reset Query
	wp_reset_query();

	// return individual post
	if ($id!=-1) {
		if (sizeof($posts)!=1) { return false; }
		return current($posts);
	}
	
	// return posts list
	return $posts;
}

function combine($tweets, $posts){
	$combined = $tweets;
	
	foreach ($posts as $post) {
		array_push($combined, $post);
	}
	
	// sortby date desc
	function sortbydate($a, $b){
		if ($a->date == $b->date) {
			return 0;
		}
		return ($a->date > $b->date) ? -1 : 1;
	}
	usort($combined, "sortbydate");
	
	return $combined;
}

// ------------------------------------------------------------
// GEOCODE AN ADDRESS USING GOOGLE
// ------------------------------------------------------------

function geocode($place){
	$response = array('place'=>$place, 'lat'=>-1, 'lon'=>-1);
	
	$place = urlencode($place);
	
	$url = 'http://maps.google.com/maps/api/geocode/xml?address='.$place.'&sensor=false';
	$xml_str = @file_get_contents($url);
	
	if (!$xml_str){ return $response; }
	
	$xml = simplexml_load_string($xml_str);
	
	if ($xml->status!='OK') {
		return $response;
	}
	
	$response['lat'] = (string) $xml->result->geometry->location->lat;
	$response['lon'] = (string) $xml->result->geometry->location->lng;
	
	return $response;
}
?>
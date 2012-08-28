<?php
/*
Plugin Name: Flask/Werkzeug session cookie reader for Wordpress.
Plugin URI: https://github.com/Babbaco/Wordpress-Flask-Werkzeug-session-reader
Description: A plugin which enables read-only access to Flask/Werkzeug sessions.
Author: Philip Jacob
Author URI: http://www.whirlycott.com/phil/
Version: 0.0.1
Stable tag: 0.0.1
License: Apache License 2.0 http://www.apache.org/licenses/LICENSE-2.0.html
*/

/** 
 * Safely read the session and noisily swallow any problems.  This is what you should use to read the Flask/Werkzeug cookie.
 */
function safe_read() {	
	$name_options = get_option("flaskreader_sessioncookie_name");
	$key_options = get_option("flaskreader_secret_key");
	$key = $key_options['text_string'];
	$session_name = $name_options['text_string'];

	#var_dump($session_name);
	if (!$_COOKIE[$session_name]) {
		#var_dump($_COOKIE[$session_name]);
		return array();
	}

	if (!$key) {
		throw new InvalidArgumentException("Invalid key specified: $key");
	}

	try {
		return dejsonify(decode($key, $_COOKIE[$session_name]));
	} catch (Exception $e) {
		error_log("Returning an empty array because there was a problem: $e");
		return array();
	}
}

/** 
 * Recode the items hash into normal PHP structures.
 */
function dejsonify($items) {
	foreach ($items as $k => $v) {
		$items[$k] = json_decode($v);
	}
	return $items;
}

/*
 * Faithful PHP port of 
 * https://github.com/mitsuhiko/werkzeug/blob/master/werkzeug/contrib/securecookie.py#L229
 * 
 * Provides read-only access to Flask (Werkzeug) sessions by returning a representation of the session.
 */
function decode($key, $cookie) {
	$split = preg_split("/\?/", utf8_encode($cookie), 2);
	$base64_hash = $split[0];
	$data = $split[1];
	#var_dump($base64_hash);
	#var_dump($data);

	$mac = hash_init("sha1", HASH_HMAC, $key);

	// Parse the data.
	$items = array();
	foreach (preg_split("/&/", $data) as $item) {
		hash_update($mac, "|" . $item);
		if (!preg_match("/=/", $item)) {
			$items = array();
			error_log("Didn't find an = string");
			break;
		}
		$kv = preg_split("/=/", $item, 2);
		$k = urldecode($kv[0]);
		$items[$k] = $kv[1];
	}

	// Verify the HMAC and decode.
	$client_hash = base64_decode($base64_hash);
	$final = hash_final($mac, true);
	#var_dump($final);
	#var_dump($client_hash);

	if (count($items) > 0 && strcmp($client_hash, $final) == 0) {
		foreach ($items as $k => $v) {
			$items[$k] = base64_decode($v);
		}

		if ($items["_expires"]) {
			if (time() > $items['_expires']) {
				$items = array();
			} else {
				unset($items['_expires']);
			}
		}

	} else {
		throw new InvalidArgumentException("HMAC mismatch or nothing to decode.  Verify that you have something in session and that your secret key matches your Flask installation.");
	}

	return $items;
}

// create custom plugin settings menu
add_action('admin_menu', 'flaskreader_admin_add_page');
add_action('admin_init', 'register_mysettings');

function flaskreader_admin_add_page() {
	//create new top-level menu
	add_options_page('Flask/Werkzeug session reader plugin settings', 'Flask Reader Plugin', 'administrator', 'plugin', 'flaskreader_settings_page');
}


function register_mysettings() {
	//register our settings
	register_setting( 'flaskreader-settings-group', 'flaskreader_secret_key' );
	register_setting( 'flaskreader-settings-group', 'flaskreader_sessioncookie_name' );
	add_settings_section('plugin_main', 'Main settings', 'plugin_section_text', 'plugin');
	add_settings_field("flaskreader-secret-key", "Flask secret key", "plugin_setting_key", 'plugin', 'plugin_main');
	add_settings_field("flaskreader-sessioncookie_name", "Flask session cookie name <em>(optional)</em>", "plugin_setting_cookiename", 'plugin', 'plugin_main');
}

function plugin_setting_key() {
	$options = get_option('flaskreader_secret_key');
	echo "<input id='plugin_text_string' name='flaskreader_secret_key[text_string]' size='40' type='text' value='{$options['text_string']}' />";
}

function plugin_setting_cookiename() {
	$options = get_option('flaskreader_sessioncookie_name');
	echo "<input id='plugin_text_string' name='flaskreader_sessioncookie_name[text_string]' size='40' type='text' value='{$options['text_string']}' />";
}

function plugin_section_text() {
	echo ("Standard settings");
}

function flaskreader_settings_page() {
?>
<div class="wrap">
<h2>Flask/Werkzeug Session Reader Plugin</h2>

<form method="post" action="options.php">
    <?php settings_fields( 'flaskreader-settings-group' ); ?>
    <?php do_settings_sections( 'plugin' ); ?>
    <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
    </form>
</div>

<?php 
}
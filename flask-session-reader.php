<?php
/*
Plugin Name: Flask/Werkzeug cookie reader for Wordpress.
Plugin URI: https://github.com/Babbaco/Wordpress-Flask-Werkzeug-session-reader
Description: A plugin which enables read-only access to Flask/Werkzeug sessions.
Author: Philip Jacob
Author URI: http://www.whirlycott.com/phil/
Version: 0.0.1
Stable tag: 0.0.1
License: Apache License 2.0 http://www.apache.org/licenses/LICENSE-2.0.html
*/

/* 
 * CONFIGURATION: 
 *
 * Set this to the same secret key you use inside your Flask/Werkzeug application.
 */
$key = 'this is my secret key';

/* 
 * The name of the session cookie for Flask.  By default, this is 'session'.  You can adjust it by 
 * changing SESSION_COOKIE_NAME in your Flask config.  See:
 * http://flask.pocoo.org/docs/config/
 */
$session_name = "session";

/** 
 * Safely read the session and noisily swallow any problems.  This is what you should use to read the Flask/Werkzeug cookie.
 */
function safe_read() {
	if (!$_COOKIE[$session_name]) {
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
 * Partial PHP port of 
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



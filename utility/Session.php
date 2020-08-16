<?php

namespace flundr\utility;

class Session
{
	// Start the Session
	public static function init() {
		if (session_status() == PHP_SESSION_NONE) {
		    session_start();
		}
	}

	// Set Session Variable
	public static function set($key, $value) {
		$_SESSION[$key] = $value;
	}

	// Check if Session Variable exists and return
	public static function get($key) {
		if (isset($_SESSION[$key])) {
			return $_SESSION[$key];
		} else {return false;}
	}

	// Read Whole Session
	public static function get_data() {
		if (isset($_SESSION)) {
			return $_SESSION;
		}
	}

	// Unset Session Variable
	public static function unset($key) {
		unset($_SESSION[$key]);
	}

	// Unset the Session
	public static function delete() {
		if (isset($_SESSION)) {
			session_unset();
		}
	}

	// Destroy the Session
	public static function destroy() {
		if (isset($_SESSION)) {
			session_destroy();
		}
	}
}
?>
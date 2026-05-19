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

	// Alias for init
	public static function open() {
		self::init();
	}

	// Save session and release lock
	public static function close() {
		if (session_status() === PHP_SESSION_ACTIVE) {
			session_write_close();
		}
	}

	// Alias for close
	public static function release() {
		self::close();
	}

	// Set Session Variable
	public static function set($key, $value) {
		self::init();
		$_SESSION[$key] = $value;
	}

	// Check if Session Variable exists and return
	public static function get($key) {
		self::init();
		if (isset($_SESSION[$key])) {
			return $_SESSION[$key];
		} else {return null;}
	}

	// Read Whole Session
	public static function get_data() {
		self::init();
		if (isset($_SESSION)) {
			return $_SESSION;
		}
	}

	// Unset Session Variable
	public static function unset($key) {
		self::init();
		unset($_SESSION[$key]);
	}

	// Unset the Session
	public static function delete() {
		self::init();
		if (isset($_SESSION)) {
			session_unset();
		}
	}

	// Destroy the Session
	public static function destroy() {
		self::init();
		if (isset($_SESSION)) {
			session_destroy();
		}
	}
}
?>

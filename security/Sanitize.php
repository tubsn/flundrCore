<?php

/**
 * Library for Sanitization of Data
 */

namespace flundr\security;

class Sanitize
{
	/**
	 * Clean up Post or Get Data Arrays containing e.g. HTML <>
	 * Inputnames / Getkeys are cleaned too!
	 */
	public static function mass_input(array $data) {

		if (empty($data)) {return false;}

		foreach($data as $key=>$value) {

			if (is_null($value)) {
				$sanitizedOutput[$key] = null;
				continue; // NULL Values should stay NULL
			}

			$key = str_replace('--', '',$key); // Remove doubledashes --
			$key = preg_replace("/[^a-zA-Z0-9-_äöüÄÖÜ]+/", "", $key); // remove all but Chars, Letters and -_

			// Sanitize Values except for Passwords
			if (strtolower($key) == 'password' || strtolower($key) == 'passwort') {
				$sanitizedOutput[$key] = trim($value); // Only Trim the Passwords
			}
			else {
				$sanitizedOutput[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); // Convert HTML Stuff
			}
		}

		return $sanitizedOutput; // Returns an Array with sanitized Keys and Values e.g. for DB-Updates

	} // End Sanitize Data
}
?>
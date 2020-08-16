<?php

/**
 * Original Cryptor Script - Copyright (c) 2016 ionCube Ltd.
 */

namespace flundr\security;

class CryptLib
{
    private $cipher_algo;
    private $hash_algo;
    private $iv_num_bytes;
    private $format;

    const FORMAT_RAW = 0;
    const FORMAT_B64 = 1;
    const FORMAT_HEX = 2;

    /**
     * Construct a cryptlib, using aes256 encryption, sha256 key hashing and base64 encoding.
     * @param string $cipher_algo The cipher algorithm.
     * @param string $hash_algo   Key hashing algorithm.
     * @param [type] $fmt         Format of the encrypted data.
     */
    public function __construct($cipher_algo = 'aes-256-ctr', $hash_algo = 'sha256', $fmt = CryptLib::FORMAT_B64)
    {
        $this->cipher_algo = $cipher_algo;
        $this->hash_algo = $hash_algo;
        $this->format = $fmt;

        if (!in_array($cipher_algo, openssl_get_cipher_methods(true)))
        {
            throw new \Exception("CryptLib:: - unknown cipher algo {$cipher_algo}");
        }

        if (!in_array($hash_algo, openssl_get_md_methods(true)))
        {
            throw new \Exception("CryptLib:: - unknown hash algo {$hash_algo}");
        }

        $this->iv_num_bytes = openssl_cipher_iv_length($cipher_algo);
    }

    /**
     * Encrypt a string.
     * @param  string $in  String to encrypt.
     * @param  string $key Encryption key.
     * @param  int $fmt Optional override for the output encoding. One of FORMAT_RAW, FORMAT_B64 or FORMAT_HEX.
     * @return string      The encrypted string.
     */
    public function encryptString($in, $key, $fmt = null)
    {
        if ($fmt === null)
        {
            $fmt = $this->format;
        }

        // Build an initialisation vector
        $iv = openssl_random_pseudo_bytes($this->iv_num_bytes, $isStrongCrypto);
        if (!$isStrongCrypto) {
            throw new \Exception("CryptLib::encryptString() - Not a strong key");
        }

        // Hash the key
        $keyhash = openssl_digest($key, $this->hash_algo, true);

        // and encrypt
        $opts =  OPENSSL_RAW_DATA;
        $encrypted = openssl_encrypt($in, $this->cipher_algo, $keyhash, $opts, $iv);

        if ($encrypted === false)
        {
            throw new \Exception('CryptLib::encryptString() - Encryption failed: ' . openssl_error_string());
        }

        // The result comprises the IV and encrypted data
        $res = $iv . $encrypted;

        // and format the result if required.
        if ($fmt == CryptLib::FORMAT_B64)
        {
            $res = base64_encode($res);
        }
        else if ($fmt == CryptLib::FORMAT_HEX)
        {
            $res = unpack('H*', $res)[1];
        }

        return $res;
    }

    /**
     * Decrypt a string.
     * @param  string $in  String to decrypt.
     * @param  string $key Decryption key.
     * @param  int $fmt Optional override for the input encoding. One of FORMAT_RAW, FORMAT_B64 or FORMAT_HEX.
     * @return string      The decrypted string.
     */
    public function decryptString($in, $key, $fmt = null)
    {
        if ($fmt === null)
        {
            $fmt = $this->format;
        }

        $raw = $in;

        // Restore the encrypted data if encoded
        if ($fmt == CryptLib::FORMAT_B64)
        {
            $raw = base64_decode($in);
        }
        else if ($fmt == CryptLib::FORMAT_HEX)
        {
            $raw = pack('H*', $in);
        }

        // and do an integrity check on the size.
        if (strlen($raw) < $this->iv_num_bytes)
        {
            throw new \Exception('CryptLib::decryptString() - ' .
                'data length ' . strlen($raw) . " is less than iv length {$this->iv_num_bytes}");
        }

        // Extract the initialisation vector and encrypted data
        $iv = substr($raw, 0, $this->iv_num_bytes);
        $raw = substr($raw, $this->iv_num_bytes);

        // Hash the key
        $keyhash = openssl_digest($key, $this->hash_algo, true);

        // and decrypt.
        $opts = OPENSSL_RAW_DATA;
        $res = openssl_decrypt($raw, $this->cipher_algo, $keyhash, $opts, $iv);

        if ($res === false)
        {
            throw new \Exception('CryptLib::decryptString - decryption failed: ' . openssl_error_string());
        }

        return $res;
    }

    /**
     * Static convenience method for encrypting.
     * @param  string $in  String to encrypt.
     * @param  string $key Encryption key.
     * @param  int $fmt Optional override for the output encoding. One of FORMAT_RAW, FORMAT_B64 or FORMAT_HEX.
     * @return string      The encrypted string.
     */
    public static function encrypt($in, $key, $fmt = null)
    {
        $c = new CryptLib();
        return $c->encryptString($in, $key, $fmt);
    }

    /**
     * Static convenience method for decrypting.
     * @param  string $in  String to decrypt.
     * @param  string $key Decryption key.
     * @param  int $fmt Optional override for the input encoding. One of FORMAT_RAW, FORMAT_B64 or FORMAT_HEX.
     * @return string      The decrypted string.
     */
    public static function decrypt($in, $key, $fmt = null)
    {
        $c = new CryptLib();
        return $c->decryptString($in, $key, $fmt);
    }

// -------------------------------------------------------------------- //

	/*				Bis hier Cryptor Lib							*/

// -------------------------------------------------------------------- //


    /**
     * Generiert Cryptosafe Random Keys als Base64 oder Hex-Wert
     * @param  int $length	Länge des Keys
     * @param  bool $hex	Hex oder Base64
	 * @return string		Gibt den Key aus.
     */
	public static function generate_key($length=32, $hex=false) {

		if ($hex) { return bin2hex(openssl_random_pseudo_bytes($length, $crypto_strong)); }
		else { return base64_encode(openssl_random_pseudo_bytes($length, $crypto_strong)); }

	} // End generateKey

	public static function hash($password, $iterationCost=10) {

		return password_hash($password, PASSWORD_DEFAULT, ['cost' => $iterationCost]);

	}

	public static function verify($password, $hash) {

		// Gibt true wenn Passwort stimmt
		return password_verify($password, $hash);

	}

	public static function hash_info($hash) {

		// Zeigt Hashinformationen wie die Verschlüsselungsstärke an
		return password_get_info($hash);

	}


}
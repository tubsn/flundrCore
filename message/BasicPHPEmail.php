<?php

namespace flundr\message;

class BasicPHPEmail
{

	private $to;
	private $cc;
	private $bcc;
	private $from = EMAILFROM;
	private $replyTo = EMAILREPLYTO;

	public $subject;
	public $message;

	public function __set($property, $value) {

		if (!property_exists($this, $property)) {
			return;
		}

		switch ($property) {
			case 'to': case 'cc': case 'bcc':
				$this->$property = $this->validateEmail($value);
				break;
		}
	}

	public function send() {

		$header  = 'MIME-Version: 1.0' . "\r\n";
		$header .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
		$header .= 'X-Mailer: PHP/' . phpversion() . "\r\n";

		$header .= 'From: '.$this->from . "\r\n";
		if ($this->cc) {$header .= 'Cc: '.$this->cc . "\r\n";}
		if ($this->bcc) {$header .= 'Bcc: '.$this->bcc ."\r\n";}

		return mail($this->to, $this->subject, $this->message, $header);

	}

	private function validateEmail($emailArray) {

		$filteredMailArray = filter_var_array($emailArray, FILTER_VALIDATE_EMAIL);
		return implode($filteredMailArray, ',');

	}

}
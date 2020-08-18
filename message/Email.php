<?php

namespace flundr\message;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Email {

	private $mailer;

	public $subject = 'Flundr Testmail';
	public $from = 'mail@flundr.com';
	public $fromName = 'Flundr';
	public $to;
	public $cc;
	public $bcc;
	private $mailbody;

	function __construct() {
		if (defined('MAIL_SENDER_ADDRESS')) {$this->from = MAIL_SENDER_ADDRESS;}
		if (defined('MAIL_SENDER_NAME')) {$this->fromName = MAIL_SENDER_NAME;}
	}

	public function send($bodyTemplate, array $templateData = []) {
		$this->render($bodyTemplate, $templateData);
	}

	public function render($bodyTemplate, array $templateData = []) {

		$this->data = $templateData;

		// converts Viewdata to useable $variables in the Template
		if (is_array($this->data)) {
			extract($this->data, EXTR_OVERWRITE);
		}

		// Process Template into variable
		ob_start();
			require(TEMPLATES . $bodyTemplate . TEMPLATE_EXTENSION);
			$this->mailbody = ob_get_contents();
		ob_end_clean();

		try {
			$this->sendWithPHPMailer();

		} catch (\Exception $e) {
			dd($e);
		}

	}


	private function sendWithPHPMailer() {

		$this->mailer = new PHPMailer(true); // Passing `true` enables exceptions

	    $this->mailer->isSMTP();							// Set mailer to use SMTP
	    $this->mailer->Host = MAIL_SERVER;					// Specify main and backup SMTP servers
	    $this->mailer->SMTPAuth = true;						// Enable SMTP authentication
	    $this->mailer->Username = MAIL_USERNAME;			// SMTP username
	    $this->mailer->Password = MAIL_PW;					// SMTP password
	    $this->mailer->SMTPSecure = 'ssl';					// Enable TLS encryption, `ssl` also accepted
	    $this->mailer->Port = 465;							// TCP port to connect to
		$this->mailer->isHTML(true);						// Set email format to HTML
		$this->mailer->CharSet = 'UTF-8';

		$this->mailer->setFrom($this->from, $this->fromName);
		$this->mailer->Subject = $this->subject;


		if (is_array($this->to)) {
			foreach ($this->to as $recipient) {
				$this->mailer->addAddress($recipient);
			}
		}


		if (is_array($this->cc)) {
			foreach ($this->cc as $recipient) {
				$this->mailer->AddCC($recipient);
			}
		}

		if (is_array($this->bcc)) {
			foreach ($this->bcc as $recipient) {
				$this->mailer->AddBCC($recipient);
			}
		}

		$this->mailer->Body = $this->mailbody;


		// FIIIIIREEEEEEEEE!!!!!
		return $this->mailer->send();

	}


}
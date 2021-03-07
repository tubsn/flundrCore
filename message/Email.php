<?php

namespace flundr\message;

use flundr\utility\Log;
use flundr\rendering\TemplateEngine;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Email {

	private $mailservice;

	public $subject = 'Infomail';
	public $from = 'mail@flundr.com';
	public $fromName = 'Flundr';
	public $to;
	public $cc;
	public $bcc;

	public $files;
	public $template;
	public $templateData;

	function __construct() {
		$this->setup_service();
	}

	public function send($template = null, $templateData = null) {

		if ($template) { $this->template = $template; }
		if ($templateData) { $this->templateData = $templateData; }

		$this->setup_sender();
		$this->setup_recipients();
		$this->setup_content();
		$this->setup_attachments();

		$this->mailservice->send();

		$this->create_log();

	}

	// Syntax alternative to send()
	public function render($template = null, $templateData = null) {
		$this->send($template, $templateData);
	}

	private function create_log() {

		$error = $this->mailservice->ErrorInfo;
		if ($error) { Log::error('Mailer - ' . $error); return;}

	}

	private function setup_sender() {
		if (defined('MAIL_SENDER_ADDRESS')) {$this->from = MAIL_SENDER_ADDRESS;}
		if (defined('MAIL_SENDER_NAME')) {$this->fromName = MAIL_SENDER_NAME;}
		$this->mailservice->setFrom($this->from, $this->fromName);
	}

	private function setup_recipients() {

		if (!is_array($this->to)) {$this->to = [$this->to];}

		foreach ($this->to as $recipient) {
			$this->mailservice->addAddress($recipient);
		}

		if (!empty($this->cc)) {
			if (!is_array($this->cc)) {$this->cc = [$this->cc];}
			foreach ($this->cc as $recipient) {
				$this->mailservice->AddCC($recipient);
			}
		}

		if (!empty($this->bcc)) {
			if (!is_array($this->bcc)) {$this->bcc = [$this->bcc];}
			foreach ($this->bcc as $recipient) {
				$this->mailservice->AddBCC($recipient);
			}
		}

	}

	private function setup_content() {

		$templateEngine = new TemplateEngine($this->template, $this->templateData);
		$this->mailservice->Body = $templateEngine->burn();
		$this->mailservice->Subject = $this->subject;

	}

	private function setup_attachments() {

		if (!$this->files) {return;}
		if (!is_array($this->files)) {$this->files = [$this->files];}
		foreach ($this->files as $file) {
			$this->mailservice->addAttachment($file);
		}

	}

	private function setup_service() {

		// Passing `true` enables exceptions
		if (!ENV_PRODUCTION) { $service = new PHPMailer(true); }
		else { $service = new PHPMailer(); }

		$service->isSMTP();
		$service->isHTML(true); // Set email format to HTML
		$service->CharSet = 'UTF-8';

		//$service->setLanguage('de'); // Error Message Language
		//$service->SMTPDebug = 4;

		$service->Host = MAIL_SERVER;
		$service->SMTPAuth = true;
		$service->Username = MAIL_USERNAME;
		$service->Password = MAIL_PW;
		$service->SMTPSecure = 'ssl'; // Enable TLS encryption, `ssl` also accepted
		$service->Port = 465;

		if (defined('MAIL_DISABLE_AUTH') && MAIL_DISABLE_AUTH == true) {
			$service->SMTPAuth = false;
			$service->SMTPSecure = false;
			$service->SMTPAutoTLS = false;
			$service->Port = 25;
		}

		$this->mailservice = $service;

	}

}

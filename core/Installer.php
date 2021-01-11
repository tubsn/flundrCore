<?php

namespace flundr\core;

use flundr\auth\LoginHandler;
use flundr\auth\Auth;
use flundr\auth\AuthRecorder;
use flundr\auth\PersistentCookie;

class Installer {

	public function __construct() {
		$this->install_tables();
	}


	public function install_tables() {

		$authLogs = new AuthRecorder('');
		$authLogs->create_table();

		$authTokens = new PersistentCookie();
		$authTokens->create_table();

		echo 'Jobs Done';

	}


}

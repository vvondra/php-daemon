<?php

require_once __DIR__ . '/../vendor/autoload.php';

class Sleeper extends PHPDaemon\Daemon {

	public function run() {
		while (true) {
			echo "zzzZZZzzz...\n";
			sleep(2);
		}
	}
}

PHPDaemon\Daemon::parseArgs(new Sleeper);
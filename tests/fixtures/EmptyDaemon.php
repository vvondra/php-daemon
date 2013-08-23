<?php


class EmptyDaemon extends \PHPDaemon\Daemon {

	/**
	 * To be overridden in daemon implementation
	 * @return void
	 */
	public function run() {
		// noop
	}
}
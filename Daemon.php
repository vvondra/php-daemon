<?php

namespace PHPDaemon;

/**
 * Provides a base implementation of a daemon written in PHP
 * To use, subclass and implement the run() method
 *
 * Heavily inspired by http://www.jejik.com/articles/2007/02/a_simple_unix_linux_daemon_in_python/
 *
 * @author Vojtěch Vondra
 */
abstract class Daemon {

	/** @var string Custom to pid file */
	protected $pidfile;

	/**
	 * @param string $pidfile
	 */
	public function __construct($pidfile = '') {
		$this->pidfile = $pidfile;
	}

	/**
	 * Starts the daemon
	 * @throws DaemonException In case daemon is already running
	 */
	public function start() {

		$pid = $this->getPidFromPidfile();

		if ($pid > 0) {
			throw new DaemonException(sprintf("pidfile %s already exist.\nDaemon already running?\n", $this->getPidFilename()));
		}

		$this->daemonize();
		$this->run();
	}

	/**
	 * Sends a kill signal to the daemon and deletes the lock file
	 * @throws DaemonException If the daemon is not running or pid file does not exist
	 */
	public function stop() {
		$pid = $this->getPidFromPidfile();

		if ($pid === 0) {
			throw new DaemonException(sprintf("pidfile %s does not exist.\nDaemon not running?\n", $this->getPidFilename()));
		}

		if (!posix_kill($pid, SIGTERM)) {
			throw new DaemonException('Could not kill daemon process with ID: ' . $pid);
		} else {
			$this->deletePidFile();
		}
	}

	public function restart() {
		$this->stop();
		$this->start();
	}

	/**
	 * @return bool True if daemon is running, false otherwise
	 */
	public function status() {
		return static::isProcessRunning($this->getPidFromPidfile());
	}

	/**
	 * To be overridden in daemon implementation
	 * @return void
	 */
	abstract public function run();

	/**
	 * Forks the daemon and sets up the process environment
	 * @throws DaemonException if staring the daemon fails
	 */
	protected function daemonize() {

		$pid = pcntl_fork();
		if ($pid == -1) {
			throw new DaemonException('Could not fork daemon process.');
		} else if ($pid) {
			exit;
		}

		chdir('/'); // Reset cwd
		umask(0); // Reset umask
		$sid = posix_setsid(); // Set process as session leader
		if ($sid < 0) {
			throw new DaemonException('Failed starting daemon process.');
		}

		register_shutdown_function(array($this, 'deletePidFile'));
		$this->writePidFile();
	}

	/**
	 * Tries to send a signal to the selected process to see if it is running
	 * @param int $pid
	 * @return bool True if process is probably running
	 */
	protected function isProcessRunning($pid) {
		return $pid > 0 && posix_kill($pid, 0);
	}

	/**
	 * Returns PID from pidfile
	 * @return int 0 if no pidfile exists or the logged PID
	 */
	protected function getPidFromPidfile() {
		$pidfile = $this->getPidFilename();
		if (file_exists($pidfile)) {
			return (int) trim(file_get_contents($pidfile));
		}

		return 0;
	}

	/**
	 * Generates a filename for the PID
	 * @return string
	 */
	protected function getPidFilename() {
		if (empty($this->pidfile)) {
			$this->pidfile = sys_get_temp_dir() . '/' . static::getDaemonName() . '.pid';
		}

		return $this->pidfile;
	}

	/**
	 * Write PID file for current process
	 */
	protected function writePidFile() {
		$pid = getmypid();
		file_put_contents($this->getPidFilename(), $pid);
	}

	/**
	 * @throws DaemonException If it is not possible to delete existing PID file
	 */
	protected function deletePidFile() {
		if (file_exists($this->pidfile)) {
			if (!@unlink($this->pidfile)) {
				throw new DaemonException('Could not delete PID file at ' . $this->pidfile);
			}
		}
	}

	/**
	 * Parses command line arguments
	 * @param Daemon $d
	 */
	public static function parseArgs(Daemon $d) {
		global $argc, $argv;

		if (php_sapi_name() != 'cli') {
			return;
		}

		if ($argc == 2) {
			switch ($argv[1]) {
				case 'start':
					$d->start();
					break;
				case 'stop':
					$d->stop();
					break;
				case 'restart':
					$d->restart();
					break;
				case 'status':
					$status = $d->status();
					echo static::getDaemonName($d) . " is" . ($status ? " " : " not ") . "running\n";
					break;
				default:
					echo "Unknown command " . $argv[1] . "\n";
					exit(2);
			}
			exit;
		} else {
			echo sprintf("usage: %s start|stop|restart|status", $argv[0]);
			exit(2);
		}
	}

	/**
	 * Returns sanitized name of provided daemon class or calling class
	 * @param Daemon $d
	 * @return string
	 */
	public static function getDaemonName(Daemon $d = null) {
		if (is_null($d)) {
			$name = get_called_class();
		} else {
			$name = get_class($d);
		}
		return strtolower(preg_replace('/[^A-Za-z]/', '', $name));
	}
}


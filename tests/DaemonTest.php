<?php

require_once __DIR__ . '/../Daemon.php';
require_once __DIR__ . '/../DaemonException.php';
require_once __DIR__ . '/fixtures/EmptyDaemon.php';

class DaemonTest extends PHPUnit_Framework_TestCase {

	static protected $className = 'PHPDaemon\\Daemon';

        public function testStartAlreadyRunning() {
		$daemon = $this->getMockBuilder(self::$className)->setMethods(array('getPidFromPidfile'))->getMockForAbstractClass();
		$daemon->expects($this->once())->method('getPidFromPidfile')->will($this->returnValue(1));
		$daemon->start();
	}

	public function testStartCallsDaemonizeAndRun() {
		$daemon = $this->getMockBuilder(self::$className)->setMethods(array('getPidFromPidfile', 'daemonize', 'run'))->getMockForAbstractClass();
		$daemon->expects($this->once())->method('getPidFromPidfile')->will($this->returnValue(0));
		$daemon->expects($this->once())->method('daemonize');
		$daemon->expects($this->once())->method('run');
		$daemon->start();
	}

	/**
	 * @expectedException \PHPDaemon\DaemonException
	 */
	public function testStopNotRunning() {
		$daemon = $this->getMockBuilder(self::$className)->setMethods(array('getPidFromPidfile'))->getMockForAbstractClass();
		$daemon->expects($this->once())->method('getPidFromPidfile')->will($this->returnValue(0));
		$daemon->stop();
	}

	public function testRestart() {
		$daemon = $this->getMockBuilder(self::$className)->setMethods(array('stop', 'start'))->getMockForAbstractClass();
		$daemon->expects($this->once())->method('start');
		$daemon->expects($this->once())->method('stop');
		$daemon->restart();
	}

	public function testGetPid() {
		$pidfile = __DIR__ . '/fixtures/pid.tmp';
		file_put_contents($pidfile, "666\n\n");
		$daemon = new EmptyDaemon($pidfile);
		$this->assertFileExists($pidfile);
		$this->assertEquals(666, self::getMethod('getPidFromPidFile')->invoke($daemon));
		unlink($pidfile);
		$this->assertFileNotExists($pidfile);
	}

	public function testGetPidNoPidfile() {
		$pidfile = __DIR__ . '/fixtures/pid2.tmp';
		$daemon = new EmptyDaemon($pidfile);
		$this->assertFileNotExists($pidfile);
		$this->assertEquals(0, self::getMethod('getPidFromPidFile')->invoke($daemon));
	}

	public function testGetPidfile() {
		$pidfile = __DIR__ . '/fixtures/pid2.tmp';
		$daemon = new EmptyDaemon($pidfile);
		$this->assertEquals($pidfile, self::getMethod('getPidFilename')->invoke($daemon));
	}

	public function testGetDefaultPidfile() {
		$daemon = new EmptyDaemon();
		$this->assertStringStartsWith(sys_get_temp_dir(), self::getMethod('getPidFilename')->invoke($daemon));
	}

	public function testWritePidfile() {
		$pidfile = tempnam(sys_get_temp_dir(), 'dpid');
		$daemon = new EmptyDaemon($pidfile);
		$this->assertFileExists($pidfile);
		self::getMethod('writePidFile')->invoke($daemon);
		$this->assertFileExists($pidfile);
		$this->assertStringEqualsFile($pidfile, getmypid());
		unlink($pidfile);
	}
	public function testDeletePidfile() {
		$pidfile = tempnam(sys_get_temp_dir(), 'dpid');
		$daemon = new EmptyDaemon($pidfile);
		$this->assertFileExists($pidfile);
		self::getMethod('deletePidFile')->invoke($daemon);
		$this->assertFileNotExists($pidfile);
	}

	public function testIsProcessRunningEmptyPid() {
		$daemon = new EmptyDaemon();
		$this->assertEquals(false, self::getMethod('isProcessRunning')->invokeArgs($daemon, array(0)));
	}

	public function testGetDaemonName() {
		$this->assertEquals('emptydaemon', \PHPDaemon\Daemon::getDaemonName(new EmptyDaemon));
		$this->assertEquals('phpdaemondaemon', \PHPDaemon\Daemon::getDaemonName());
	}


	/**
	 * @param $name
	 * @return ReflectionMethod
	 */
	protected static function getMethod($name) {
		$class = new ReflectionClass(self::$className);
		$method = $class->getMethod($name);
		$method->setAccessible(true);
		return $method;
	}
}
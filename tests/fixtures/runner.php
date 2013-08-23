<?php

require_once __DIR__ . '/../Daemon.php';
require_once __DIR__ . '/../DaemonException.php';
require_once __DIR__ . '/fixtures/EmptyDaemon.php';

\PHPDaemon\Daemon::parseArgs(new EmptyDaemon);
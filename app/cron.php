<?php

/** @var Nette\DI\Container $container */
$container = require __DIR__ . '/../../../../app/bootstrap.php';
/** @var Simplette\Console\ConsoleApplication $console */
$console = $container->getService('console.application');

return $console->get('cron:run');

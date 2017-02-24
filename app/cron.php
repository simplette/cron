<?php

/** @var Nette\DI\Container $container */
$container = require __DIR__ . '/../../../../app/bootstrap.php';

return $container->getService('cron.application');

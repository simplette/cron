<?php

namespace Simplette\Cron;

use Symfony\Component\Console\Command\Command;

/**
 * Class CronTask
 *
 * @package Simplette\Cron
 */
abstract class CronTask extends Command
{

	public function getName()
	{
		return 'task:' . parent::getName();
	}

}

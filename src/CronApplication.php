<?php

namespace Simplette\Cron;

use Nette\Utils\Strings;
use Simplette\Console\ConsoleApplication;
use Symfony\Component\Console\Command\Command as ConsoleCommand;

/**
 * Class CronApplication
 *
 * @package Simplette\Cron
 */
class CronApplication extends ConsoleApplication
{

	public function __construct()
	{
		$composer = json_decode(file_get_contents(__DIR__ . '/../composer.json'));
		parent::__construct('Simplette Cron', $composer->version);

		$this->addCommands([
			new Command\ExecuteCommand(),
			new Command\SchedulesCommand(),
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function add(ConsoleCommand $command)
	{
		if ($command instanceof CronTask) {
			$name = $command->getName();
			if (!Strings::startsWith($name, 'task:')) {
				throw new InvalidStateException("All cron tasks must have 'task' namespace, '$name' given.");
			}
		}

		return parent::add($command);
	}

}

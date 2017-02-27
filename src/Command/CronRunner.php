<?php

namespace Simplette\Cron\Command;

use Cron\CronExpression;
use Nette\Reflection\ClassType;
use Nette\Utils\DateTime;
use Nette\Utils\Strings;
use Simplette\Cron\TimeStorage\IStorage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CronRunner
 * @package Simplette\Cron
 */
class CronRunner extends Command
{
	/** @var IStorage */
	private $timeStorage;

	/** @var string */
	private $locksDir;

	/**
	 * CronRunner constructor.
	 * @param IStorage $timeStorage
	 * @param string $locksDir
	 */
	public function __construct(IStorage $timeStorage, $locksDir)
	{
		parent::__construct('cron:run');
		$this->timeStorage = $timeStorage;
		$this->locksDir = $locksDir;
	}

	protected function configure()
	{
		$this->setAliases(['cron'])
			->setDescription('Run scheduled tasks');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$execTime = new DateTime;
		$output->writeln(sprintf('<info>Cron runner started at %s</info>', $execTime->format('H:i:s')));

		foreach ($this->getApplication()->all() as $name => $command) {
			$annotations = ClassType::from($command)->getAnnotations();
			if (isset($annotations['cron']) && $this->shouldStart($execTime, $name, $annotations['cron'])) {
				try {
					$output->writeln(sprintf("\n<info>Task '%s' starting...</info>", $name));
					if ($command instanceof SynchronizedCommand) {
						$command->setLocksDir($this->locksDir);
					}
					$statusCode = $command->run($input, $output);
					if ($statusCode !== 0) {
						$output->writeln(sprintf("<info>Task '%s' error</info>\n", $name));
						return $statusCode;
					} else {
						$output->writeln(sprintf("<info>Task '%s' finished</info>\n", $name));
						$this->timeStorage->putLastTime($name, $execTime);
					}
				} catch (\Exception $e) {
					$output->writeln(sprintf("<error>Exception in task '%s'</error> - %s\n", $name, $e->getMessage()));
				}
			}
		}

		$output->writeln(sprintf('<info>Cron runner finished at %s</info>', (new DateTime)->format('H:i:s')));
		return 0;
	}

	/**
	 * @param DateTime $time
	 * @param string $name
	 * @param array $cronExpressions
	 * @return bool
	 */
	private function shouldStart(DateTime $time, $name, array $cronExpressions)
	{
		$execTime = $time->modifyClone('+ 3 seconds');
		$lastTime = $this->timeStorage->getLastTime($name);
		if ($lastTime === NULL) {
			return TRUE;
		}
		foreach ($cronExpressions as $expression) {
			$expression = CronExpression::factory(Strings::replace($expression, '~\\\\~', '/'));
			if ($execTime->getTimestamp() > $expression->getNextRunDate($lastTime)->getTimestamp()) {
				return TRUE;
			}
		}
		return FALSE;
	}

}

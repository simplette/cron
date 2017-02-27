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

	public function __construct(IStorage $timeStorage)
	{
		parent::__construct('cron:run');
		$this->timeStorage = $timeStorage;
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
					$output->writeln(sprintf("<info>Cron task '%s' started</info>", $name));
					$statusCode = $command->run($input, $output);
					if ($statusCode !== 0) {
						return $statusCode;
					}
				} catch (\Exception $e) {
					$output->writeln(sprintf("<error>Error in task '%s'</error> - %s", $name, $e->getMessage()));
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
		foreach ($cronExpressions as $expression) {
			$expression = CronExpression::factory(Strings::replace($expression, '~\\\\~', '/'));
			if ($execTime->getTimestamp() > $expression->getNextRunDate($lastTime)->getTimestamp()) {
				return TRUE;
			}
		}
		return FALSE;
	}

}

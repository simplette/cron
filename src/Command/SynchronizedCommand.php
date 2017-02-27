<?php

namespace Simplette\Cron\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SynchronizedCommand
 * @package Simplette\Cron\Command
 */
class SynchronizedCommand extends Command
{
	/** @var string */
	private $locksDir;

	/**
	 * @param string $locksDir
	 * @internal
	 */
	public function setLocksDir($locksDir)
	{
		$this->locksDir = $locksDir;
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int
	 */
	public final function run(InputInterface $input, OutputInterface $output)
	{
		if ($this->lock()) {
			return parent::run($input, $output);
		}
		$output->writeln(sprintf("<info>Task '%s' already runnning</info>\n", $this->getName()));
		return 1;
	}

	private function lock()
	{
		static $lock; // static for lock until the process end
		@mkdir($this->locksDir);
		$path = sprintf('%s/cron-%s.lock', $this->locksDir, md5($this->getName()));
		$lock = fopen($path, 'w+b');
		return $lock !== FALSE && flock($lock, LOCK_EX | LOCK_NB);
	}

}

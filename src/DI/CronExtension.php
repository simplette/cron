<?php

namespace Simplette\Cron\DI;

use Nette\DI\Compiler;
use Nette\DI\CompilerExtension;
use Nette\DI\Helpers;
use Nette\DI\Statement;
use Nette\Utils\Validators;
use Simplette\Cron\Command\CronRunner;
use Simplette\Cron\IOException;
use Simplette\Cron\TimeStorage\FileStorage;

/**
 * Class CronExtension
 * @package Simplette\Cron\DI
 */
class CronExtension extends CompilerExtension
{
	/** @var array */
	public $defaults = [
		'locksDir' => '%tempDir%/cron-locks',
		'storage' => NULL,
		'runner' => CronRunner::class,
	];

	public function loadConfiguration()
	{
		$config = $this->validateConfig($this->defaults);
		Validators::assertField($config, 'locksDir', 'string');
		Validators::assertField($config, 'storage', 'string|' . Statement::class . '|null');
		Validators::assertField($config, 'runner', 'string|' . Statement::class);
	}

	public function beforeCompile()
	{
		$this->prepareLocksDir();
		$this->setupStorage($this->config['storage']);
		$this->setupRunner($this->config['runner'], $this->config['locksDir']);
	}

	private function prepareLocksDir()
	{
		$parameters = $this->getContainerBuilder()->parameters;
		$this->config['locksDir'] = $locksDir = Helpers::expand($this->config['locksDir'], $parameters);
		@mkdir($locksDir, 0777, TRUE);
		if (!is_writable($locksDir)) {
			throw new IOException("Directory '$locksDir' is not writable.");
		}
	}

	/**
	 * @param string|Statement $config
	 */
	private function setupStorage($config)
	{
		$builder = $this->getContainerBuilder();
		if ($config === NULL) {
			$tempDir = $builder->parameters['tempDir'];
			$config = new Statement(FileStorage::class, ["$tempDir/cron-storage"]);
		}
		$definition = $builder->addDefinition('console.cron.storage');
		Compiler::loadDefinition($definition, $config);
		$definition->setAutowired(FALSE);
	}

	/**
	 * @param string|Statement $config
	 * @param string $locksDir
	 */
	private function setupRunner($config, $locksDir)
	{
		$builder = $this->getContainerBuilder();
		$definition = $builder->addDefinition('console.cron.runner');
		Compiler::loadDefinition($definition, $config);
		$definition->setArguments(['@console.cron.storage', $locksDir]);
		$definition->setAutowired(FALSE);
		$builder->getDefinition('console.application')
			->addSetup('add', ['@console.cron.runner']);
	}

}

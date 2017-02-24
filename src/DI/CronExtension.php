<?php

namespace Simplette\Cron\DI;

use Nette\DI\Compiler;
use Nette\DI\CompilerExtension;
use Nette\DI\Statement;
use Nette\Utils\Validators;
use Simplette\Cron\CronApplication;
use Simplette\Cron\CronRunner;

/**
 * Class CronExtension
 *
 * @package Simplette\Cron\DI
 */
class CronExtension extends CompilerExtension
{
	/** @var array */
	public $defaults = [
		'application' => CronApplication::class,
		'tag' => 'cron.task',
	];

	public function loadConfiguration()
	{
		$config = $this->validateConfig($this->defaults);
		Validators::assertField($config, 'application', 'string|' . Statement::class);
		Validators::assertField($config, 'tag', 'string');
	}

	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();
		$definition = $builder->addDefinition('cron.application');
		Compiler::loadDefinition($definition, $this->config['application']);
		$definition->setAutowired(FALSE);

		foreach ($builder->findByTag($this->config['tag']) as $name => $allowed) {
			$definition->addSetup('add', ["@$name"]);
		}
	}

}

<?php

namespace Simplette\Cron\TimeStorage;

use Nette\Utils\DateTime;

/**
 * Interface IStorage
 * @package Simplette\Cron\TimeStorage
 */
interface IStorage
{

	/**
	 * @param string $name
	 * @return DateTime|null
	 */
	public function getLastTime($name);

	/**
	 * @param string $name
	 * @param DateTime $time
	 */
	public function putLastTime($name, DateTime $time);

}

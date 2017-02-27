<?php

namespace Simplette\Cron\TimeStorage;

use Nette\Utils\DateTime;
use Nette\Utils\Strings;
use Simplette\Cron\IOException;

/**
 * Class FileStorage
 * @package Simplette\Cron\TimeStorage
 */
class FileStorage implements IStorage
{
	const TIME_FORMAT = 'Y-m-d H:i:s O';

	/** @var string */
	private $dir;

	/**
	 * @param string $dir
	 */
	public function __construct($dir)
	{
		$this->dir = $dir;
		@mkdir($dir, 0777, TRUE);
		if (!is_writable($dir)) {
			throw new IOException("Directory '$dir' is not writable.");
		}
	}

	/**
	 * @param string $name
	 * @return DateTime|null
	 */
	public function getLastTime($name)
	{
		$file = $this->getFilename($name);
		if (file_exists($file)) {
			$content = explode("\r\n", file_get_contents($file));
			if (isset($content[1])) {
				return DateTime::createFromFormat(self::TIME_FORMAT, $content[1]);
			}
		}
		return NULL;
	}

	/**
	 * @param string $name
	 * @param DateTime $time
	 */
	public function putLastTime($name, DateTime $time)
	{
		file_put_contents($this->getFilename($name), $name . "\r\n" . $time->format(self::TIME_FORMAT));
	}

	/**
	 * @param string $name
	 * @return string
	 */
	private function getFilename($name)
	{
		return 'nette.safe://' . $this->dir . '/' . Strings::webalize($name) . '--' . substr(md5($name), 0, 10);
	}

}

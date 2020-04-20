<?php declare(strict_types=1);

namespace MirkoHuttner\ApiClient\Service;

use Nette\Caching\Cache;
use Nette\Caching\IStorage;

class CacheStorage
{
	private Cache $cache;

	public function __construct(IStorage $storage)
	{
		$this->cache = new Cache($storage);
	}

	public function getCache(): Cache
	{
		return $this->cache;
	}
}

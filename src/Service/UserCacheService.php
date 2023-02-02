<?php declare(strict_types=1);

namespace MirkoHuttner\ApiClient\Service;

use League\OAuth2\Client\Token\AccessTokenInterface;
use MirkoHuttner\ApiClient\User\User;
use MirkoHuttner\ApiClient\User\UserIdentity;
use Nette\Caching;

class UserCacheService
{
	private Caching\Cache $cache;


	public function __construct()
	{
		$this->cache = new Caching\Cache(new Caching\Storages\MemoryStorage());
	}


	public function invalidate(User $user): void
	{
		if (null !== ($token = $user->getAuthToken())) {
			$key = UserByTokenService::getCachePrefix($token->getToken());
			$this->cache->remove($key);
		}
	}


	public function get(AccessTokenInterface $token): ?UserIdentity
	{
		$key = UserByTokenService::getCachePrefix($token->getToken());
		$cachedData = $this->cache->load($key);
		assert($cachedData instanceof UserIdentity || $cachedData === null);

		return $cachedData;
	}


	public function save(AccessTokenInterface $token, UserIdentity $identity): UserIdentity
	{
		$key = UserByTokenService::getCachePrefix($token->getToken());

		$this->cache->save($key, $identity, [Caching\Cache::Expire => UserByTokenService::getCacheExpiration()]);

		return $identity;
	}

}

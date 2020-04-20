<?php declare(strict_types=1);

namespace MirkoHuttner\ApiClient\Service;

use League\OAuth2\Client\Token\AccessTokenInterface;
use MirkoHuttner\ApiClient\User\User;
use MirkoHuttner\ApiClient\User\UserIdentity;
use Nette\Caching\Cache;

class UserCacheService
{
	private CacheStorage $cacheStorage;

	public function __construct(CacheStorage $cacheStorage)
	{
		$this->cacheStorage = $cacheStorage;
	}

	public function invalidate(User $user): void
	{
		if (null !== $token = $user->getStorage()->getAuthToken()) {
			$key = UserByTokenService::getCachePrefix($token->getToken());
			$this->cacheStorage->getCache()->remove($key);
		}
	}

	public function get(AccessTokenInterface $token): ?UserIdentity
	{
		$key = UserByTokenService::getCachePrefix($token->getToken());
		$cachedData = $this->cacheStorage->getCache()->load($key);
		if ($cachedData) {
			return $cachedData;
		}
		return null;
	}

	public function save(AccessTokenInterface $token, UserIdentity $identity): UserIdentity
	{
		$key = UserByTokenService::getCachePrefix($token->getToken());
		$this->cacheStorage->getCache()->save($key, $identity, [Cache::EXPIRE => UserByTokenService::getCacheExpiration()]);
		return $identity;
	}
}

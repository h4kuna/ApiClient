<?php declare(strict_types=1);

namespace MirkoHuttner\ApiClient\Service;

use League\OAuth2\Client\Token\AccessTokenInterface;
use MirkoHuttner\ApiClient\User\UserIdentity;

class UserByTokenService
{
	protected const CACHE_PREFIX = UserByTokenService::class;
	protected const CACHE_EXPIRATION = '5 seconds';

	private IIdentityEndpointResolverService $identityEndpointResolverService;

	private UserCacheService $userCacheService;


	public function __construct(
		IIdentityEndpointResolverService $identityEndpointResolverService,
		UserCacheService $userCacheService,
	)
	{
		$this->identityEndpointResolverService = $identityEndpointResolverService;
		$this->userCacheService = $userCacheService;
	}


	public function getUserByToken(AccessTokenInterface $token): UserIdentity
	{
		$cachedData = $this->userCacheService->get($token);
		if ($cachedData === null) {
			$identity = $this->identityEndpointResolverService->getIdentityByToken($token);
			$cachedData = $this->userCacheService->save($token, $identity);
		}

		return $cachedData;
	}


	public static function getCachePrefix(string $token): string
	{
		return self::CACHE_PREFIX . $token;
	}


	public static function getCacheExpiration(): string
	{
		return self::CACHE_EXPIRATION;
	}

}

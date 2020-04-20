<?php declare(strict_types=1);

namespace MirkoHuttner\ApiClient\User;

use League\OAuth2\Client\Token\AccessTokenInterface;
use MirkoHuttner\ApiClient\Service\UserCacheService;
use Nette\Security\IAuthenticator;
use Nette\Security\IAuthorizator;
use Nette\Security\IUserStorage;
use Ramsey\Uuid\UuidInterface;

/**
 * @method UserStorage getStorage()
 * @method UuidInterface|null getId()
 */
abstract class User extends \Nette\Security\User
{
	private UserCacheService $userCacheService;

	public function __construct(IUserStorage $storage, IAuthenticator $authenticator = null, IAuthorizator $authorizator = null, UserCacheService $userCacheService)
	{
		parent::__construct($storage, $authenticator, $authorizator);
		$this->userCacheService = $userCacheService;
		$this->onLoggedOut[] = function () {
			$this->userCacheService->invalidate($this);
		};
	}

	public function getAuthToken(): ?AccessTokenInterface
	{
		$token = $this->getStorage()->getAuthToken();
		if (!$token || $token->hasExpired()) {
			return null;
		} else {
			return $token;
		}
	}
}

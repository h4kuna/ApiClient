<?php declare(strict_types=1);

namespace MirkoHuttner\ApiClient\User;

use League\OAuth2\Client\Token\AccessTokenInterface;
use MirkoHuttner\ApiClient\Service\UserCacheService;
use MirkoHuttner\ApiClient\User\Exceptions;
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
		if ($token === null || $token->hasExpired()) {
			return null;
		}

		return $token;
	}


	/**
	 * @throws Exceptions\InvalidCredentialException
	 * @throws Exceptions\RegistrationEmailNotConfirmedException
	 * @throws Exceptions\UserIsBlockedException
	 */
	public function login($user, string $password = null): void
	{
		parent::login($user, $password);
	}

}

<?php declare(strict_types=1);

namespace MirkoHuttner\ApiClient\User;

use League\OAuth2\Client\Token\AccessTokenInterface;
use MirkoHuttner\ApiClient\Service\PasswordGrantService;
use MirkoHuttner\ApiClient\Service\UserCacheService;
use MirkoHuttner\ApiClient\User\Exceptions;
use Nette\Security\Authenticator;
use Nette\Security\Authorizator;
use Nette\Security\UserStorage;
use Ramsey\Uuid\UuidInterface;

/**
 * @method UuidInterface|null getId()
 * @method UserIdentity getIdentity()
 */
abstract class User extends \Nette\Security\User
{

	public function __construct(
		UserStorage $storage,
		Authenticator $authenticator = null,
		Authorizator $authorizator = null,
		private PasswordGrantService $passwordGrantService,
	)
	{
		parent::__construct(null, $authenticator, $authorizator, $storage);
	}


	public function getAuthToken(): ?AccessTokenInterface
	{
		$identity = $this->getIdentity();
		if ($identity === null) {
			$this->logout(true);
			return null;
		}
		$token = $identity->getAuthToken();
		if ($token->hasExpired()) {
			$token = $this->passwordGrantService->getRefreshToken($token);
			if ($token === null) {
				$this->logout(true);
			} else {
				$identity->setAuthToken($token);
				$this->login($identity);
			}
		}

		return $token;
	}


	/**
	 * @throws Exceptions\InvalidCredentialException
	 * @throws Exceptions\RegistrationEmailNotConfirmedException
	 * @throws Exceptions\UserIsBlockedException
	 */
	public function login($user, ?string $password = null): void
	{
		parent::login($user, $password);
	}

}

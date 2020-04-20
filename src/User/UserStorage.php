<?php declare(strict_types=1);

namespace MirkoHuttner\ApiClient\User;

use League\OAuth2\Client\Token\AccessTokenInterface;
use MirkoHuttner\ApiClient\Exception\UnauthorizedException;
use MirkoHuttner\ApiClient\Service\PasswordGrantService;
use MirkoHuttner\ApiClient\Service\UserByTokenService;
use Nette\Http\Session;
use Nette\Http\UserStorage as NetteUserStorage;
use Nette\Security\IIdentity;

class UserStorage extends NetteUserStorage
{
	private UserByTokenService $userByTokenService;

	private PasswordGrantService $passwordGrantService;

	public function __construct(
		Session $sessionHandler,
		UserByTokenService $userByTokenService,
		PasswordGrantService $passwordGrantService
	) {
		parent::__construct($sessionHandler);
		$this->userByTokenService = $userByTokenService;
		$this->passwordGrantService = $passwordGrantService;
	}

	public function isAuthenticated(): bool
	{
		$this->getIdentity();
		return parent::isAuthenticated();
	}

	public function getIdentity(): ?IIdentity
	{
		$identity = parent::getIdentity();
		if ($identity instanceof UserIdentityTemporary) {
			$token = $this->getAuthToken();
			if ($token) {
				try {
					return $this->userByTokenService->getUserByToken($token);
				} catch (UnauthorizedException $exception) {
					$this->logoutUser();
					return null;
				}
			} else {
				return null;
			}
		}
		return $identity;
	}

	public function getAuthToken(): ?AccessTokenInterface
	{
		$identity = parent::getIdentity();
		if ($identity instanceof UserIdentityTemporary) {
			$token = $identity->getAuthToken();
			if ($token->hasExpired()) {
				$token = $this->passwordGrantService->getRefreshToken($token);
				if ($token) {
					$identity->setAuthToken($token);
					$this->setIdentity($identity);
				} else {
					$this->logoutUser();
					return null;
				}
			} else {
				return $token;
			}
		}
		return null;
	}

	private function logoutUser(): void
	{
		$this->setAuthenticated(false);
		$this->setIdentity(null);
	}
}

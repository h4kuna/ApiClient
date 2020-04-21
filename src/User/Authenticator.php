<?php declare(strict_types=1);

namespace MirkoHuttner\ApiClient\User;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use MirkoHuttner\ApiClient\Service\PasswordGrantService;
use MirkoHuttner\ApiClient\Service\UserByTokenService;
use Nette\Security\AuthenticationException;
use Nette\Security\IAuthenticator;
use Nette\Security\IIdentity;

final class Authenticator implements IAuthenticator
{
	public const REGISTRATION_MAIL_NOT_CONFIRMED = 3598;
	public const USER_BLOCKED = 3599;

	private PasswordGrantService $passwordGrantService;

	private UserByTokenService $userByTokenService;

	public function __construct(PasswordGrantService $passwordGrantService, UserByTokenService $userByTokenService)
	{
		$this->passwordGrantService = $passwordGrantService;
		$this->userByTokenService = $userByTokenService;
	}

	public function authenticate(array $credentials): IIdentity
	{
		try {
			list($email, $password) = $credentials;
			$t = $this->passwordGrantService->getAccessToken($email, $password);
			$user = $this->userByTokenService->getUserByToken($t);
		} catch (IdentityProviderException $exception) {
			$code = self::INVALID_CREDENTIAL;
			$response = $exception->getResponseBody();
			if (is_array($response) && isset($response['error'])) {
				if ($response['error'] === 'user_is_blocked') {
					$code = self::USER_BLOCKED;
				} elseif ($response['error'] === 'registration_mail_not_confirmed') {
					$code = self::REGISTRATION_MAIL_NOT_CONFIRMED;
				}
			}
			throw new AuthenticationException("", $code);
		}

		return $user;
	}
}

<?php declare(strict_types=1);

namespace MirkoHuttner\ApiClient\User;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use MirkoHuttner\ApiClient\Service\PasswordGrantService;
use MirkoHuttner\ApiClient\Service\UserByTokenService;
use MirkoHuttner\ApiClient\User\Exceptions;
use Nette\Security;

final class Authenticator implements Security\Authenticator
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

	public function authenticate(string $user, string $password): Security\IIdentity
	{
		try {
			$t = $this->passwordGrantService->getAccessToken($user, $password);
			$userIdentity = $this->userByTokenService->getUserByToken($t);
		} catch (IdentityProviderException $exception) {
			$response = $exception->getResponseBody();
			if (is_array($response) && isset($response['error'])) {
				if ($response['error'] === 'user_is_blocked') {
					throw new Exceptions\UserIsBlockedException();
				} elseif ($response['error'] === 'registration_mail_not_confirmed') {
					throw new Exceptions\RegistrationEmailNotConfirmedException();
				}
			}
			throw new Exceptions\InvalidCredentialException();
		}

		return $userIdentity;
	}
}

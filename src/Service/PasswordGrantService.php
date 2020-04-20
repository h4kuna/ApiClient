<?php declare(strict_types=1);

namespace MirkoHuttner\ApiClient\Service;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Token\AccessTokenInterface;

class PasswordGrantService
{
	public const GRANT = 'password';

	private GenericProvider $provider;

	public function __construct(GenericProvider $provider)
	{
		$this->provider = $provider;
	}

	public function getAccessToken(string $username, string $password): AccessTokenInterface
	{
		return $this->provider->getAccessToken(self::GRANT, ['username' => $username, 'password' => $password]);
	}

	public function getRefreshToken(AccessTokenInterface $token): ?AccessTokenInterface
	{
		try {
			return $this->provider->getAccessToken('refresh_token', ['refresh_token' => $token->getRefreshToken()]);
		} catch (IdentityProviderException $exception) {
			return null;
		}
	}
}

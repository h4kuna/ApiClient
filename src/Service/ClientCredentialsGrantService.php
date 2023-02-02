<?php declare(strict_types=1);

namespace MirkoHuttner\ApiClient\Service;

use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Nette\Utils\FileSystem;

class ClientCredentialsGrantService
{
	public const GRANT = 'client_credentials';
	private const SECONDS_TO_EXPIRATION = 120;

	private string $clientCredentialsTempTokenFile;

	private GenericProvider $provider;


	public function __construct(string $clientCredentialsTempTokenFile, GenericProvider $provider)
	{
		$this->clientCredentialsTempTokenFile = $clientCredentialsTempTokenFile;
		$this->provider = $provider;
	}


	public function getAccessToken(bool $invalidate = false): AccessTokenInterface
	{
		if ($invalidate) {
			return $this->getNewToken();
		}

		$tmp = $this->getTokenFromTemp();
		if ($tmp !== null && !$tmp->hasExpired() && $tmp->getExpires() !== null && ($tmp->getExpires() - self::SECONDS_TO_EXPIRATION) > time()) {
			return $tmp;
		}

		return $this->getNewToken();
	}


	private function getNewToken(): AccessTokenInterface
	{
		$token = $this->provider->getAccessToken(self::GRANT);
		$this->storeToken($token);

		return $token;
	}


	private function getTokenFromTemp(): ?AccessTokenInterface
	{
		$token = null;
		if (file_exists($this->clientCredentialsTempTokenFile)) {
			$token = unserialize(FileSystem::read($this->clientCredentialsTempTokenFile));
			$token = $token instanceof AccessTokenInterface ? $token : null;
		}

		return $token;
	}


	private function storeToken(AccessTokenInterface $token): void
	{
		FileSystem::write($this->clientCredentialsTempTokenFile, serialize($token));
	}

}

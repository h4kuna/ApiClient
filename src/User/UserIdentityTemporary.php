<?php declare(strict_types=1);

namespace MirkoHuttner\ApiClient\User;

use League\OAuth2\Client\Token\AccessTokenInterface;
use Nette\Security\IIdentity;
use Ramsey\Uuid\UuidInterface;

class UserIdentityTemporary implements IIdentity
{
	private UuidInterface $id;

	private AccessTokenInterface $authToken;

	public function __construct(UuidInterface $id, AccessTokenInterface $token)
	{
		$this->id = $id;
		$this->authToken = $token;
	}

	/**
	 * @return UuidInterface
	 */
	public function getId(): UuidInterface
	{
		return $this->id;
	}

	/**
	 * @return array
	 */
	public function getRoles(): array
	{
		return [];
	}

	/**
	 * @return AccessTokenInterface
	 */
	public function getAuthToken(): AccessTokenInterface
	{
		return $this->authToken;
	}

	/**
	 * @param AccessTokenInterface $authToken
	 */
	public function setAuthToken(AccessTokenInterface $authToken): void
	{
		$this->authToken = $authToken;
	}
}

<?php declare(strict_types=1);

namespace MirkoHuttner\ApiClient\User;

use League\OAuth2\Client\Token\AccessTokenInterface;
use Nette\Security\IIdentity;

abstract class UserIdentity implements IUserIdentity, IIdentity
{
	protected AccessTokenInterface $authToken;

	/**
	 * @return mixed
	 */
	abstract public function getId();

	/**
	 * @return mixed
	 */
	abstract public function getData();

	public function getAuthToken(): AccessTokenInterface
	{
		return $this->authToken;
	}

	public function setAuthToken(AccessTokenInterface $authToken): void
	{
		$this->authToken = $authToken;
	}
}

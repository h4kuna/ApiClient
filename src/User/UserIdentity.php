<?php declare(strict_types=1);

namespace MirkoHuttner\ApiClient\User;

use Nette\Security\IIdentity;

abstract class UserIdentity implements IUserIdentity, IIdentity
{
	/**
	 * @return mixed
	 */
	abstract public function getId();

	/**
	 * @return mixed
	 */
	abstract public function getData();
}

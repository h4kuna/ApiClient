<?php declare(strict_types=1);

namespace MirkoHuttner\ApiClient\User;

interface IUserIdentity
{
	/**
	 * @return mixed
	 */
	public function getId();

	/**
	 * @return mixed
	 */
	public function getData();
}

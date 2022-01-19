<?php declare(strict_types=1);

namespace MirkoHuttner\ApiClient\User\Exceptions;

use MirkoHuttner\ApiClient\User\Authenticator;

final class UserIsBlockedException extends UserException
{

	protected function getDefaultCode(): int
	{
		return Authenticator::USER_BLOCKED;
	}

}

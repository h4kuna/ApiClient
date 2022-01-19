<?php declare(strict_types=1);

namespace MirkoHuttner\ApiClient\User\Exceptions;

use MirkoHuttner\ApiClient\User\Authenticator;

final class InvalidCredentialException extends UserException
{

	protected function getDefaultCode(): int
	{
		return Authenticator::INVALID_CREDENTIAL;
	}

}

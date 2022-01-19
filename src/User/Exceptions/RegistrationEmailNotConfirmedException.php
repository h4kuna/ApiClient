<?php declare(strict_types=1);

namespace MirkoHuttner\ApiClient\User\Exceptions;

use MirkoHuttner\ApiClient\User\Authenticator;

final class RegistrationEmailNotConfirmedException extends UserException
{

	protected function getDefaultCode(): int
	{
		return Authenticator::REGISTRATION_MAIL_NOT_CONFIRMED;
	}

}

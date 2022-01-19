<?php declare(strict_types=1);

namespace MirkoHuttner\ApiClient\User\Exceptions;

use Nette\Security\AuthenticationException;

abstract class UserException extends AuthenticationException
{

	/**
	 * @param string $message
	 * @param int $code
	 */
	public function __construct($message = "", $code = 0, \Throwable $previous = null)
	{
		if ($code === 0) {
			$code = $this->getDefaultCode();
		}
		parent::__construct($message, $code, $previous);
	}


	protected function getDefaultCode(): int
	{
		return 0;
	}

}

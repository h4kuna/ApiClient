<?php declare(strict_types=1);

namespace MirkoHuttner\ApiClient\Exception;

use MirkoHuttner\ApiClient\ValueObject\ValidationError;

class BadRequestException extends \Nette\Application\BadRequestException
{
	private ?array $validationErrors = null;

	/**
	 * @return ValidationError[]|null
	 */
	public function getValidationErrors()
	{
		return $this->validationErrors;
	}

	/**
	 * @param ValidationError[]|null $validationErrors
	 */
	public function setValidationErrors(?array $validationErrors): void
	{
		$this->validationErrors = $validationErrors;
	}
}

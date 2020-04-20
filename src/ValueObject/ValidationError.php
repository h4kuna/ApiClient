<?php declare(strict_types=1);

namespace MirkoHuttner\ApiClient\ValueObject;

class ValidationError
{
	public ?string $parameter;

	public string $message;

	public function __construct(?string $parameter, string $message)
	{
		$this->parameter = $parameter;
		$this->message = $message;
	}
}

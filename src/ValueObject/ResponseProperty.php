<?php declare(strict_types=1);

namespace MirkoHuttner\ApiClient\ValueObject;

class ResponseProperty
{
	public string $name;

	public bool $nullable;

	public string $type;

	public string $description;

	/**
	 * @var array<ResponseProperty>
	 */
	public array $children = [];

	public function __construct(string $name, bool $nullable, string $type, string $description)
	{
		$this->name = $name;
		$this->nullable = $nullable;
		$this->type = $type;
		$this->description = $description;
	}
}

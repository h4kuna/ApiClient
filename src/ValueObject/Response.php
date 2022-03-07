<?php declare(strict_types=1);

namespace MirkoHuttner\ApiClient\ValueObject;

class Response
{
	public string $method;

	public string $path;

	public ?string $authGrantType = null;

	/**
	 * @var ResponseProperty[]|null
	 */
	public ?array $responseProperties = null;

	public bool $responsePropertiesArray = false;

	/**
	 * @var RequestParameter[]|null
	 */
	public ?array $requestBodyParameters = null;

	/**
	 * @var RequestParameter[]|null
	 */
	public ?array $requestQueryParameters = null;

	public bool $isBinary = false;

	public bool $hasCount = false;
	
	public string $source = '';

	public function __construct(string $method, string $path)
	{
		$this->method = $method;
		$this->path = $path;
	}
}

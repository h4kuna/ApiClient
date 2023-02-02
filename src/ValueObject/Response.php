<?php declare(strict_types=1);

namespace MirkoHuttner\ApiClient\ValueObject;

class Response
{
	public string $method;

	public string $path;

	public string $authGrantType = '';

	/**
	 * @var array<ResponseProperty>
	 */
	public array $responseProperties = [];

	public bool $responsePropertiesArray = false;

	/**
	 * @var array<RequestParameter>
	 */
	public array $requestBodyParameters = [];

	/**
	 * @var array<RequestParameter>
	 */
	public array $requestQueryParameters = [];

	public bool $isBinary = false;

	public bool $hasCount = false;
	
	public string $source = '';

	public function __construct(string $method, string $path)
	{
		$this->method = $method;
		$this->path = $path;
	}
}

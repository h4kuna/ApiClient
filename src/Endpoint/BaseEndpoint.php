<?php declare(strict_types=1);

namespace MirkoHuttner\ApiClient\Endpoint;

use MirkoHuttner\ApiClient\Service\ApiClientService;

abstract class BaseEndpoint implements IEndpoint
{
	public function getAuthType(): string
	{
		return ApiClientService::AUTH_GRANT_CLIENT_CREDENTIALS;
	}

	public function getEntityClassName(): ?string
	{
		return null;
	}

	public function isArray(): bool
	{
		return false;
	}

	public function getData(): ?array
	{
		return null;
	}

	public function getAdditionalQueryData(): ?array
	{
		return null;
	}

	public function isBinary(): bool
	{
		return false;
	}

	public function hasCount(): bool
	{
		return false;
	}
}

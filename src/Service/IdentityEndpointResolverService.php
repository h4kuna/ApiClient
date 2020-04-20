<?php declare(strict_types=1);

namespace MirkoHuttner\ApiClient\Service;

abstract class IdentityEndpointResolverService implements IIdentityEndpointResolverService
{
	protected ApiClientService $apiClientService;

	public function __construct(ApiClientService $apiClientService)
	{
		$this->apiClientService = $apiClientService;
	}
}

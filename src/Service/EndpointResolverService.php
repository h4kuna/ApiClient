<?php declare(strict_types=1);

namespace MirkoHuttner\ApiClient\Service;

use League\OAuth2\Client\Token\AccessTokenInterface;
use MirkoHuttner\ApiClient\Endpoint\IEndpoint;
use MirkoHuttner\ApiClient\Exception\UnauthorizedException;
use MirkoHuttner\ApiClient\Exception\UnexpectedStructureReturnedException;
use MirkoHuttner\ApiClient\Exception\UnsupportedHttpMethodException;
use MirkoHuttner\ApiClient\Exception\UserNotLoggedException;
use MirkoHuttner\ApiClient\Mapper\EntityMapper;
use MirkoHuttner\ApiClient\User\User;
use MirkoHuttner\ApiClient\ValueObject\ResponseDataCount;
use Psr\Http\Message\ResponseInterface;

class EndpointResolverService
{
	private ApiClientService $apiClientService;

	private ClientCredentialsGrantService $clientCredentialsGrantService;

	private User $user;

	private ?ResponseInterface $lastResponse = null;

	public function __construct(
		ApiClientService $apiClientService,
		ClientCredentialsGrantService $clientCredentialsGrantService,
		User $user
	) {
		$this->apiClientService = $apiClientService;
		$this->clientCredentialsGrantService = $clientCredentialsGrantService;
		$this->user = $user;
	}

	/**
	 * @param IEndpoint $endpoint
	 * @return mixed
	 * @throws UnexpectedStructureReturnedException
	 * @throws UserNotLoggedException
	 */
	public function call(IEndpoint $endpoint, bool $returnCount = false)
	{
		try {
			$token = $this->getTokenForEndpoint($endpoint);
			$url = $endpoint->getUrl();
			$data = $endpoint->getData();
			$additionalQueryData = $endpoint->getAdditionalQueryData();
			switch ($endpoint->getMethod()) {
				case ApiClientService::METHOD_GET:
					$response = $this->apiClientService->get($url, $data, $token);
					break;
				case ApiClientService::METHOD_POST:
					$response = $this->apiClientService->post($url, $data, $token, $additionalQueryData);
					break;
				case ApiClientService::METHOD_PUT:
					$response = $this->apiClientService->put($url, $data, $token, $additionalQueryData);
					break;
				case ApiClientService::METHOD_PATCH:
					$response = $this->apiClientService->patch($url, $data, $token, $additionalQueryData);
					break;
				case ApiClientService::METHOD_DELETE:
					$response = $this->apiClientService->delete($url, $data, $token, $additionalQueryData);
					break;
				default:
					throw new UnsupportedHttpMethodException();
			}

			$this->lastResponse = $response;
			$data = null;
			if ($endpoint->getEntityClassName() !== null) {
				$d = $this->getData($response, $endpoint);
				$data = $d->data;

				if ($returnCount) {
					return $d->count;
				}
			}

			return $data;
		} catch (UnauthorizedException $exception) {
			$this->user->logout(true);
			throw $exception;
		}
	}

	public function getLastResponse(): ResponseInterface
	{
		if ($this->lastResponse === null) {
			throw new \RuntimeException('First time call api.');
		}

		return $this->lastResponse;
	}

	protected function getData(ResponseInterface $response, IEndpoint $endpoint): ResponseDataCount
	{
		$data = $this->apiClientService->getResponseData($response, $endpoint->isBinary());
		$returnData = null;
		$entityClassName = $endpoint->getEntityClassName();
		if ($entityClassName !== null) {
			if ($endpoint->isArray()) {
				$returnData = [];
				assert(is_iterable($data->data));
				foreach ($data->data as $d) {
					$returnData[] = EntityMapper::create($d, $entityClassName);
				}
			} else {
				$returnData = EntityMapper::create($data->data, $entityClassName, $endpoint->isBinary());
			}
		}

		return new ResponseDataCount($returnData, $data->count);
	}

	/**
	 * @param IEndpoint $endpoint
	 * @return AccessTokenInterface
	 * @throws UserNotLoggedException
	 */
	protected function getTokenForEndpoint(IEndpoint $endpoint): AccessTokenInterface
	{
		$token = $this->user->getAuthToken();
		if ($this->user->isLoggedIn() && $token !== null) {
			return $token;
		}

		if ($endpoint->getAuthType() === ApiClientService::AUTH_GRANT_PASSWORD) {
			throw new UserNotLoggedException;
		} else {
			return $this->clientCredentialsGrantService->getAccessToken();
		}
	}
}

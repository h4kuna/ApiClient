<?php declare(strict_types=1);

namespace MirkoHuttner\ApiClient\Service;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Token\AccessTokenInterface;
use MirkoHuttner\ApiClient\Exception\BadRequestException;
use MirkoHuttner\ApiClient\Exception\RemoteServerErrorException;
use MirkoHuttner\ApiClient\Exception\UnauthorizedException;
use MirkoHuttner\ApiClient\Exception\UnexpectedStructureReturnedException;
use MirkoHuttner\ApiClient\ValueObject\ResponseDataCount;
use MirkoHuttner\ApiClient\ValueObject\ValidationError;
use Nette\Http\FileUpload;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class ApiClientService
{
	public const METHOD_GET = 'GET';
	public const METHOD_POST = 'POST';
	public const METHOD_PUT = 'PUT';
	public const METHOD_DELETE = 'DELETE';
	public const METHOD_PATCH = 'PATCH';

	public const AUTH_GRANT_CLIENT_CREDENTIALS = 'grant-client-credentials';
	public const AUTH_GRANT_PASSWORD = 'grant-password';

	private static bool $tokenInvalidated = false;

	private string $baseUrl;

	private ClientCredentialsGrantService $clientCredentialsGrantService;

	private GenericProvider $provider;

	public function __construct(
		string $baseUrl,
		GenericProvider $provider,
		ClientCredentialsGrantService $clientCredentialsGrantService
	) {
		$this->baseUrl = $baseUrl;
		$this->provider = $provider;
		$this->clientCredentialsGrantService = $clientCredentialsGrantService;
	}

	public function getResponseData(ResponseInterface $response, bool $isBinary = false): ResponseDataCount
	{
		$data = $response->getBody()->getContents();
		if ($isBinary) {
			return new ResponseDataCount($data);
		}

		$data = json_decode($data);
		if ($data && isset($data->data)) {
			return new ResponseDataCount($data->data, isset($data->count) ? (int) $data->count : null);
		} else {
			throw new UnexpectedStructureReturnedException();
		}
	}

	public function get(string $url, ?array $data = null, ?AccessTokenInterface $token = null): ResponseInterface
	{
		if ($data) {
			$url .=  '?' . $this->buildQueryData($data);
		}
		return $this->request(self::METHOD_GET, $url, $token);
	}

	public function post(string $url, ?array $data, ?AccessTokenInterface $token = null, ?array $additionalQueryData = null): ResponseInterface
	{
		$url = $this->addAdditionalQueryData($url, $additionalQueryData);
		return $this->request(self::METHOD_POST, $url, $token, $data);
	}

	public function put(string $url, ?array $data, ?AccessTokenInterface $token = null, ?array $additionalQueryData = null): ResponseInterface
	{
		$url = $this->addAdditionalQueryData($url, $additionalQueryData);
		return $this->request(self::METHOD_PUT, $url, $token, $data);
	}

	public function patch(string $url, ?array $data, ?AccessTokenInterface $token = null, ?array $additionalQueryData = null): ResponseInterface
	{
		$url = $this->addAdditionalQueryData($url, $additionalQueryData);
		return $this->request(self::METHOD_PATCH, $url, $token, $data);
	}

	public function delete(string $url, ?array $data, ?AccessTokenInterface $token = null, ?array $additionalQueryData = null): ResponseInterface
	{
		$url = $this->addAdditionalQueryData($url, $additionalQueryData);
		return $this->request(self::METHOD_DELETE, $url, $token, $data);
	}

	private function addAdditionalQueryData(string $url, ?array $additionalQueryData = null): string
	{
		if ($additionalQueryData) {
			$d = $this->buildQueryData($additionalQueryData);
			if (strpos($url, '?')) {
				$url .=  '&' . $d;
			} else {
				$url .=  '?' . $d;
			}
		}
		return $url;
	}

	/**
	 * @param string $method
	 * @param string $url
	 * @param AccessTokenInterface|null $token
	 * @param array|null $data
	 * @return ResponseInterface
	 * @throws BadRequestException
	 * @throws RemoteServerErrorException
	 */
	protected function request(string $method, string $url, ?AccessTokenInterface $token = null, ?array $data = null): ResponseInterface
	{
		$authRequest = $this->getAuthenticatedRequest($method, $url, $token, $data);
		$response = $this->getClient()->send($authRequest, ['http_errors' => false]);
		$status = $response->getStatusCode();
		if ($status >= 500 && $status < 600) {
			throw new RemoteServerErrorException($response->getReasonPhrase(), $status);
		} elseif ($status === 401) {
			// try to get new token if status === 401 for the first time
			if (self::$tokenInvalidated === false) {
				$token = $this->clientCredentialsGrantService->getAccessToken(true);
				self::$tokenInvalidated = true;
				return $this->request($method, $url, $token, $data);
			}
			throw new UnauthorizedException($response->getReasonPhrase(), $status);
		} elseif ($status >= 400 && $status < 500) {
			$errorData = json_decode($response->getBody()->getContents());
			$ex = new BadRequestException($response->getReasonPhrase(), $status);
			if ($errorData && isset($errorData->data->clientErrors)) {
				$validationErrors = null;
				foreach ($errorData->data->clientErrors as $error) {
					$validationErrors[] = new ValidationError($error->parameter, $error->message);
				}
				$ex->setValidationErrors($validationErrors);
			}
			throw $ex;
		}
		return $response;
	}

	protected function getAuthenticatedRequest(string $method, string $url, ?AccessTokenInterface $token = null, ?array $data = null): RequestInterface
	{
		if (!$token) {
			$token = $this->clientCredentialsGrantService->getAccessToken();
		}
		$options = [];

		if ($data) {
			/** @var FileUpload|bool $sendingOnlyOneFile */
			$sendingOnlyOneFile = false;
			if (is_iterable($data)) {
				foreach ($data as $key => $d) {
					if ($d instanceof FileUpload) {
						$sendingOnlyOneFile = $d;
						$data[$key] = base64_encode($d->contents ?: '');
					} elseif ($d instanceof \DateTime) {
						$data[$key] = $d->format('Y-m-d H:i:s');
					}
				}
			}
			$sendingOnlyOneFile = $sendingOnlyOneFile ? \count($data) === 1 : false;

			if (!$sendingOnlyOneFile) {
				$options = ['body' => json_encode($data)];
			} else {
				$headers = ['Content-Type' => 'image/jpeg', 'Content-Length' => $d->size];
				$options = ['body' => $d->contents, 'headers' => $headers];
			}
		}

		return $this->provider->getAuthenticatedRequest($method, $this->baseUrl . $url, $token, $options);
	}

	protected function getClient(): ClientInterface
	{
		return new Client;
	}

	protected function buildQueryData(array $data): string
	{
		$d = [];
		foreach ($data as $key => $di) {
			if (is_bool($di)) {
				$d[$key] = $di ? 'true' : 'false';
			} else {
				$d[$key] = $di;
			}
		}
		return http_build_query($d);
	}
}

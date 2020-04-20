<?php declare(strict_types=1);

namespace MirkoHuttner\ApiClient\Service;

use MirkoHuttner\ApiClient\ValueObject\RequestParameter;
use MirkoHuttner\ApiClient\ValueObject\Response;
use MirkoHuttner\ApiClient\ValueObject\ResponseProperty;
use Nette\Http\FileUpload;

class EndpointSchemaTranslatorService
{
	private ApiClientService $apiClientService;

	private string $schemaUrl;

	public function __construct(
		string $schemaUrl,
		ApiClientService $apiClientService
	) {
		$this->schemaUrl = $schemaUrl;
		$this->apiClientService = $apiClientService;
	}

	/**
	 * @return Response[]
	 */
	public function getResponses(): array
	{
		$paths = $this->getSchemaPaths();
		$responsesData = [];
		/** @phpstan-ignore-next-line */
		foreach ($paths as $pathName => $path) {
			foreach ($path as $method => $params) {
				$r = new Response(strtoupper($method), $pathName);

				// parameters in query
				$queryParams = null;
				if (isset($params->parameters)) {
					foreach ($params->parameters as $qParam) {
						if ($qParam->in === 'query') {
							if (!isset($qParam->allowEmptyValue)) {
								$required = false;
							} else {
								$required = $qParam->allowEmptyValue ? false : true;
							}
							if (isset($qParam->required) && $qParam->required) {
								$required = true;
							}

							$schema = $qParam->schema;
							$type = $this->resolveType($schema->type, isset($schema->format) ? $schema->format : null);
							$description = isset($qParam->description) ? $qParam->description : null;
							$queryParams[] = new RequestParameter($qParam->name, $required, $type, $description);
						}
					}
				}
				$r->requestQueryParameters = $queryParams;

				// request body parameters
				$bodyParams = null;
				if (isset($params->requestBody->content->{'application/json'}->schema)) {
					$s = $params->requestBody->content->{'application/json'}->schema;
					foreach ($s->properties as $name => $property) {
						$required = isset($s->required) ? in_array($name, $s->required) : false;
						$type = $this->resolveType($property->type, isset($property->format) ? $property->format : null);
						$description = isset($s->description) ? $s->description : null;
						$bodyParams[] = new RequestParameter($name, $required, $type, $description);
					}
				}

				// image file
				if (isset($params->requestBody->content->{'image/jpeg'})) {
					$bodyParams[] = new RequestParameter('image', true, FileUpload::class, null);
				}

				$r->requestBodyParameters = $bodyParams;

				// response data
				foreach ($params->responses as $code => $response) {
					if ($code === '200') {
						if (isset($response->content->{'application/json'}->schema->properties->data)) {
							$data = $response->content->{'application/json'}->schema->properties->data;
							if ($data->type === 'array') {
								$properties = $data->items->properties;
								$isArray = true;
							} elseif ($data->type === 'object') {
								$properties = $data->properties;
								$isArray = false;
							} else {
								throw new \InvalidArgumentException('Type not supported. Type: ' . $data->type);
							}

							$values = $this->propertiesToResponse((array) $properties);

							$r->responseProperties = $values;
							$r->responsePropertiesArray = $isArray;
							$r->hasCount = isset($response->content->{'application/json'}->schema->properties->count);
						} elseif (isset($response->content->{'application/pdf'})) {
							$r->responseProperties = [new ResponseProperty('content', false, 'string', null)];
							$r->responsePropertiesArray = false;
							$r->isBinary = true;
						}
					} elseif ($code === '401' && isset($response->description)) {
						if ($response->description === 'Client is not authorized using OAuth2.') {
							$r->authGrantType = ApiClientService::AUTH_GRANT_CLIENT_CREDENTIALS;
						} elseif ($response->description === 'Client is not authorized with OAuth2 Password grant.') {
							$r->authGrantType = ApiClientService::AUTH_GRANT_PASSWORD;
						}
					}
				}

				$responsesData[] = $r;
			}
		}
		return $responsesData;
	}

	/**
	 * @return ResponseProperty[]|null
	 */
	private function propertiesToResponse(array $properties): ?array
	{
		$values = null;
		foreach ($properties as $name => $property) {
			$children = null;
			if ($property->type === 'object') {
				$children = $this->propertiesToResponse((array) $property->properties);
				$type = 'object';
			} else {
				$type = $this->resolveType($property->type, isset($property->format) ? $property->format : null);
			}
			$nullable = isset($property->nullable) ? $property->nullable : false;
			$description = isset($property->description) ? $property->description : null;
			$re = new ResponseProperty($name, $nullable, $type, $description);
			$re->children = $children;
			$values[] = $re;
		}
		return $values;
	}

	private function resolveType(string $type, ?string $format): string
	{
		if ($type === 'string' && $format === 'date') {
			return '\DateTime';
		} elseif ($type === 'string' && $format === 'date-time') {
			return '\DateTime';
		} elseif ($type === 'string' && $format === 'uuid') {
			return '\Ramsey\Uuid\UuidInterface';
		} elseif ($type === 'boolean' || $type === 'bool') {
			return 'bool';
		} elseif ($type === 'array') {
			return 'array';
		} elseif ($type === 'integer') {
			return 'int';
		} elseif ($type === 'number' && $format === 'float') {
			return 'float';
		} elseif ($type === 'string') {
			return 'string';
		} else {
			throw new \InvalidArgumentException('Type or format not supported. Type: ' . $type);
		}
	}

	private function getSchemaPaths(): \stdClass
	{
		$response = $this->apiClientService->get($this->schemaUrl);
		$data = $response->getBody()->getContents();
		$data = json_decode($data);
		if (!$data) {
			throw new \InvalidArgumentException;
		}
		return $data->paths;
	}
}

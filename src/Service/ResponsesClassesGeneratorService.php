<?php declare(strict_types=1);

namespace MirkoHuttner\ApiClient\Service;

use MirkoHuttner\ApiClient\Endpoint\BaseEndpoint;
use MirkoHuttner\ApiClient\Entity\BaseEntity;
use MirkoHuttner\ApiClient\RequestValue\BaseRequestValue;
use MirkoHuttner\ApiClient\ValueObject\RequestParameter;
use MirkoHuttner\ApiClient\ValueObject\Response;
use MirkoHuttner\ApiClient\ValueObject\ResponseProperty;
use Nette\Http\Url;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;

class ResponsesClassesGeneratorService
{
	private string $namespaceStart;

	private Url $url;

	private EndpointSchemaTranslatorService $endpointSchemaTranslatorService;

	private PhpFileFromNamespaceCreatorService $phpFileFromNamespaceCreatorService;

	public function __construct(
		string $namespaceStart,
		Url $url,
		EndpointSchemaTranslatorService $endpointSchemaTranslatorService,
		PhpFileFromNamespaceCreatorService $phpFileFromNamespaceCreatorService
	) {
		$this->namespaceStart = self::normalizeNamespace($namespaceStart);
		$this->url = $url;
		$this->endpointSchemaTranslatorService = $endpointSchemaTranslatorService;
		$this->phpFileFromNamespaceCreatorService = $phpFileFromNamespaceCreatorService;
	}

	public function generate(): void
	{
		$responses = $this->endpointSchemaTranslatorService->getResponses();
		foreach ($responses as $response) {
			$this->generateEndpoint($response);
		}
	}

	private function generateEndpoint(Response $response): PhpNamespace
	{
		$suffix = 'Endpoint';
		$className = $this->getClassName($response) . $suffix;
		$namespaceName = $this->getNamespace($response) . '\\' . $suffix;
		$namespace = new PhpNamespace($namespaceName);
		$namespace->addUse(BaseEndpoint::class);
		$endpointClass = $namespace->addClass($className);
		$endpointClass->setFinal();
		$endpointClass->setExtends(BaseEndpoint::class);
		$namespace->addUse(ApiClientService::class);
		$constructor = $endpointClass->addMethod('__construct');

		if ($response->source !== '') {
			$endpointClass->addComment(sprintf('@see %s', $response->source));
		}

		// auth type
		if ($response->authGrantType) {
			$getAuthType = $endpointClass->addMethod('getAuthType');
			$getAuthType->setReturnType('string');
			$gt = $response->authGrantType;
			$type = $gt === ApiClientService::AUTH_GRANT_PASSWORD ? 'ApiClientService::AUTH_GRANT_PASSWORD' : 'ApiClientService::AUTH_GRANT_CLIENT_CREDENTIALS';
			$getAuthType->setBody('return ' . $type . ';');
		}

		// url
		$path = str_replace($this->url->getPath(), '', $response->path);
		$getUrl = $endpointClass->addMethod('getUrl');
		$getUrl->setReturnType('string');
		$getUrl->setBody("return '" . $path . "';");

		// method
		if ($response->method === ApiClientService::METHOD_GET) {
			$m = 'ApiClientService::METHOD_GET';
		} elseif ($response->method === ApiClientService::METHOD_POST) {
			$m = 'ApiClientService::METHOD_POST';
		} elseif ($response->method === ApiClientService::METHOD_DELETE) {
			$m = 'ApiClientService::METHOD_DELETE';
		} elseif ($response->method === ApiClientService::METHOD_PUT) {
			$m = 'ApiClientService::METHOD_PUT';
		} elseif ($response->method === ApiClientService::METHOD_PATCH) {
			$m = 'ApiClientService::METHOD_PATCH';
		}

		if (isset($m)) {
			$getMethod = $endpointClass->addMethod('getMethod');
			$getMethod->setReturnType('string');
			$getMethod->setBody("return " . $m . ";");
		}

		// request parameters
		$parametersType = ['body' => $response->requestBodyParameters, 'query' => $response->requestQueryParameters];
		foreach ($parametersType as $type => $parameters) {
			if (!$parameters) {
				continue;
			}

			$entityNs = $this->generateRequestEntity($response, $parameters, $type);
			$cs = $entityNs->getClasses();
			/** @var ClassType $class */
			$class = end($cs);
			$csName = $entityNs->getName() . '\\' . ($class->getName() ?: '');
			$namespace->addUse($csName);
			$propName = lcfirst($class->getName() ?: '');
			$p = $endpointClass->addProperty($propName);
			$p->setType($csName);

			if ($type === 'query' && $response->method !== ApiClientService::METHOD_GET) {
				$getAdditionalQueryData = $endpointClass->addMethod('getAdditionalQueryData');
				$getAdditionalQueryData->setReturnType('array');
				$getAdditionalQueryData->setBody('return $this->' . $propName . '->toArray();');
			} else {
				$getData = $endpointClass->addMethod('getData');
				$getData->setReturnType('array');
				$getData->setBody('return $this->' . $propName . '->toArray();');
			}
		}

		// response parameters
		if ($response->responseProperties) {
			$entityNs = $this->generateResponseEntity($response);
			$cs = $entityNs->getClasses();
			/** @var ClassType $class */
			$class = end($cs);
			$csName = $entityNs->getName() . '\\' . $class->getName();
			$namespace->addUse($csName);

			$getEntityClassName = $endpointClass->addMethod('getEntityClassName');
			$getEntityClassName->setReturnType('string');
			$getEntityClassName->setBody('return ' . $class->getName() . '::class;');

			$isArray = $endpointClass->addMethod('isArray');
			$isArray->setReturnType('bool');
			$isArray->setBody("return " . ($response->responsePropertiesArray ? 'true' : 'false') . ";");

			if ($response->isBinary) {
				$isBinary = $endpointClass->addMethod('isBinary');
				$isBinary->setReturnType('bool');
				$isBinary->setBody("return true;");
			}

			if ($response->hasCount) {
				$isBinary = $endpointClass->addMethod('hasCount');
				$isBinary->setReturnType('bool');
				$isBinary->setBody("return true;");
			}
		}

		// constructor
		if ($endpointClass->getProperties()) {
			$body = '';
			foreach ($endpointClass->getProperties() as $property) {
				$constructor->addParameter($property->getName())->setType((string) $property->getType());
				$body .= '$this->' . $property->getName() . ' = ' . '$' . $property->getName() . ";\n";
			}
			$constructor->addBody($body);
		} else {
			$endpointClass->removeMethod('__construct');
		}

		$this->phpFileFromNamespaceCreatorService->create($namespace, $this->namespaceStart);
		return $namespace;
	}

	/**
	 * @param Response $response
	 * @param RequestParameter[] $params
	 */
	private function generateRequestEntity(Response $response, array $params, string $type): PhpNamespace
	{
		if (!$params) {
			throw new \InvalidArgumentException('Response has no request parameters.');
		}

		$suffix = ucfirst($type) . 'RequestEntity';
		$className = $this->getClassName($response) . $suffix;
		$namespaceName = $this->getNamespace($response) . '\\' . $suffix;

		$namespace = new PhpNamespace($namespaceName);
		$class = $namespace->addClass($className);
		$class->setFinal();
		$namespace->addUse(BaseRequestValue::class);
		$class->setExtends(BaseRequestValue::class);

		$constructorParams = [];
		foreach ($params as $param) {
			$nullable = !$param->required;
			$p = $class->addProperty($param->name);
			$p->setType($param->type);
			$p->setNullable($nullable);
			if ($p->isNullable()) {
				$p->setInitialized(true);
			}
			if ($param->description) {
				$p->addComment($param->description);
			}
			if (!$nullable) {
				$constructorParams[] = $param;
			}
		}

		if ($constructorParams) {
			$constructor = $class->addMethod('__construct');
			$body = '';
			foreach ($constructorParams as $param) {
				$constructor->addParameter($param->name)->setType($param->type);
				$body .= '$this->' . $param->name . ' = $' . $param->name . ";\n";
			}
			$constructor->addBody($body);
		}

		$this->phpFileFromNamespaceCreatorService->create($namespace, $this->namespaceStart);
		return $namespace;
	}

	/**
	 * @param Response $response
	 * @param ResponseProperty[]|null $props
	 * @return PhpNamespace
	 */
	private function generateResponseEntity(Response $response, ?array $props = null, string $subName = ''): PhpNamespace
	{
		if (!$props) {
			$props = $response->responseProperties;
		}
		if (!$props) {
			throw new \InvalidArgumentException('Response has no properties.');
		}

		$suffix = 'ResponseEntity';
		$className = ucfirst($subName) . $this->getClassName($response) . $suffix;
		$namespaceName = $this->getNamespace($response) . '\\' . $suffix;

		$namespace = new PhpNamespace($namespaceName);
		$class = $namespace->addClass($className);
		$class->setFinal();
		$namespace->addUse(BaseEntity::class);
		$class->setExtends(BaseEntity::class);
		$constructor = $class->addMethod('__construct');

		$constructorParams = [];
		foreach ($props as $prop) {
			$p = $class->addProperty($prop->name);
			if ($prop->type === 'object') {
				$entityNs = $this->generateResponseEntity($response, $prop->children, $prop->name);
				$cs = $entityNs->getClasses();
				/** @var ClassType $classSub */
				$classSub = end($cs);
				$prop->type = $entityNs->getName() . '\\' . $classSub->getName();
				$p->setType($prop->type);
			} else {
				$p->setType($prop->type);
			}
			$p->setNullable($prop->nullable);
			if ($p->isNullable()) {
				$p->setInitialized(true);
			}
			if ($prop->description) {
				$p->addComment($prop->description);
			}
			if (!$prop->nullable) {
				$constructorParams[] = $prop;
			}
		}

		if ($constructorParams) {
			$body = '';
			foreach ($constructorParams as $param) {
				$constructor->addParameter($param->name)->setType($param->type);
				$body .= '$this->' . $param->name . ' = $' . $param->name . ";\n";
			}
			$constructor->addBody($body);
		} else {
			$class->removeMethod('__construct');
		}

		$this->phpFileFromNamespaceCreatorService->create($namespace, $this->namespaceStart);
		return $namespace;
	}

	private function getClassName(Response $response): string
	{
		$p = substr($response->path, strrpos($response->path, '/') + 1);
		$p = self::replaceDash($p);
		$p = str_replace('{uuid}', 'Detail', $p);

		return ucfirst($p . ucfirst(strtolower($response->method)));
	}

	private function getNamespace(Response $response): string
	{
		$needle = '\\';
		$p = substr($response->path, 0, strrpos($response->path, '/'));
		$p = self::replaceDash($p);
		$p = ltrim(self::replaceAndUpperChar($p, '/', $needle) . $needle . 'Model', $needle);

		return $this->namespaceStart . str_replace($needle, 'Module\\', $p);
	}

	/**
	 * foo-bar => fooBar
	 */
	private static function replaceDash(string $input): string
	{
		return self::replaceAndUpperChar($input, '-');
	}

	/**
	 * foo{$char}bar -> foo{$replace}Bar
	 */
	private static function replaceAndUpperChar(string $haystack, string $char, string $replace = ''): string
	{
		return (string) preg_replace_callback(
			'/' . preg_quote($char, '/') . '([a-z0-9])/',
			static fn($find) => ($replace . strtoupper($find[1])),
			$haystack
		);
	}

	private static function normalizeNamespace(string $namespaceStart): string
	{
		$namespaceStart = trim($namespaceStart, '\\ ');
		if ($namespaceStart === '') {
			return '';
		}

		return $namespaceStart . '\\';
	}

}

<?php declare(strict_types=1);

namespace MirkoHuttner\ApiClient\DI;

use MirkoHuttner\ApiClient\Command\GenerateSchemaClassesCommand;
use MirkoHuttner\ApiClient\Service\ApiClientService;
use MirkoHuttner\ApiClient\Service\CacheStorage;
use MirkoHuttner\ApiClient\Service\ClientCredentialsGrantService;
use MirkoHuttner\ApiClient\Service\EndpointResolverService;
use MirkoHuttner\ApiClient\Service\EndpointSchemaTranslatorService;
use MirkoHuttner\ApiClient\Service\PasswordGrantService;
use MirkoHuttner\ApiClient\Service\PhpFileFromNamespaceCreatorService;
use MirkoHuttner\ApiClient\Service\ResponsesClassesGeneratorService;
use MirkoHuttner\ApiClient\Service\UserByTokenService;
use MirkoHuttner\ApiClient\Service\UserCacheService;
use MirkoHuttner\ApiClient\User\Authenticator;
use MirkoHuttner\ApiClient\User\UserStorage;
use Nette\DI\CompilerExtension;
use Nette\Schema\Expect;
use Nette\Schema\Schema;

class ApiClientExtension extends CompilerExtension
{
	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'baseUrl' => Expect::string(),
			'schemaUrl' => Expect::string('/v1/schema'),
			'generatedModelPath' => Expect::string(),
			'clientCredentialsTempTokenFile' => Expect::string(),
		]);
	}

	public function loadConfiguration()
	{
		$config = $this->config;
		$builder = $this->getContainerBuilder();

		// generator
		$builder->addDefinition($this->prefix('generateSchemaClassesCommand'))
			->setFactory(GenerateSchemaClassesCommand::class);

		$builder->addDefinition($this->prefix('endpointSchemaTranslatorService'))
			->setFactory(EndpointSchemaTranslatorService::class, [$config->schemaUrl]);

		$builder->addDefinition($this->prefix('phpFileFromNamespaceCreatorService'))
			->setFactory(PhpFileFromNamespaceCreatorService::class, [$config->generatedModelPath]);

		$builder->addDefinition($this->prefix('responsesClassesGeneratorService'))
			->setFactory(ResponsesClassesGeneratorService::class);

		// client
		$builder->addDefinition($this->prefix('clientCredentialsGrantService'))
			->setFactory(ClientCredentialsGrantService::class, [$config->clientCredentialsTempTokenFile]);

		$builder->addDefinition($this->prefix('apiClientService'))
			->setFactory(ApiClientService::class, [$config->baseUrl]);

		$builder->addDefinition($this->prefix('passwordGrantService'))
			->setFactory(PasswordGrantService::class);

		$builder->addDefinition($this->prefix('cacheStorage'))
			->setFactory(CacheStorage::class);

		$builder->addDefinition($this->prefix('endpointResolverService'))
			->setFactory(EndpointResolverService::class);

		// user
		$builder->removeDefinition('security.authenticator');
		$builder->addDefinition('security.authenticator')
			->setFactory(Authenticator::class);

		$builder->removeDefinition('security.userStorage');
		$builder->addDefinition('security.userStorage')
			->setFactory(UserStorage::class);

		$builder->addDefinition($this->prefix('userCacheService'))
			->setFactory(UserCacheService::class);

		$builder->addDefinition($this->prefix('userByTokenService'))
			->setFactory(UserByTokenService::class);
	}
}

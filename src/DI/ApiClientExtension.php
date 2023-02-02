<?php declare(strict_types=1);

namespace MirkoHuttner\ApiClient\DI;

use MirkoHuttner\ApiClient\Command\GenerateSchemaClassesCommand;
use MirkoHuttner\ApiClient\Service\ApiClientService;
use MirkoHuttner\ApiClient\Service\ClientCredentialsGrantService;
use MirkoHuttner\ApiClient\Service\EndpointResolverService;
use MirkoHuttner\ApiClient\Service\EndpointSchemaTranslatorService;
use MirkoHuttner\ApiClient\Service\PasswordGrantService;
use MirkoHuttner\ApiClient\Service\PhpFileFromNamespaceCreatorService;
use MirkoHuttner\ApiClient\Service\ResponsesClassesGeneratorService;
use MirkoHuttner\ApiClient\Service\UserByTokenService;
use MirkoHuttner\ApiClient\Service\UserCacheService;
use MirkoHuttner\ApiClient\User\Authenticator;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\Statement;
use Nette\Http\Url;
use Nette\Schema\Expect;
use Nette\Schema\Schema;

class ApiClientExtension extends CompilerExtension
{
	public function getConfigSchema(): Schema
	{
		$credentialsTempTokenFileDefault = isset($this->getContainerBuilder()->parameters['tempDir']) ?
			$this->getContainerBuilder()->parameters['tempDir'] . '/credentialsTempTokenFile' :
			null;

		return Expect::structure([
			'baseUrl' => Expect::string(),
			'namespaceStart' => Expect::string(''),
			'schemaUrl' => Expect::string('/v1/schema'),
			'generatedModelPath' => Expect::string(),
			'clientCredentialsTempTokenFile' => Expect::string($credentialsTempTokenFileDefault),
			'onlyPathsStartWith' => Expect::listOf('string')->default(['/v1/']),
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
			->setFactory(EndpointSchemaTranslatorService::class, [$config->schemaUrl, $config->onlyPathsStartWith]);

		$builder->addDefinition($this->prefix('phpFileFromNamespaceCreatorService'))
			->setFactory(PhpFileFromNamespaceCreatorService::class, [$config->generatedModelPath]);

		$builder->addDefinition($this->prefix('responsesClassesGeneratorService'))
			->setFactory(ResponsesClassesGeneratorService::class)
			->setArguments([$config->namespaceStart, new Statement(Url::class, [$config->baseUrl])]);

		// client
		$builder->addDefinition($this->prefix('clientCredentialsGrantService'))
			->setFactory(ClientCredentialsGrantService::class, [$config->clientCredentialsTempTokenFile]);

		$builder->addDefinition($this->prefix('apiClientService'))
			->setFactory(ApiClientService::class, [$config->baseUrl]);

		$builder->addDefinition($this->prefix('passwordGrantService'))
			->setFactory(PasswordGrantService::class);

		$builder->addDefinition($this->prefix('endpointResolverService'))
			->setFactory(EndpointResolverService::class);

		// user
		$builder->removeDefinition('security.authenticator');
		$builder->addDefinition('security.authenticator')
			->setFactory(Authenticator::class);

		$builder->addDefinition($this->prefix('userCacheService'))
			->setFactory(UserCacheService::class);

		$builder->addDefinition($this->prefix('userByTokenService'))
			->setFactory(UserByTokenService::class);
	}

}

<?php declare(strict_types=1);

namespace MirkoHuttner\ApiClient\Command;

use MirkoHuttner\ApiClient\Service\ResponsesClassesGeneratorService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class GenerateSchemaClassesCommand extends Command
{
	protected static $defaultName = 'api:generate-classes';

	private ResponsesClassesGeneratorService $responsesClassesGeneratorService;

	public function __construct(ResponsesClassesGeneratorService $responsesClassesGeneratorService)
	{
		parent::__construct();
		$this->responsesClassesGeneratorService = $responsesClassesGeneratorService;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$this->responsesClassesGeneratorService->generate();
		$output->writeln('Classes were generated.');
		return self::SUCCESS;
	}
}

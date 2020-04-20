<?php declare(strict_types=1);

namespace MirkoHuttner\ApiClient\Command;

use MirkoHuttner\ApiClient\Service\ResponsesClassesGeneratorService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class GenerateSchemaClassesCommand extends Command
{
	private ResponsesClassesGeneratorService $responsesClassesGeneratorService;

	public function __construct(ResponsesClassesGeneratorService $responsesClassesGeneratorService)
	{
		parent::__construct();
		$this->responsesClassesGeneratorService = $responsesClassesGeneratorService;
	}

	protected function configure(): void
	{
		$this->setName('api:generate-classes');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$this->responsesClassesGeneratorService->generate();
		$output->write('Classes were generated.');
		return 0;
	}
}

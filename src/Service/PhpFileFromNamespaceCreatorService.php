<?php declare(strict_types=1);

namespace MirkoHuttner\ApiClient\Service;

use Nette\PhpGenerator\ClassLike;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;
use Nette\Utils\FileSystem;
use Nette\Utils\Strings;

class PhpFileFromNamespaceCreatorService
{
	private string $basePath;

	/**
	 * @var array<string, true>
	 */
	private array $purgeDirs = [];


	public function __construct(string $basePath)
	{
		$this->basePath = $basePath;
	}


	public function create(PhpNamespace $namespace, string $ignorePath = ''): void
	{
		$path = $this->createDir($namespace, $ignorePath);
		foreach ($namespace->getClasses() as $class) {
			$class->addComment('This class is auto-generated. Do not modify it manually!');
			assert($class instanceof ClassType);
			$this->createPhpFile($class, $path);
		}
	}


	private function createDir(PhpNamespace $namespace, string $ignorePath = ''): string
	{
		$namespaceForPath = $namespace->getName();
		if ($ignorePath !== '') {
			$namespaceForPath = Strings::replace($namespaceForPath, sprintf('/^%s/', preg_quote($ignorePath, '/')));
		}

		return $this->purgeDir(...explode('\\', $namespaceForPath));
	}


	private function purgeDir(string ...$namespace): string
	{
		if (isset($namespace[0])) {
			$rootDir = $this->basePath . $namespace[0];
			if (!isset($this->purgeDirs[$rootDir])) {
				FileSystem::delete($rootDir);
				$this->purgeDirs[$rootDir] = true;
			}
		}

		$fullPath = $this->basePath . implode(DIRECTORY_SEPARATOR, $namespace);
		FileSystem::createDir($fullPath, 0755);

		return $fullPath;
	}


	private function createPhpFile(ClassType $class, string $path): void
	{
		$fullPath = $path . DIRECTORY_SEPARATOR . $class->getName() . '.php';
		if (file_exists($fullPath)) {
			unlink($fullPath);
		}

		$file = fopen($fullPath, 'w');
		if ($file === false) {
			throw new \RuntimeException('Cannot open file: ' . $fullPath);
		}
		fwrite($file, '<?php declare(strict_types=1);' . "\n\n");
		fwrite($file, (string) $class->getNamespace());
		fclose($file);
	}

}

<?php declare(strict_types=1);

namespace MirkoHuttner\ApiClient\Service;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;
use Nette\Utils\Strings;

class PhpFileFromNamespaceCreatorService
{
	private string $basePath;

	public function __construct(string $basePath)
	{
		$this->basePath = $basePath;
	}

	public function create(PhpNamespace $namespace, string $ignorePath = ''): void
	{
		$path = $this->createDir($namespace, $ignorePath);
		foreach ($namespace->getClasses() as $class) {
			$class->addComment('This class is auto-generated. Do not modify it manually!');
			$this->createPhpFile($class, $path);
		}
	}

	private function createDir(PhpNamespace $namespace, string $ignorePath = ''): string
	{
		$namespaceForPath = $namespace->getName();
		if ($ignorePath !== '') {
			$namespaceForPath = Strings::replace($namespaceForPath, sprintf('/^%s/', preg_quote($ignorePath, '/')));
		}

		$fullPath = $this->basePath . str_replace('\\', '/', $namespaceForPath);
		if (!file_exists($fullPath)) {
			mkdir($fullPath, 0777, true);
		}
		return $fullPath;
	}

	private function createPhpFile(ClassType $class, string $path): void
	{
		$fullPath = $path . DIRECTORY_SEPARATOR . $class->getName() . '.php';
		if (file_exists($fullPath)) {
			unlink($fullPath);
		}

		$file = fopen($fullPath, 'w');
		if ($file) {
			fwrite($file, '<?php declare(strict_types=1);' . "\n\n");
			fwrite($file, (string) $class->getNamespace());
			fclose($file);
		} else {
			throw new \RuntimeException('Cannot open file: ' . $fullPath);
		}
	}
}

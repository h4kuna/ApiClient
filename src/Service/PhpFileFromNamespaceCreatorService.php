<?php declare(strict_types=1);

namespace MirkoHuttner\ApiClient\Service;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;

class PhpFileFromNamespaceCreatorService
{
	private string $basePath;

	public function __construct(string $basePath)
	{
		$this->basePath = $basePath;
	}

	public function create(PhpNamespace $namespace): void
	{
		$path = $this->createDir($namespace);
		foreach ($namespace->getClasses() as $class) {
			$class->addComment('This class is auto-generated. Do not modify it manually!');
			$this->createPhpFile($class, $path);
		}
	}

	private function createDir(PhpNamespace $namespace): string
	{
		$fullPath = $this->basePath . str_replace('\\', '/', $namespace->getName());
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

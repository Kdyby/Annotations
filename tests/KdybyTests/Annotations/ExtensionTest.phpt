<?php

declare(strict_types = 1);

/**
 * Test: Kdyby\Doctrine\Extension.
 *
 * @testCase
 */

namespace KdybyTests\Annotations;

use Doctrine\Common\Annotations\Reader;
use Nette\Configurator;
use Nette\DI\Container;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';



class ExtensionTest extends \Tester\TestCase
{

	public function createContainer(string $configFile): Container
	{
		$config = new Configurator();
		$config->setTempDirectory(TEMP_DIR);
		$config->addParameters(['container' => ['class' => 'SystemContainer_' . md5($configFile)]]);
		$config->addConfig(__DIR__ . '/../nette-reset.neon');
		$config->addConfig(__DIR__ . '/config/' . $configFile . '.neon');

		$config->onCompile[] = static function ($config, \Nette\DI\Compiler $compiler): void {
			$compiler->addExtension('annotations', new \Kdyby\Annotations\DI\AnnotationsExtension());
		};

		return $config->createContainer();
	}

	public function testFunctionality(): void
	{
		$container = $this->createContainer('ignored');
		/** @var \Doctrine\Common\Annotations\Reader $reader */
		$reader = $container->getByType(Reader::class);
		Assert::true($reader instanceof Reader);

		require_once __DIR__ . '/data/Dj.php';
		require_once __DIR__ . '/data/HandsInTheAir.php';

		$annotations = $reader->getPropertyAnnotations(new \ReflectionProperty(\KdybyTests\Annotations\Data\Dj::class, 'music'));
		Assert::equal([
			new \KdybyTests\Annotations\Data\HandsInTheAir([]),
		], $annotations);
	}

}

(new ExtensionTest())->run();

<?php

/**
 * Test: Kdyby\Doctrine\Extension.
 *
 * @testCase
 */

namespace KdybyTests\Annotations;

use Doctrine\Common\Annotations\Reader;
use Kdyby\Annotations\DI\AnnotationsExtension;
use Nette\Configurator;
use ReflectionProperty;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';



class ExtensionTest extends \Tester\TestCase
{

	/**
	 * @param string $configFile
	 * @return \Nette\DI\Container
	 */
	public function createContainer($configFile)
	{
		$config = new Configurator();
		$config->setTempDirectory(TEMP_DIR);
		$config->addParameters(['container' => ['class' => 'SystemContainer_' . md5($configFile)]]);
		$config->addConfig(__DIR__ . '/../nette-reset.neon');
		$config->addConfig(__DIR__ . '/config/' . $configFile . '.neon');
		AnnotationsExtension::register($config);

		return $config->createContainer();
	}

	public function testFunctionality()
	{
		$container = $this->createContainer('ignored');
		/** @var \Doctrine\Common\Annotations\Reader $reader */
		$reader = $container->getByType(Reader::class);
		Assert::true($reader instanceof Reader);

		require_once __DIR__ . '/data/Dj.php';
		require_once __DIR__ . '/data/HandsInTheAir.php';

		$annotations = $reader->getPropertyAnnotations(new ReflectionProperty(Dj::class, 'music'));
		Assert::equal([
			new HandsInTheAir([]),
		], $annotations);
	}

}

(new ExtensionTest())->run();

<?php

/**
 * Test: Kdyby\Doctrine\Extension.
 *
 * @testCase
 */

namespace KdybyTests\Annotations;


require_once __DIR__ . '/../bootstrap.php';



class ExtensionTest extends \Tester\TestCase
{

	/**
	 * @param string $configFile
	 * @return \Nette\DI\Container
	 */
	public function createContainer($configFile)
	{
		$config = new \Nette\Configurator();
		$config->setTempDirectory(TEMP_DIR);
		$config->addParameters(['container' => ['class' => 'SystemContainer_' . md5($configFile)]]);
		$config->addConfig(__DIR__ . '/../nette-reset.neon');
		$config->addConfig(__DIR__ . '/config/' . $configFile . '.neon');
		\Kdyby\Annotations\DI\AnnotationsExtension::register($config);

		return $config->createContainer();
	}

	public function testFunctionality()
	{
		$container = $this->createContainer('ignored');
		/** @var \Doctrine\Common\Annotations\Reader $reader */
		$reader = $container->getByType(\Doctrine\Common\Annotations\Reader::class);
		\Tester\Assert::true($reader instanceof \Doctrine\Common\Annotations\Reader);

		require_once __DIR__ . '/data/Dj.php';
		require_once __DIR__ . '/data/HandsInTheAir.php';

		$annotations = $reader->getPropertyAnnotations(new \ReflectionProperty(Dj::class, 'music'));
		\Tester\Assert::equal([
			new HandsInTheAir([]),
		], $annotations);
	}

}

(new ExtensionTest())->run();

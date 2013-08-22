<?php

/**
 * Test: Kdyby\Doctrine\Extension.
 *
 * @testCase Kdyby\Doctrine\ExtensionTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Kdyby\Doctrine
 */

namespace KdybyTests\Annotations;

use Doctrine;
use Kdyby;
use Nette;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class ExtensionTest extends Tester\TestCase
{

	/**
	 * @param string $configFile
	 * @return \SystemContainer|Nette\DI\Container
	 */
	public function createContainer($configFile)
	{
		$config = new Nette\Configurator();
		$config->setTempDirectory(TEMP_DIR);
		$config->addParameters(array('container' => array('class' => 'SystemContainer_' . md5($configFile))));
		$config->addConfig(__DIR__ . '/../nette-reset.neon', $config::NONE);
		$config->addConfig(__DIR__ . '/config/' . $configFile . '.neon', $config::NONE);
		Kdyby\Annotations\DI\AnnotationsExtension::register($config);

		return $config->createContainer();
	}



	public function testFunctionality()
	{
		$container = $this->createContainer('ignored');
		$reader = $container->getByType('Doctrine\Common\Annotations\Reader');
		Assert::true($reader instanceof Doctrine\Common\Annotations\Reader);
		/** @var Doctrine\Common\Annotations\Reader $reader */

		require_once __DIR__ . '/files/ignored.php';
		$annotations = $reader->getPropertyAnnotations(new \ReflectionProperty('KdybyTests\Annotations\Dj', 'music'));
		Assert::equal(array(
			new \KdybyTests\Annotations\HandsInTheAir(array())
		), $annotations);
	}

}

\run(new ExtensionTest());

<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Annotations\DI;

use Nette;
use Nette\PhpGenerator as Code;
use Nette\Utils\Validators;



if (!class_exists('Nette\DI\CompilerExtension')) {
	class_alias('Nette\Config\CompilerExtension', 'Nette\DI\CompilerExtension');
	class_alias('Nette\Config\Compiler', 'Nette\DI\Compiler');
	class_alias('Nette\Config\Helpers', 'Nette\DI\Config\Helpers');
}

if (isset(Nette\Loaders\NetteLoader::getInstance()->renamed['Nette\Configurator']) || !class_exists('Nette\Configurator')) {
	unset(Nette\Loaders\NetteLoader::getInstance()->renamed['Nette\Configurator']); // fuck you
	class_alias('Nette\Config\Configurator', 'Nette\Configurator');
}

/**
 * @author Filip Procházka <filip@prochazka.su>
 * @author Jáchym Toušek <enumag@gmail.com>
 */
class AnnotationsExtension extends Nette\DI\CompilerExtension
{

	/** @var array */
	public $defaults = array(
		'ignored' => array(
			'persistent',
			'serializationVersion',
		),
		'debug' => '%debugMode%',
	);



	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig($this->defaults);

		$builder->addDefinition($this->prefix('reflectionReader'))
			->setClass('Doctrine\Common\Annotations\AnnotationReader')
			->setAutowired(FALSE);

		Validators::assertField($config, 'ignored', 'array');
		foreach ($config['ignored'] as $annotationName) {
			$builder->getDefinition($this->prefix('reflectionReader'))
				->addSetup('addGlobalIgnoredName', array($annotationName));
		}

		$builder->addDefinition($this->prefix('reader'))
			->setClass('Doctrine\Common\Annotations\Reader')
			->setFactory('Doctrine\Common\Annotations\CachedReader', array(
				$this->prefix('@reflectionReader'),
				new Nette\DI\Statement('Kdyby\DoctrineCache\Cache', array(
					'@Nette\Caching\IStorage',
					'Doctrine.Annotations',
					$config['debug']
				)),
				$config['debug']
			))
			->setInject(FALSE);
	}



	public function afterCompile(Code\ClassType $class)
	{
		$init = $class->methods['initialize'];
		$init->addBody('Doctrine\Common\Annotations\AnnotationRegistry::registerLoader("class_exists");');
	}



	/**
	 * @param \Nette\Configurator $configurator
	 */
	public static function register(Nette\Configurator $configurator)
	{
		$configurator->onCompile[] = function ($config, Nette\DI\Compiler $compiler) {
			$compiler->addExtension('annotations', new AnnotationsExtension());
		};
	}

}

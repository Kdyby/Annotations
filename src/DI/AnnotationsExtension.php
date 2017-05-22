<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Annotations\DI;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Annotations\Reader;
use Kdyby\DoctrineCache\DI\Helpers;
use Nette;
use Nette\PhpGenerator as Code;
use Nette\Utils\Validators;



/**
 * @author Filip Procházka <filip@prochazka.su>
 * @author Jáchym Toušek <enumag@gmail.com>
 */
class AnnotationsExtension extends Nette\DI\CompilerExtension
{

	/** @var array */
	public $defaults = [
		'ignore' => [
			'persistent',
			'serializationVersion',
		],
		'cache' => 'default',
		'debug' => '%debugMode%',
	];



	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getModifiedConfig($this->defaults);

		$reflectionReader = $builder->addDefinition($this->prefix('reflectionReader'))
			->setClass(AnnotationReader::class)
			->setAutowired(FALSE);

		Validators::assertField($config, 'ignore', 'array');
		foreach ($config['ignore'] as $annotationName) {
			$reflectionReader->addSetup('addGlobalIgnoredName', [$annotationName]);
			AnnotationReader::addGlobalIgnoredName($annotationName);
		}

		$builder->addDefinition($this->prefix('reader'))
			->setClass(Reader::class)
			->setFactory(CachedReader::class, [
				$this->prefix('@reflectionReader'),
				Helpers::processCache($this, $config['cache'], 'annotations', $config['debug']),
				$config['debug']
			]);

		// for runtime
		AnnotationRegistry::registerLoader("class_exists");
	}



	/**
	 * @return array
	 */
	public function getModifiedConfig(array $defaults = [])
	{
		$config = $this->validateConfig($defaults);

		// ignoredAnnotations
		$globalConfig = $this->compiler->getConfig();
		if (!empty($globalConfig['doctrine']['ignoredAnnotations'])) {
			trigger_error("Section 'doctrine: ignoredAnnotations:' is deprecated, please use '$this->name: ignore:' ", E_USER_DEPRECATED);
			$config = Nette\DI\Config\Helpers::merge($config, ['ignore' => $globalConfig['doctrine']['ignoredAnnotations']]);
		}

		return Nette\DI\Helpers::expand($config, $this->compiler->getContainerBuilder()->parameters);
	}



	public function afterCompile(Code\ClassType $class)
	{
		$init = $class->getMethod('initialize');
		$originalInitialize = (string) $init->getBody();
		$init->setBody('?::registerLoader("class_exists");' . "\n", [new Code\PhpLiteral(AnnotationRegistry::class)]);
		$init->addBody($originalInitialize);
	}



	public static function register(Nette\Configurator $configurator)
	{
		$configurator->onCompile[] = function ($config, Nette\DI\Compiler $compiler) {
			$compiler->addExtension('annotations', new AnnotationsExtension());
		};
	}

}

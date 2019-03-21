<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Annotations\DI;

class AnnotationsExtension extends \Nette\DI\CompilerExtension
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
		$config = $this->getConfig($this->defaults);

		$reflectionReader = $builder->addDefinition($this->prefix('reflectionReader'))
			->setClass(\Doctrine\Common\Annotations\AnnotationReader::class)
			->setAutowired(FALSE);

		\Nette\Utils\Validators::assertField($config, 'ignore', 'array');
		foreach ($config['ignore'] as $annotationName) {
			$reflectionReader->addSetup('addGlobalIgnoredName', [$annotationName]);
			\Doctrine\Common\Annotations\AnnotationReader::addGlobalIgnoredName($annotationName);
		}

		$builder->addDefinition($this->prefix('reader'))
			->setClass(\Doctrine\Common\Annotations\Reader::class)
			->setFactory(\Doctrine\Common\Annotations\CachedReader::class, [
				$this->prefix('@reflectionReader'),
				\Kdyby\DoctrineCache\DI\Helpers::processCache($this, $config['cache'], 'annotations', $config['debug']),
				$config['debug'],
			]);

		// for runtime
		\Doctrine\Common\Annotations\AnnotationRegistry::registerUniqueLoader('class_exists');
	}

	/**
	 * @return array
	 */
	public function getConfig(array $defaults = NULL, $expand = TRUE)
	{
		$config = parent::getConfig($defaults, $expand);

		// ignoredAnnotations
		$globalConfig = $this->compiler->getConfig();
		if (!empty($globalConfig['doctrine']['ignoredAnnotations'])) {
			trigger_error(sprintf("Section 'doctrine: ignoredAnnotations:' is deprecated, please use '%s: ignore:' ", $this->name), E_USER_DEPRECATED);
			$config = \Nette\DI\Config\Helpers::merge($config, ['ignore' => $globalConfig['doctrine']['ignoredAnnotations']]);
		}

		return $this->compiler->getContainerBuilder()->expand($config);
	}

	public function afterCompile(\Nette\PhpGenerator\ClassType $class)
	{
		$init = $class->getMethod('initialize');
		$originalInitialize = (string) $init->getBody();
		$init->setBody(
			'?::registerUniqueLoader("class_exists");' . "\n",
			[
				new \Nette\PhpGenerator\PhpLiteral(\Doctrine\Common\Annotations\AnnotationRegistry::class)
			]);
		$init->addBody($originalInitialize);
	}

	public static function register(\Nette\Configurator $configurator)
	{
		$configurator->onCompile[] = function ($config, \Nette\DI\Compiler $compiler) {
			$compiler->addExtension('annotations', new AnnotationsExtension());
		};
	}

}

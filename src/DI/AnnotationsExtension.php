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
use Nette\Configurator;
use Nette\DI\Compiler as DICompiler;
use Nette\DI\Config\Helpers as ConfigHelpers;
use Nette\PhpGenerator\ClassType as ClassTypeGenerator;
use Nette\PhpGenerator\PhpLiteral;
use Nette\Utils\Validators;

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
				$config['debug'],
			]);

		// for runtime
		AnnotationRegistry::registerUniqueLoader('class_exists');
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
			$config = ConfigHelpers::merge($config, ['ignore' => $globalConfig['doctrine']['ignoredAnnotations']]);
		}

		return $this->compiler->getContainerBuilder()->expand($config);
	}

	public function afterCompile(ClassTypeGenerator $class)
	{
		$init = $class->getMethod('initialize');
		$originalInitialize = (string) $init->getBody();
		$init->setBody('?::registerUniqueLoader("class_exists");' . "\n", [new PhpLiteral(AnnotationRegistry::class)]);
		$init->addBody($originalInitialize);
	}

	public static function register(Configurator $configurator)
	{
		$configurator->onCompile[] = function ($config, DICompiler $compiler) {
			$compiler->addExtension('annotations', new AnnotationsExtension());
		};
	}

}

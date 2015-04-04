Documentation
=============

This extension is here to provide integration of [Doctrine Annotations](http://docs.doctrine-project.org/projects/doctrine-common/en/latest/reference/annotations.html) into Nette Framework.


Installation
------------

The best way to install Kdyby/Annotations is using  [Composer](http://getcomposer.org/):

```sh
$ composer require kdyby/annotations
```

and enable the extension using your neon config

```yml
extensions:
	annotations: Kdyby\Annotations\DI\AnnotationsExtension
```


Configuration
---------------------

This extension creates new configuration section `annotations`. You can configure manually which annotations should be ignored by Doctrine Annotations. You can also manually enable or disable debug mode for annotations. By default it's detected via the %debugMode% parameter from Nette DI Container which is what you want in most cases.

```yml
annotations:
	ignore:
		- myannotation
	debug: yes
```

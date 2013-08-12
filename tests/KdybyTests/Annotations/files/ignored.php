<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace KdybyTests\Annotations;

use Doctrine\Common\Annotations\Annotation;
use Nette;



class Dj extends Nette\Object
{

	/**
	 * @dropTheBass
	 * @HandsInTheAir
	 */
	public $music;

}



/**
 * @Annotation
 * @Target("PROPERTY")
 */
class HandsInTheAir extends Annotation
{

}

<?php
namespace Lcobucci\EasySoap\Annotations;

use Mindplay\Annotation\Core\Annotation;

/**
 * Overrides the property type on @var
 *
 * @usage('property'=>true, 'inherited'=>true)
 */
class WebServicePropertyAnnotation extends Annotation
{
	/**
	 * @var string
	 */
	public $type;
}
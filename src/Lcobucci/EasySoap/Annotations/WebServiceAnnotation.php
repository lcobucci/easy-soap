<?php
namespace Lcobucci\EasySoap\Annotations;

use Mindplay\Annotation\Core\Annotation;

/**
 * Defines that the class is a webservice
 *
 * @usage('class'=>true, 'inherited'=>true)
 */
class WebServiceAnnotation extends Annotation
{
	/**
	 * Service name
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Service namespace
	 *
	 * @var string
	 */
	public $namespace;

	/**
	 * @var string
	 */
	public $endpoint;
}
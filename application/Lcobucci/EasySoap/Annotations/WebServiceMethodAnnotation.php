<?php
namespace Lcobucci\EasySoap\Annotations;

use Mindplay\Annotation\Core\Annotation;

/**
 * Defines that the method will be exposed
 *
 * @usage('method'=>true, 'inherited'=>true)
 */
class WebServiceMethodAnnotation extends Annotation
{
	/**
	 * @var array
	 */
	public $params;

	/**
	 * @var string
	 */
	public $return;

	/**
	 * @var string
	 */
	public $responseParamName;

	/**
	 * @var string
	 */
	public $description;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->params = array();
	}
}
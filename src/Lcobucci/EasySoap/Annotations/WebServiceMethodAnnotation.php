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

	/**
	 * @return string
	 */
	public function __toString()
	{
		$doc = '@WebServiceMethod';
		$options = array();

		$this->addDescription($options);
		$this->addParams($options);
		$this->addParamName($options);
		$this->addReturn($options);

		if (isset($options[0])) {
			$doc .= '(' . implode(', ', $options) . ')';
		}

		return $doc;
	}

	/**
	 * @param array $options
	 */
	protected function addDescription(array &$options)
	{
		if (!is_null($this->description)) {
			$options[] = "'description' => '" . $this->description . "'";
		}
	}

	/**
	 * @param array $options
	 */
	protected function addParamName(array &$options)
	{
		if (!is_null($this->responseParamName)) {
			$options[] = "'responseParamName' => '" . $this->responseParamName . "'";
		}
	}

	/**
	 * @param array $options
	 */
	protected function addReturn(array &$options)
	{
		if (!is_null($this->return)) {
			$options[] = "'return' => '" . $this->return . "'";
		}
	}

	/**
	 * @param array $options
	 */
	protected function addParams(array &$options)
	{
		if (isset($this->params[0])) {
			$params = array();

			foreach ($this->params as $param => $type) {
				$params[] = "'" . $param . "' => '" . $type . "'";
			}

			$options[] = "'params' => array(" . implode(', ', $params) . ")";
		}
	}
}
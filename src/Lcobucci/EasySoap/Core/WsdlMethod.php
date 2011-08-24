<?php
namespace Lcobucci\EasySoap\Core;

use Mindplay\Annotation\Standard\ParamAnnotation;
use Mindplay\Annotation\Standard\ReturnAnnotation;
use Mindplay\Annotation\Core\Annotations;
use Lcobucci\EasySoap\Annotations\WebServiceMethodAnnotation;
use \ReflectionMethod;

class WsdlMethod
{
	/**
	 * @var array
	 */
	protected $params;

	/**
	 * @var string
	 */
	protected $return;

	/**
	 * @var Lcobucci\EasySoap\Annotations\WebServiceMethodAnnotation
	 */
	protected $config;

	/**
	 * @var \ReflectionMethod
	 */
	private $method;

	/**
	 * @param \ReflectionMethod $method
	 */
	public function __construct(ReflectionMethod $method)
	{
		$this->method = $method;
		$this->params = array();

		$this->parseAnnotations();
		$this->overrideMethodParams();
	}

	/**
	 * Parse the method annotations
	 */
	protected function parseAnnotations()
	{
		foreach (Annotations::ofMethod($this->method) as $annotation) {
			if ($annotation instanceof WebServiceMethodAnnotation) {
				$this->config = $annotation;
			} elseif ($annotation instanceof ReturnAnnotation) {
				$this->return = $annotation->type;
			} elseif ($annotation instanceof ParamAnnotation) {
				$this->params[$annotation->name] = $annotation->type;
			}
		}
	}

	/**
	 * @throws Lcobucci\EasySoap\Core\WsdlException
	 */
	protected function overrideMethodParams()
	{
		foreach ($this->config->params as $name => $type) {
			$this->params[$name] = $type;
		}

		if (count($this->params) != $this->method->getNumberOfParameters()) {
			throw new WsdlException('The number of params in annotations must be the same of the method signature');
		}
	}

	/**
	 * @return array
	 */
	public function getParams()
	{
		return $this->params;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->method->getName();
	}

	/**
	 * @return string
	 */
	public function getDescription()
	{
		return $this->config->description ?: $this->getDocBlockDescription();
	}

	/**
	 * @return string
	 */
	protected function getDocBlockDescription()
	{
		$docBlock = $this->method->getDocComment();
		$matches = array();
		$description = null;

		if (preg_match(':/\*\*\s*\r?\n\s*\*\s(.*?)\r?\n\s*\*(\s@|/):s', $docBlock, $matches)) {
			$description = $matches[1];
			$description = preg_replace('/(^\s*\*\s)/m', '', $description);
			$description = preg_replace('/\r?\n\s*\*\s*(\r?\n)*/s', "\n", $description);
			$description = trim($description);
		}

		return $description;
	}

	/**
	 * @return string
	 */
	public function getReturnType()
	{
		$return = $this->config->return ?: $this->return;

		if (substr($return, -2, 2) == '[]') {
			throw new WsdlException('Methods should return an array through a transfer object');
		}

		return $return;
	}

	/**
	 * @return string
	 */
	public function getReturnName()
	{
		return $this->config->responseParamName ?: $this->method->name . 'Response';
	}

	/**
	 * @return boolean
	 */
	public function hasReturn()
	{
		return !is_null($this->getReturnType());
	}
}
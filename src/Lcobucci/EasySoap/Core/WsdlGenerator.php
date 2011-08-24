<?php
namespace Lcobucci\EasySoap\Core;

use Lcobucci\EasySoap\Annotations\WebServiceAnnotation;
use Mindplay\Annotation\Core\Annotations;
use Mindplay\Annotation\Core\AnnotationException;
use \ReflectionClass;
use \ReflectionMethod;

class WsdlGenerator
{
	/**
	 * @var Lcobucci\EasySoap\Core\WsdlDocument
	 */
	protected $wsdl;

	/**
	 * @var \ReflectionClass
	 */
	protected $class;

	/**
	 * @var string
	 */
	protected $endpoint;

	/**
	 * @var Lcobucci\EasySoap\Core\WsdlClassMap
	 */
	protected $classMap;

	private static function createAnnotationsAliases()
	{
	    try {
    	    Annotations::addAlias('WebService', 'Lcobucci\EasySoap\Annotations\WebServiceAnnotation');
    	    Annotations::addAlias('WebServiceMethod', 'Lcobucci\EasySoap\Annotations\WebServiceMethodAnnotation');
    	    Annotations::addAlias('WebServiceProperty', 'Lcobucci\EasySoap\Annotations\WebServicePropertyAnnotation');
	    } catch (AnnotationException $e) {
	        // Ignored
	    }
	}

	/**
	 * @param string $className
	 * @param string $endpoint
	 */
	public function __construct($className, $endpoint = null)
	{
	    self::createAnnotationsAliases();

		$this->classMap = new WsdlClassMap();
		$this->class = new ReflectionClass($className);
		$this->endpoint = $endpoint;
	}

	/**
	 * @param Lcobucci\EasySoap\Annotations\WebServiceAnnotation $config
	 */
	protected function initDocument(WebServiceAnnotation $config)
	{
		if (!is_null($config->endpoint)) {
			$this->endpoint = $config->endpoint;
		}

		$this->wsdl = new WsdlDocument(
			$config->name ?: $this->class->getShortName(),
			$config->namespace ?: 'urn:' . $this->class->getShortName(),
			$this->endpoint,
			$this->classMap
		);
	}

	/**
	 * @return array
	 */
	public function getClassMap()
	{
		foreach ($this->getWebserviceMethods() as $method) {
			$this->classMap->attachTypesFromMethod($method);
		}

		return $this->classMap->getWsdlClassMap();
	}

	/**
	 * Gera o WSDL do webservice
	 */
	public function generate()
	{
		$config = $this->getWebServiceAnnotation();

		$this->initDocument($config);

		foreach ($this->getWebserviceMethods() as $method) {
			$this->classMap->attachTypesFromMethod($method);
			$this->wsdl->addMethod($method);
		}

		foreach ($this->classMap->getWsdlClassMap() as $alias => $type) {
			$this->wsdl->addComplexType($alias, $this->classMap->getDefinitionByType($type));
		}
	}

	/**
	 * @return Lcobucci\EasySoap\Core\WsdlMethod[]
	 */
	protected function getWebserviceMethods()
	{
		$methods = array();

		foreach ($this->class->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
			if ($this->willBeExposed($method)) {
				$methods[] = new WsdlMethod($method);
			}
		}

		if (!isset($methods[0])) {
			throw new WsdlException(
				'You must have at least one public method with '
				. 'the Lcobucci\EasySoap\Annotations\WebServiceMethod annotation'
			);
		}

		return $methods;
	}

	/**
	 * @param \ReflectionMethod $method
	 * @throws Lcobucci\EasySoap\Core\WsdlException
	 */
	protected function willBeExposed(ReflectionMethod $method)
	{
		$annotations = Annotations::ofMethod(
			$method,
			null,
			'Lcobucci\EasySoap\Annotations\WebServiceMethodAnnotation'
		);

		return isset($annotations[0]);
	}

	/**
	 * @return Lcobucci\EasySoap\Annotations\WebServiceAnnotation
	 */
	protected function getWebServiceAnnotation()
	{
		$config = Annotations::ofClass($this->class, 'Lcobucci\EasySoap\Annotations\WebServiceAnnotation');

		if (!isset($config[0])) {
			throw new WsdlException('Webservices must have the Lcobucci\EasySoap\Annotations\WebService annotation');
		}

		if (is_null($this->endpoint) && is_null($config[0]->endpoint)) {
			throw new WsdlException('You must define a endpoint');
		}

		return $config[0];
	}


	/**
	 * @return string
	 */
	public function render()
	{
		header('Content-type: text/xml');

		return $this->wsdl->render();
	}
}
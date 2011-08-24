<?php
namespace Lcobucci\EasySoap\Core;

use Mindplay\Annotation\Standard\ParamAnnotation;
use Mindplay\Annotation\Standard\ReturnAnnotation;
use Lcobucci\EasySoap\Annotations\WebServiceMethodAnnotation;
use Mindplay\Annotation\Core\Annotations;
use \DOMDocument;
use \DOMElement;
use \ReflectionMethod;
use \ReflectionClass;

class WsdlDocument
{
	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var string
	 */
	protected $namespace;

	/**
	 * @var string
	 */
	protected $endpoint;

	/**
	 * @var array
	 */
	protected $types;

	/**
	 * @var \DOMElement
	 */
	private $xml;

	/**
	 * @var \DOMDocument
	 */
	private $dom;

	/**
	 * @var Lcobucci\EasySoap\Core\WsdlClassMap
	 */
	private $classMap;

	/**
	 * @param string $name
	 * @param string $namespace
	 * @param string $endpoint
	 * @param WsdlClassMap $map
	 */
	public function __construct($name, $namespace, $endpoint, WsdlClassMap $map)
	{
		$this->name = $name;
		$this->namespace = $namespace;
		$this->endpoint = $endpoint;
		$this->classMap = $map;
		$this->types = array();

		$this->createWsdlSkeleton();
	}

	protected function createWsdlSkeleton()
	{
		$wsdl = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
				<wsdl:definitions name="' . $this->name . '" targetNamespace="' . $this->namespace . '"
					xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/"
					xmlns:wsdl="http://schemas.xmlsoap.org/wsdl/"
					xmlns:xsd="http://www.w3.org/2001/XMLSchema"
					xmlns:tns="' . $this->namespace . '">
					<wsdl:types>
						<xsd:schema targetNamespace="' . $this->namespace . '" />
					</wsdl:types>
					<wsdl:portType name="' . $this->name . '" />
					<wsdl:binding name="' . $this->name . 'SOAP" type="' . WsdlTypeConverter::$namespaceAlias . ':' . $this->name . '">
						<soap:binding style="rpc" transport="http://schemas.xmlsoap.org/soap/http" />
					</wsdl:binding>
					<wsdl:service name="' . $this->name . '">
						<wsdl:port name="' . $this->name . 'SOAP" binding="' . WsdlTypeConverter::$namespaceAlias . ':' . $this->name . 'SOAP">
							<soap:address location="' . $this->endpoint . '" />
						</wsdl:port>
					</wsdl:service>
				</wsdl:definitions>';

		$this->dom = new DOMDocument();
		$this->dom->loadXML($wsdl);

		$this->xml = $this->dom->documentElement;
	}

	/**
	 * @return \DOMElement
	 */
	protected function getSchemaTag()
	{
		return $this->dom->getElementsByTagNameNS('http://www.w3.org/2001/XMLSchema', 'schema')->item(0);
	}

	/**
	 * @return \DOMElement
	 */
	protected function getPortTypeTag()
	{
		return $this->dom->getElementsByTagNameNS('http://schemas.xmlsoap.org/wsdl/', 'portType')->item(0);
	}

	/**
	 * @return DOMElement
	 */
	protected function getBindingTag()
	{
		return $this->dom->getElementsByTagNameNS('http://schemas.xmlsoap.org/wsdl/', 'binding')->item(0);
	}

	/**
	 * @param Lcobucci\EasySoap\Core\WsdlMethod $method
	 */
	public function addMethod(WsdlMethod $method)
	{
		$this->addMethodMessages($method);
		$this->addMethodPort($method);
		$this->addMethodBinding($method);
	}

	/**
	 * @param Lcobucci\EasySoap\Core\WsdlMethod $method
	 */
	protected function addMethodMessages(WsdlMethod $method)
	{
		$messageIn = $this->dom->createElementNS('http://schemas.xmlsoap.org/wsdl/', 'message');
		$messageIn->setAttribute('name', $method->getName() . 'Request');

		foreach ($method->getParams() as $paramName => $paramType) {
			$arg = $this->dom->createElementNS('http://schemas.xmlsoap.org/wsdl/', 'part');
			$arg->setAttribute('name', $paramName);
			$arg->setAttribute('type', WsdlTypeConverter::toXsd($paramType, $this->classMap));

			$messageIn->appendChild($arg);
		}

		$this->xml->insertBefore($messageIn, $this->getPortTypeTag());

		if ($method->hasReturn()) {
			$messageOut = $this->dom->createElementNS('http://schemas.xmlsoap.org/wsdl/', 'message');
			$messageOut->setAttribute('name', $method->getName() . 'Response');

			$return = $this->dom->createElementNS('http://schemas.xmlsoap.org/wsdl/', 'part');

			$return->setAttribute('name', $method->getReturnName());
			$return->setAttribute('type', WsdlTypeConverter::toXsd($method->getReturnType(), $this->classMap));

			$messageOut->appendChild($return);

			$this->xml->insertBefore($messageOut, $this->getPortTypeTag());
		}
	}

	/**
	 * @param Lcobucci\EasySoap\Core\WsdlMethod $method
	 */
	protected function addMethodPort(WsdlMethod $method)
	{
		$port = $this->dom->createElementNS('http://schemas.xmlsoap.org/wsdl/', 'operation');

		$port->setAttribute('name', $method->getName());
		$port->appendChild($this->dom->createElementNS('http://schemas.xmlsoap.org/wsdl/', 'documentation', $method->getDescription()));

		$input = $this->dom->createElementNS('http://schemas.xmlsoap.org/wsdl/', 'input');
		$input->setAttribute('message', WsdlTypeConverter::$namespaceAlias . ':' . $method->getName() . 'Request');
		$port->appendChild($input);

		if ($method->hasReturn()) {
			$output = $this->dom->createElementNS('http://schemas.xmlsoap.org/wsdl/', 'output');
			$output->setAttribute('message', WsdlTypeConverter::$namespaceAlias . ':' . $method->getName() . 'Response');
			$port->appendChild($output);
		}

		$this->getPortTypeTag()->appendChild($port);
	}

	/**
	 * @param Lcobucci\EasySoap\Core\WsdlMethod $method
	 */
	protected function addMethodBinding(WsdlMethod $method)
	{
		$operation = $this->dom->createElementNS('http://schemas.xmlsoap.org/wsdl/', 'operation');
		$operation->setAttribute('name', $method->getName());

		$soapOper = $this->dom->createElementNS('http://schemas.xmlsoap.org/wsdl/soap/', 'operation');
		$soapOper->setAttribute('soapAction', $this->namespace . (strpos($this->namespace, 'urn:') !== false ? 'Action' : '/' . $method->getName()));
		$operation->appendChild($soapOper);

		$input = $this->dom->createElementNS('http://schemas.xmlsoap.org/wsdl/', 'input');
		$inputBody = $this->dom->createElementNS('http://schemas.xmlsoap.org/wsdl/soap/', 'body');
		$inputBody->setAttribute('namespace', $this->namespace);
		$inputBody->setAttribute('use', 'literal');
		$input->appendChild($inputBody);
		$operation->appendChild($input);

		if ($method->hasReturn()) {
			$output = $this->dom->createElementNS('http://schemas.xmlsoap.org/wsdl/', 'output');
			$outputBody = $this->dom->createElementNS('http://schemas.xmlsoap.org/wsdl/soap/', 'body');
			$outputBody->setAttribute('namespace', $this->namespace);
			$outputBody->setAttribute('use', 'literal');
			$output->appendChild($outputBody);
			$operation->appendChild($output);
		}

		$this->getBindingTag()->appendChild($operation);
	}

	/**
	 * @param string $typeName
	 * @param \ReflectionClass $class
	 */
	public function addComplexType($typeName, ReflectionClass $class)
	{
		$complexType = $this->dom->createElementNS('http://www.w3.org/2001/XMLSchema', 'complexType');
		$complexType->setAttribute('name', $typeName);

		$sequence = $this->dom->createElementNS('http://www.w3.org/2001/XMLSchema', 'sequence');

		foreach ($class->getProperties() as $prop) {
			$wsdlProperty = Annotations::ofProperty($prop, null, 'Lcobucci\EasySoap\Annotations\WebServicePropertyAnnotation');
			$var = Annotations::ofProperty($prop, null, 'Mindplay\Annotation\Standard\VarAnnotation');

			if (isset($wsdlProperty[0])) {
				$type = $wsdlProperty[0]->type;
			} elseif (isset($var[0])) {
				$type = $var[0]->type;
			}

			$element = $this->dom->createElementNS('http://www.w3.org/2001/XMLSchema', 'element');

			if (substr($type, -2, 2) == '[]') {
				$type = substr($type, 0, -2);
				$element->setAttribute('minOccurs', '0');
				$element->setAttribute('maxOccurs', 'unbounded');
			}

			$element->setAttribute('name', $prop->getName());
			$element->setAttribute('type', WsdlTypeConverter::toXsd($type, $this->classMap));

			$sequence->appendChild($element);
		}

		$complexType->appendChild($sequence);
		$this->getSchemaTag()->appendChild($complexType);
	}

	/**
	 * @return string
	 */
	public function render()
	{
		return $this->dom->saveXML();
	}
}
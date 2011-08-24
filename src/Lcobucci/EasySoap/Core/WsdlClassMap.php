<?php
namespace Lcobucci\EasySoap\Core;

use Mindplay\Annotation\Core\Annotations;
use \SplObjectStorage;
use \ReflectionClass;
use \ReflectionException;

class WsdlClassMap
{
	/**
	 * @var \SplObjectStorage
	 */
	private $map;

	/**
	 * @var array
	 */
	private $types;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->map = new SplObjectStorage();
		$this->types = array();
	}

	/**
	 * @param WsdlMethod $method
	 */
	public function attachTypesFromMethod(WsdlMethod $method)
	{
		foreach ($method->getParams() as $type) {
			$this->mapType($type);
		}

		if ($method->hasReturn()) {
			$this->mapType($method->getReturnType());
		}
	}

	protected function mapType($type)
	{
		try {
			$type = str_replace('[]', '', $type);

			if (is_null($this->getDefinitionByType($type)) && !WsdlTypeConverter::existsOnPhpTypes($type)) {
				$type = new ReflectionClass($type);
				$this->map->attach($type);

				foreach ($type->getProperties() as $prop) {
					$wsdlProperty = Annotations::ofProperty($prop, null, 'Lcobucci\EasySoap\Annotations\WebServicePropertyAnnotation');
					$var = Annotations::ofProperty($prop, null, 'Mindplay\Annotation\Standard\VarAnnotation');

					if (isset($wsdlProperty[0])) {
						$this->mapType($wsdlProperty[0]->type);
					} elseif (isset($var[0])) {
						$this->mapType($var[0]->type);
					} else {
						throw new WsdlException('Properties must have @var or @Lcobucci\EasySoap\Annotations\WebServiceProperty');
					}
				}
			}
		} catch (ReflectionException $e) {
			throw new WsdlException(
				'Problems occurred when processing the type ' . $type,
				null,
				$e
			);
		}
	}

	/**
	 * @return array
	 */
	public function getWsdlClassMap()
	{
		foreach ($this->map as $class) {
			$wsdlType = $class->getShortName();
			$className = $class->getName();

			if (!isset($this->types[$wsdlType])) {
				$this->types[$wsdlType] = $className;
			} elseif ($this->types[$wsdlType] != $className) {
				$increment = 1;

				while (isset($this->types[$wsdlType . $increment]) && $this->types[$wsdlType . $increment] != $className) {
					++$increment;
				}

				$this->types[$wsdlType . $increment] = $className;
			}
		}

		return $this->types;
	}

	/**
	 * @param string $type
	 * @return string
	 * @throws WsdlException
	 */
	public function getWsdlAlias($type)
	{
		if (substr($type, 0, 1) == '\\') {
			$type = substr($type, 1);
		}

		if ($index = array_search($type, $this->getWsdlClassMap())) {
			return $index;
		}

		throw new WsdlException('Type ' . $type . ' was not found on class map');
	}

	/**
	 * @param string $type
	 * @return ReflectionClass
	 */
	public function getDefinitionByType($type)
	{
		foreach ($this->map as $obj) {
			if ($obj->getName() == $type) {
				return $obj;
			}
		}
	}
}
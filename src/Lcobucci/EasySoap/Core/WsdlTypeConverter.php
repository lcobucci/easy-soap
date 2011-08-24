<?php
namespace Lcobucci\EasySoap\Core;

class WsdlTypeConverter
{
	public static $namespaceAlias = 'tns';

	/**
	 * Returns an XSD Type for the given type
	 *
	 * @param string $type
	 * @return string
	 */
	public static function toXsd($type, WsdlClassMap $classMap = null)
	{
		if (substr($type, 0, 1) == '\\') {
			$type = substr($type, 1);
		}

		switch (strtolower($type)) {
			case 'string':
			case 'str':
				return 'xsd:string';
			case 'int':
			case 'integer':
				return 'xsd:int';
			case 'date':
				return 'xsd:date';
			case 'datetime':
				return 'xsd:dateTime';
			case 'float':
			case 'double':
				return 'xsd:float';
			case 'number':
			case 'decimal':
				return 'xsd:decimal';
			case 'boolean':
			case 'bool':
				return 'xsd:boolean';
			case 'object':
				return 'xsd:struct';
			case 'mixed':
				return 'xsd:anyType';
			default:
				if (!is_null($classMap)) {
					$type = $classMap->getWsdlAlias($type);
				}

				return self::$namespaceAlias . ':' . $type;
		}
	}

	/**
	 * Returns if the type exists on PHP core
	 *
	 * @param string $type
	 * @return boolean
	 */
	public static function existsOnPhpTypes($type)
	{
		return strpos(self::toXsd($type), self::$namespaceAlias . ':') === false;
	}
}
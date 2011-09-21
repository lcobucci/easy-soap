<?php
namespace Lcobucci\EasySoap\Core;

use \XSLTProcessor;
use \Exception;
use \SimpleXMLElement;
use \ReflectionMethod;

class SoapServer
{
	/**
	 * @var array
	 */
	protected $options;

	/**
	 * @var boolean
	 */
	protected $persistSessions;

	/**
	 * @param array $options
	 * @param boolean $persistSessions
	 */
	public function __construct(array $options = array(), $persistSessions = false)
	{
		$this->options = $options;
		$this->persistSessions = $persistSessions;
	}

	/**
	 * @param string $className
	 * @param array $constructorArgs
	 */
	public function handle($className, array $constructorArgs = null)
	{
		if ($_SERVER['REQUEST_METHOD'] == 'GET') {
			if (isset($_GET['wsdl'])) {
				echo $this->showWsdl($className);
			} else {
				echo $this->createDocumentation();
			}
		} else {
			$this->handleSoapCalls($className, $constructorArgs);
		}
	}

	/**
	 * @param string $className
	 */
	protected function handleSoapCalls($className, array $constructorArgs = null)
	{
		$options = array('classmap' => $this->createClassMap($className));

		$server = $this->getSoapServer($this->getWsdlUrl(), array_merge($this->options, $options));
		$this->configureClass($server, $className, $constructorArgs);

		if ($this->persistSessions) {
			$server->setPersistence(SOAP_PERSISTENCE_SESSION);
		}

		try {
			$server->handle();
		} catch (Exception $e)	{
			$xmlstr =
			    '<?xml version="1.0" encoding="UTF-8"?>
		    	<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
				    <SOAP-ENV:Body>
					    <SOAP-ENV:Fault>
						    <faultcode>Server</faultcode>
						    <faultstring>[' . get_class($e) . '] ' . $e->getMessage() . '</faultstring>
					    </SOAP-ENV:Fault>
				    </SOAP-ENV:Body>
			    </SOAP-ENV:Envelope>';

		    echo $xmlstr;
		}
	}

	/**
	 * @param \SoapServer $server
	 * @param string $className
	 * @param array $constructorArgs
	 */
	protected function configureClass(\SoapServer $server, $className, array $constructorArgs = null)
	{
		if (!is_null($constructorArgs)) {
			$method = new ReflectionMethod($server, 'setClass');
			$method->invokeArgs($server, array_merge(array($className), $constructorArgs));
		} else {
			$server->setClass($className);
		}
	}

	/**
	 * @param string $wsdl
	 * @param array $options
	 * @return \SoapServer
	 */
	protected function getSoapServer($wsdl, array $options = array())
	{
		return new \SoapServer($wsdl, $options);
	}

	/**
	 * @return string
	 */
	protected function getWsdlUrl()
	{
		return $this->getEndpoint() . '?wsdl';
	}

	/**
	 * @return string
	 */
	protected function getEndpoint()
	{
		$uri = parse_url($_SERVER['REQUEST_URI']);

		$url = 'http' . ($_SERVER['SERVER_PORT'] == '443' ? 's' : '') . '://';
		$url .= $this->getHost();
		$url .= $uri['path'];

		return $url;
	}

	/**
	 * @return string
	 */
	protected function getHost()
	{
		if (strpos($_SERVER['HTTP_HOST'], ':') !== false) {
			list($host, $port) = explode(':', $_SERVER['HTTP_HOST']);
		} else {
			$host = $_SERVER['HTTP_HOST'];
			$port = $_SERVER['SERVER_PORT'];
		}

		$this->changeRoute($host, $port);

		$host .= !in_array($port, array(80, 443)) ? ':' . $port : '';

		return $host;
	}

	/**
	 * @param string $host
	 * @param string $port
	 */
	protected function changeRoute(&$host, &$port)
	{
	}

	/**
	 * @param string $className
	 * @return array
	 */
	protected function createClassMap($className)
	{
		$generator = new WsdlGenerator($className, $this->getEndpoint());

		return $generator->getClassMap();
	}

	/**
	 * @return string
	 */
	protected function createDocumentation()
	{
		$xslt = new XSLTProcessor();
		$xslt->importStylesheet(new SimpleXMLElement(__DIR__ . '/../wsdl-viewer.xsl.php', null, true));

		return $xslt->transformToXml(new SimpleXMLElement($this->getWsdlUrl(), null, true));
	}

	/**
	 * @param string $className
	 */
	protected function showWsdl($className)
	{
		$generator = new WsdlGenerator($className, $this->getEndpoint());
		$generator->generate();

		return $generator->render();
	}
}
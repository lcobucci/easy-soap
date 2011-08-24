Lcobucci\EasySoap
=================

PHP library to create SOAP webservices

Installation
------------

Type the following commands in your terminal:

  * `pear channel-discover lcobucci.github.com`
  * `pear install lcobucci/EasySoap-beta`

Make sure that PEAR is in your include_path.

Important
---------

 * EasySoap and Annotation follows PSR-0 conventions, so make sure to use a compatible autoloader.
 * The WSDL will be generated on RPC/Literal binding style

Configuration
-------------

The first step you have to do is to configure the annotation's cache path of your project:

    (boot.php)
    <?php
    // Register your autoloader here
        
    use Mindplay\Annotation\Core\Annotations;
    
    Annotations::$config['cachePath'] = '/tmp/myProject'; // Make sure this path exists and it's writable
    
Basic Usage
-----------

To create a webservice you must create a PHP class (with namespace or not) that uses the **WebService** annotation, like this example:

    (Lcobucci/Tools/Service/Calculator.php)
    <?php
    namespace Lcobucci\Tools\Services;
    
    /**
     * @WebService
     */
    class Calculator
    {
    }
    
And set at least one method to be exposed with the **WebServiceMethod** annotation:

    (Lcobucci/Tools/Services/Calculator.php)
    <?php
    namespace Lcobucci\Tools\Services;
    
    /**
     * @WebService
     */
    class Calculator
    {
        /**
         * Returns the sum of given numbers
         *
         * @param number $num1
         * @param number $num2
         * @return number
         * @WebServiceMethod
         */
        public function sum($num1, $num2)
        {
            return $num1 + $num2;
        }
    }
    
After that you just have to instantiate Lcobucci\EasySoap\Core\SoapServer and call the handle() method:

    (index.php)
    <?php
    require 'boot.php'; // File with the autoloader and annotations cache path
    
    use Lcobucci\EasySoap\Core\SoapServer;
    
    $server = new SoapServer();
    $server->handle('Lcobucci\Tools\Services\Calculator'); // Always the full name of the class

And thats all, just access the index.php and you'll see a little documentation about your webservice.
If you want to see the WSDL document, access index.php?wsdl

Pretty easy, right?

Going deeper
-----------

What more can you do? Well look:

 * Complex types: just use the FULL class name on **@var**, **@param** or **@return** annotation
 * Arrays: You can use arrays just appending **[]** on the end of the type of **@var** annotation (arrays must always be inside of a complex type)
 * WebService annotation: you can set the **XML namespace**, the **service name** and the **service endpoint** on the WebService annotation - @Webservice('name'=>'SuperCalculator')
 * WebServiceMethod annotation: you can override @param and @return types on the WebServiceMethod annotation - @WebServiceMethod('return'=>'datetime')
 * WebServiceProperty annotation: you can override @var type using the optional WebServiceProperty annotation (on the complex types) - @WebServiceAnnotation('type'=>'date')
 
Roadmap
-------

 * Have complex type from different namespaces
 * Create tests
 * Use XSD complex type inheritance when it's needed

License Info
------------

Copyright (c) 2011, Luís Otávio Cobucci Oblonczyk.
All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are
permitted provided that the following conditions are met:

   1. Redistributions of source code must retain the above copyright notice, this list of
      conditions and the following disclaimer.

   2. Redistributions in binary form must reproduce the above copyright notice, this list
      of conditions and the following disclaimer in the documentation and/or other materials
      provided with the distribution.

THIS SOFTWARE IS PROVIDED BY COPYRIGHT HOLDER "AS IS" AND ANY EXPRESS OR IMPLIED
WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND
FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL COPYRIGHT HOLDER OR
CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF
ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

The views and conclusions contained in the software and documentation are those of the
authors and should not be interpreted as representing official policies, either expressed
or implied, of Luís Otávio Cobucci Oblonczyk.
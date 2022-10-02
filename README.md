> # UKRAINE NEEDS YOUR HELP NOW!
>
> On 24 February 2022, Russian [President Vladimir Putin ordered an invasion of Ukraine by Russian Armed Forces](https://www.bbc.com/news/world-europe-60504334).
>
> Your support is urgently needed.
>
> - Donate to the volunteers. Here is the volunteer fund helping the Ukrainian army to provide all the necessary equipment:
>  https://bank.gov.ua/en/news/all/natsionalniy-bank-vidkriv-spetsrahunok-dlya-zboru-koshtiv-na-potrebi-armiyi or https://savelife.in.ua/en/donate/
> - Triple-check social media sources. Russian disinformation is attempting to coverup and distort the reality in Ukraine.
> - Help Ukrainian refugees who are fleeing Russian attacks and shellings: https://www.globalcitizen.org/en/content/ways-to-help-ukraine-conflict/
> -  Put pressure on your political representatives to provide help to Ukraine.
> -  Believe in the Ukrainian people, they will not surrender, they don't have another Ukraine.
>
> THANK YOU!
----


xsd2php
=======

[![Build Status](https://travis-ci.org/goetas-webservices/xsd2php.svg?branch=master)](https://travis-ci.org/goetas-webservices/xsd2php)
[![Code Coverage](https://scrutinizer-ci.com/g/goetas-webservices/xsd2php/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/goetas-webservices/xsd2php/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/goetas-webservices/xsd2php/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/goetas-webservices/xsd2php/?branch=master)

Convert XSD into PHP classes.

With `goetas-webservices/xsd2php` you can convert any XSD/WSDL definition into PHP classes.

XSD2PHP can also generate [JMS Serializer](http://jmsyst.com/libs/serializer) compatible metadata that can be used to serialize/unserialize the object instances.

## Installation

There is one recommended way to install xsd2php via [Composer](https://getcomposer.org/):


* adding the dependency to your ``composer.json`` file:

```js
  "require": {
      ..
      "goetas-webservices/xsd2php-runtime":"^0.2.2",
      ..
  },
  "require-dev": {
      ..
      "goetas-webservices/xsd2php":"^0.3",
      ..
  },
```

## Usage

With this example we will convert [OTA XSD definitions](http://opentravel.org/Specifications/OnlineXmlSchema.aspx)
into PHP classes.

Suppose that you have all XSD files in `/home/my/ota`, first of all we need a configuration file 
(as example `config.yml`) that will keep all the namespace and directory mappings information.


```yml
# config.yml
# Linux Users: PHP Namespaces use back slash \ rather than a forward slash /
# So for destinations_php, the namespace would be TestNs\MyApp

xsd2php:
  namespaces:
    'http://www.example.org/test/': 'TestNs\MyApp'
  destinations_php: 
    'TestNs\MyApp': soap/src
#    'TestNs\MyApp': soap\src  #  on Windows

  destinations_jms:
    'TestNs\MyApp': soap/metadata
#    'TestNs\MyApp': soap\metadata  #  on Windows
 
#  Uncomment this section if you want to have also symfony/validator metadata to be generated from XSD    
#  destinations_validation:
#    'TestNs\MyApp': soap/validation
#    'TestNs\MyApp': soap\validation  #  on Windows
    
  aliases: # optional
    'http://www.example.org/test/':
      MyCustomXSDType:  'MyCustomMappedPHPType'
  naming_strategy: short # optional and default
  path_generator: psr4 # optional and default
#  known_locations: # optional
#    "http://www.example.org/test/somefile.xsd": somefile.xsd
#  known_namespace_locations: # optional
#    "urn:veloconnect:catalog-1.1": xsd/catalog-1.1.xsd
#  xml_namespaces:
#    'my-prefix': 'https://example.com/my-namespace'
# configs_jms:  #optional
#   xml_cdata: false # Disables CDATA
```

Here is an explanation on the meaning of each parameter:


* `xsd2php.namespaces` (required) defines the mapping between XML namespaces and PHP namespaces.
 (in the example we have the `http://www.example.org/test/` XML namespace mapped to `TestNs\MyApp`)


* `xsd2php.destinations_php` (required) specifies the directory where to save the PHP classes that belongs to 
 `TestNs\MyApp` PHP namespace. (in this example `TestNs\MyApp` classes will be saved into `soap/src` directory.


* `xsd2php.destinations_jms` (required) specifies the directory where to save JMS Serializer metadata files 
 that belongs to `TestNs\MyApp` PHP namespace. 
 (in this example `TestNs\MyApp` metadata will be saved into `soap/metadata` directory.


* `xsd2php.aliases` (optional) specifies some mappings that are handled by custom JMS serializer handlers.
 Allows to specify to do not generate metadata for some XML types, and assign them directly a PHP class.
 For that PHP class is necessary to create a custom JMS serialize/deserialize handler.


* `xsd2php.naming_strategy` (optional) specifies the naming strategy to use when converting XML names PHP classes.

* `xsd2php.path_generator` (optional) specifies the strategy to use for path generation and file saving

* `xsd2php.known_locations` (optional) override remote location with a local file.

* `xsd2php.known_namespace_locations` (optional) Specify schema location by namespace.
  This can be used to read schemas which import namespaces but do not specify schemaLocation attributes.

* `xsd2php.xml_namespaces` (optional) Specify XML namespace prefixes. If a prefix is defined for at namespace, then the
  prefix will *always* be used in the XML element name.

* `xsd2php.configs_jms.xml_cdata` (optional) Specify if CDATA should be used or not in serialization.

## Generate PHP classes and JMS metadata info

```sh
vendor/bin/xsd2php convert config.yml /home/my/ota/OTA_Air*.xsd

```

This command will generate PHP classes and JMS metadata files for all the XSD files matching `/home/my/ota/OTA_Air*.xsd`
and using the configuration available in `config.yml`


Serialize / Unserialize
-----------------------

XSD2PHP can also generate for you [JMS Serializer](http://jmsyst.com/libs/serializer) metadata 
that you can use to serialize/unserialize the generated PHP class instances.

The parameter `aliases` in the configuration file, will instruct XSD2PHP to not generate any metadata information or
PHP class for the `{http://www.example.org/test/}MyCustomXSDType` type.
All reference to this type are replaced with the `MyCustomMappedPHPType` name.

You have to provide a [custom serializer](http://jmsyst.com/libs/serializer/master/handlers#subscribing-handlers) 
for this type/alis.


Here is an example on how to configure JMS serializer to handle custom types

```php
<?php

use JMS\Serializer\SerializerBuilder;
use JMS\Serializer\Handler\HandlerRegistryInterface;

use GoetasWebservices\Xsd\XsdToPhpRuntime\Jms\Handler\BaseTypesHandler;
use GoetasWebservices\Xsd\XsdToPhpRuntime\Jms\Handler\XmlSchemaDateHandler;

$serializerBuilder = SerializerBuilder::create();
$serializerBuilder->addMetadataDir('metadata dir', 'TestNs');
$serializerBuilder->configureHandlers(function (HandlerRegistryInterface $handler) use ($serializerBuilder) {
    $serializerBuilder->addDefaultHandlers();
    $handler->registerSubscribingHandler(new BaseTypesHandler()); // XMLSchema List handling
    $handler->registerSubscribingHandler(new XmlSchemaDateHandler()); // XMLSchema date handling

    // $handler->registerSubscribingHandler(new YourhandlerHere());
});

$serializer = $serializerBuilder->build();

// deserialize the XML into Demo\MyObject object
$object = $serializer->deserialize('<some xml/>', 'TestNs\MyObject', 'xml');

// some code ....

// serialize the Demo\MyObject back into XML
$newXml = $serializer->serialize($object, 'xml');

```

To disable the CDATA, configure JMS as so:

```yaml
xsd2php:
  configs_jms:
    xml_cdata: false
```

Validation
----------

```php
<?php

use Symfony\Component\Validator\Validation;

// get the validator
$builder = Validation::createValidatorBuilder();
foreach (glob('soap/validation/*.yml') as $file) {
    $builder->addYamlMapping($file);
}
$validator =  $builder->getValidator();

// validate $object
$violations = $validator->validate($object, null, ['xsd_rules']);

```

Dealing with `xsd:anyType` or `xsd:anySimpleType`
-------------------------------------------------

If your XSD contains `xsd:anyType` or `xsd:anySimpleType` types you have to specify a handler for this.

When you generate the JMS metadata you have to specify a custom handler:


```yml
# config.yml

xsd2php:
  ...
  aliases: 
    'http://www.w3.org/2001/XMLSchema':
      anyType: 'MyCustomAnyTypeHandler'
      anySimpleType: 'MyCustomAnySimpleTypeHandler'
```

Now you have to create a custom serialization handler:

```php
use JMS\Serializer\XmlSerializationVisitor;
use JMS\Serializer\XmlDeserializationVisitor;

use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\VisitorInterface;
use JMS\Serializer\Context;

class MyHandler implements SubscribingHandlerInterface
{
    public static function getSubscribingMethods()
    {
        return array(
            array(
                'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
                'format' => 'xml',
                'type' => 'MyCustomAnyTypeHandler',
                'method' => 'deserializeAnyType'
            ),
            array(
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'xml',
                'type' => 'MyCustomAnyTypeHandler',
                'method' => 'serializeAnyType'
            )
        );
    }

    public function serializeAnyType(XmlSerializationVisitor $visitor, $data, array $type, Context $context)
    {
        // serialize your object here
    }

    public function deserializeAnyType(XmlDeserializationVisitor $visitor, $data, array $type)
    {
        // deserialize your object here
    }
}
```

Naming Strategy
---------------

There are two types of naming strategies: `short` and `long`. The default is `short`, this naming strategy can however generate naming conflicts.

The `long` naming strategy will suffix elements with `Element` and types with `Type`.

* `MyNamespace\User` will become `MyNamespace\UserElement`
* `MyNamespace\UserType` will become `MyNamespace\UserTypeType`

An XSD for instance with a type named `User`, a type named `UserType`, a root element named `User` and `UserElement`, will only work when using the `long` naming strategy.

* If you don't have naming conflicts and you want to have short and descriptive class names, use the `short` option.
* If you have naming conflicts use the `long` option.
* If you want to be safe, use the `long` option.

## Note 

The code in this project is provided under the 
[MIT](https://opensource.org/licenses/MIT) license. 
For professional support 
contact [goetas@gmail.com](mailto:goetas@gmail.com) 
or visit [https://www.goetas.com](https://www.goetas.com)

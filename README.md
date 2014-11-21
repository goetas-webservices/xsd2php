xsd2php
=======

[![Build Status](https://travis-ci.org/goetas/xsd2php.svg?branch=master)](https://travis-ci.org/goetas/xsd2php)
[![Code Coverage](https://scrutinizer-ci.com/g/goetas/xsd2php/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/goetas/xsd2php/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/goetas/xsd2php/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/goetas/xsd2php/?branch=master)

Convert XSD into PHP classes.

With `goetas/xsd2php` you can convert any XSD/WSDL definition into PHP classes.

XSD2PHP can also generate [JMS Serializer](http://jmsyst.com/libs/serializer) compatible metadata that can be used to serialize/unserialize the object instances.

Installation
-----------

There are two recommended ways to install xsd2php via [Composer](https://getcomposer.org/):


* adding the dependency to your ``composer.json`` file:

```js
"require": {
    ..
    "goetas/xsd2php":"2.*@dev",
    "goetas/xsd-reader":"2.*@dev",
    "jms/serializer": "xsd2php-dev as 0.18.0",
    ..
    "repositories": [{
        "type": "vcs",
        "url": "https://github.com/goetas/serializer.git"
    }],    
}
```


This package requires a patched version of JMS Serializer.
In the last year the activity of JMS serializer was very low and some features 
required by this project was rejected or not yet reviewed ( [#301](https://github.com/schmittjoh/serializer/pull/301), [#222](https://github.com/schmittjoh/serializer/pull/222) )

Usage
-----

With this example we will convert [OTA XSD definitions](http://opentravel.org/Specifications/OnlineXmlSchema.aspx) into PHP classes.

Suppose that you have allo XSD files in `/home/my/ota`. 

Generate PHP classes 
--------------------

```sh
bin/xsd2php.php convert:php \
`/home/my/ota/OTA_HotelAvail*.xsd \

--ns-map='http://www.opentravel.org/OTA/2003/05;Mercurio/OTA/2007B/' \

--ns-dest='Mercurio/OTA/2007B/;src/Mercurio/OTA/V2007B' \

--alias-map='http://www.opentravel.org/OTA/2003/05;CustomOTADateTimeFormat;Vendor/Project/CustomDateClass'

```
What about namespaces? 
* `http://www.opentravel.org/OTA/2003/05` will be converted into `Mercurio/OTA/2007B` PHP namespace

Where place the files?
* `Mercurio/OTA/2007B` classes will be placed into `src/Mercurio/OTA/V2007B` directory


What about custom types? 
* `--alias-map='http://www.opentravel.org/OTA/2003/05;CustomOTADateTimeFormat;Vendor/Project/CustomDateClass'` 
will instcut XSD2PHP to do not generate any class for `CustomOTADateTimeFormat` type inside `http://www.opentravel.org/OTA/2003/05` namespace.
All reference to this type are replaced with the `Vendor/Project/CustomDateClass` class.

Serialize / Unserialize
-----------------------

XSD2PHP can also generate for you [JMS Serializer](http://jmsyst.com/libs/serializer) metadata that you can use to serialize/unserialize the generated PHP class instances.

```sh
bin/xsd2php.php  convert:jms-yaml \
`/home/my/ota/OTA_HotelAvail*.xsd \

--ns-map='http://www.opentravel.org/OTA/2003/05;Mercurio/OTA/2007B/'  \
--ns-dest='Mercurio/OTA/2007B/;src/Metadata/JMS;' \

--alias-map='http://www.opentravel.org/OTA/2003/05;CustomOTADateTimeFormat;Vendor/Project/CustomDateClass'

```

What about namespaces? 
* `http://www.opentravel.org/OTA/2003/05` will be converted into `Mercurio/OTA/2007B` PHP namespace

Where place the files?
* `http://www.opentravel.org/OTA/2003/05` will be placed into `src/Metadata/JMS` directory

What about custom types? 
* `--alias-map='http://www.opentravel.org/OTA/2003/05;CustomOTADateTimeFormat;Vendor/Project/CustomDateClass'` 
will instcut XSD2PHP to do not generate any metadata information for `CustomOTADateTimeFormat` type inside `http://www.opentravel.org/OTA/2003/05` namespace.
All reference to this type are replaced with the `Vendor/Project/CustomDateClass` class. You have to provide a [custom serializer](http://jmsyst.com/libs/serializer/master/handlers#subscribing-handlers) for this type


```php
use JMS\Serializer\SerializerBuilder;
use JMS\Serializer\Handler\HandlerRegistryInterface;

use Goetas\Xsd\XsdToPhp\Jms\Handler\BaseTypesHandler;
use Goetas\Xsd\XsdToPhp\Jms\Handler\XmlSchemaDateHandler;

$serializerBuiler = SerializerBuilder::create();
$serializerBuiler->addMetadataDir('metadata dir', 'DemoNs');
$serializerBuiler->configureHandlers(function (HandlerRegistryInterface $h) use ($serializerBuiler) {
    $serializerBuiler->addDefaultHandlers();
    $h->registerSubscribingHandler(new BaseTypesHandler()); // XMLSchema List handling
    $h->registerSubscribingHandler(new XmlSchemaDateHandler()); // XMLSchema date handling
    
    // $h->registerSubscribingHandler(new YourhandlerHere());
});

$serializer = $serializerBuiler->build();

// unserialize the XML into Demo\MyObject object
$object = $serializer->deserialize('<some xml/>', 'DemoNs\MyObject', 'xml');

// some code ....

// serialize bck the Demo\MyObject into XML
$newXml = $serializer->serialize($object, 'xml');

```

Dealing with `xsd:anyType` or `xsd:anySimpleType`
-------------------------------------------------

If your XSD contains `xsd:anyType` or `xsd:anySimpleType` types you have to specify a handler for this.

When you generate the JMS metadata you have to specify a custom handler:

```sh
bin/xsd2php.php convert:jms-yaml \

 ... various params ... \

--alias-map='http://www.w3.org/2001/XMLSchema;anyType;MyCustomAnyTypeHandler' \
--alias-map='http://www.w3.org/2001/XMLSchema;anyType;MyCustomAnySimpleTypeHandler' \

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

Sometimes happen that you want not to have long class names but at the same time you want not too have namaing conflicts.
(example: `MyNamesapce\UserElement` instead of `MyNamesapce\User` or  `MyNamesapce\UserTypeType` instead of `MyNamesapce\UserType`).

When you have an XSD with a type named `User`, a type named `UserType` and a root element named `User` and `UserElement`,
creating the right PHP classes names will be problemeatic. To solve this you have to choose the right naming strategy.

* If you don't not have naming conflicts and you want to have short and descriptive class names, use `--naming-strategy=short` option when you generate classes and metadata
* If you have naming conflicts (or just you want to be stay safe) use `--naming-strategy=long` option when you generate classes and metadata.
This will generate PHP classes with the `Element` or `Type` suffix.
 


Note
----

I'm sorry for the terrible english fluency used inside the documentation, I'm trying to improve it.
Pull Requests are welcome.


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

--ns-map='http://www.opentravel.org/OTA/2003/05; Mercurio/OTA/2007B/' \

--ns-dest='Mercurio/OTA/2007B/; src/Mercurio/OTA/V2007B;' \

--alias-map='Vendor/Project/CustomDateClass; http://www.opentravel.org/OTA/2003/05#CustomOTADateTimeFormat'

```
What about namespaces? 
* `http://www.opentravel.org/OTA/2003/05` will be converted into `Mercurio/OTA/2007B` PHP namespace

Where place the files?
* `Mercurio/OTA/2007B` classes will be placed into `src/Mercurio/OTA/V2007B` directory


What about custom types? 
* `--alias-map='Vendor/Project/CustomDateClass;http://www.opentravel.org/OTA/2003/05#CustomOTADateTimeFormat'` 
will instcut XSD2PHP to do not generate any class for `CustomOTADateTimeFormat` type inside `http://www.opentravel.org/OTA/2003/05` namesapce.
All reference to this type are replaced with the `Vendor/Project/CustomDateClass` class.

Serilize / Unserialize
----------------------

XSD2PHP can also generate for you [JMS Serializer](http://jmsyst.com/libs/serializer) metadata that you can use to serialize/unserialize the generated PHP class instances.

```sh
bin/xsd2php.php  convert:jms-yaml \
`/home/my/ota/OTA_HotelAvail*.xsd \

--ns-map='http://www.opentravel.org/OTA/2003/05;Mercurio/OTA/2007B/'  \
--ns-dest='Mercurio/OTA/2007B/;src/Metadata/JMS;' \

--alias-map='http://www.opentravel.org/OTA/2003/05#CustomOTADateTimeFormat;Vendor/Project/CustomDateClass'

```

What about namespaces? 
* `http://www.opentravel.org/OTA/2003/05` will be converted into `Mercurio/OTA/2007B` PHP namespace

Where place the files?
* `http://www.opentravel.org/OTA/2003/05` will be placed into `src/Metadata/JMS` directory

What about custom types? 
* `--alias-map='Vendor/Project/CustomDateClass;http://www.opentravel.org/OTA/2003/05#CustomOTADateTimeFormat'` 
will instcut XSD2PHP to do not generate any metadata infmation for `CustomOTADateTimeFormat` type inside `http://www.opentravel.org/OTA/2003/05` namesapce.
All reference to this type are replaced with the `Vendor/Project/CustomDateClass` class. You have to provide a [custom serializer](http://jmsyst.com/libs/serializer/master/handlers#subscribing-handlers) for this type


```php
use JMS\Serializer\SerializerBuilder;
use JMS\Serializer\Handler\HandlerRegistryInterface;

use Goetas\Xsd\XsdToPhp\Jms\Handler\BaseTypesHandler;
use Goetas\Xsd\XsdToPhp\Jms\Handler\XmlSchemaDateHandler;

$serializerBuiler = SerializerBuilder::create();
$serializerBuiler->addMetadataDir('metadata dir', 'DemoNs');
$serializerBuiler->configureHandlers(function (HandlerRegistryInterface $h) use($serializerBuiler)
{
    $serializerBuiler->addDefaultHandlers();
    $h->registerSubscribingHandler(new BaseTypesHandler()); // XMLSchema List handling
    $h->registerSubscribingHandler(new XmlSchemaDateHandler()); // XMLSchema date handling
});

$serializer = $serializerBuiler->build();

// unserialize the XML into Demo\MyObject object
$object = $serializer->deserialize('<some xml/>', 'DemoNs\MyObject', 'xml');

// some code ....

// serialize bck the Demo\MyObject into XML
$newXml = $serializer->serialize($object, 'xml');

```


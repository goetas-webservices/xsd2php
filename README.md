xsd2php
=======

Convert XSD into PHP classes.

With `goetas/xsd2php` you can convert any XSD/WSDL definition into PHP classes.

Installation
-----------

There are two recommended ways to install xsd2php via [Composer](https://getcomposer.org/):

* using the ``composer require`` command:

```bash
composer require 'goetas/xsd2php:@dev'
```

* adding the dependency to your ``composer.json`` file:

```js
"require": {
    ..
    "goetas/xsd2php":"@dev",
    ..
}
```


Usage
-----

With this example we will convert [OTA XSD definitions](http://opentravel.org/Specifications/OnlineXmlSchema.aspx) into PHP classes.

Suppose that you have allo XSD files in `/home/my/ota`. 

Generate PHP classes 
--------------------

```sh
bin/xsd2php.php convert:php \
`/home/my/ota/OTA_HotelAvail*.xsd \

--ns-dest='src/Mercurio/OTA/V2007B;http://www.opentravel.org/OTA/2003/05' \

--ns-map='Mercurio/OTA/2007B/;http://www.opentravel.org/OTA/2003/05' \

--alias-map='Vendor/Project/CustomDateClass;http://www.opentravel.org/OTA/2003/05#CustomOTADateTimeFormat'

```


Where place the files?
* `http://www.opentravel.org/OTA/2003/05` will be placed into `src/Mercurio/OTA/V2007B` directory

What about namespaces? 
* `http://www.opentravel.org/OTA/2003/05` will be converted into `Mercurio/OTA/2007B` PHP namespace

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

--ns-dest='src/Metadata/JMS; http://www.opentravel.org/OTA/2003/05' \

--ns-map='Mercurio/OTA/2007B/;http://www.opentravel.org/OTA/2003/05'  \


```

Where place the files?
* `http://www.opentravel.org/OTA/2003/05` will be placed into `src/Metadata/JMS` directory

What about namespaces? 
* `http://www.opentravel.org/OTA/2003/05` will be converted into `Mercurio/OTA/2007B` PHP namespace

What about custom types? 
* `--alias-map='Vendor/Project/CustomDateClass;http://www.opentravel.org/OTA/2003/05#CustomOTADateTimeFormat'` 
will instcut XSD2PHP to do not generate any metadata infmation for `CustomOTADateTimeFormat` type inside `http://www.opentravel.org/OTA/2003/05` namesapce.
All reference to this type are replaced with the `Vendor/Project/CustomDateClass` class. You have to provide a [custom serializer](http://jmsyst.com/libs/serializer/master/handlers#subscribing-handlers) for this type




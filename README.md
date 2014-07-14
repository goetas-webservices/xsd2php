xsd2php
=======

Convert XSD into PHP classes.

With `goetas/xsd2php` you can convert any XSD/WSDL definition into PHP classes.

Installation with composer
--------------------------

`php composer.phar require goetas/xsd2php:master-dev`

Usage
-----

With this example we will convert [OTA XSD definitions](http://opentravel.org/Specifications/OnlineXmlSchema.aspx) into PHP classes.

Suppose that you have allo XSD files in `/home/my/ota`. 

The syntax for php executable is: 

```sh
app/console convert \
`/home/my/ota/OTA_HotelAvail*.xsd \

--ns-dest='src/Mercurio/OTA/V2007B;http://www.opentravel.org/OTA/2003/05' \
--ns-dest='src/Mercurio/OTA/V2007B/Common;' \

--ns-map='Mercurio/OTA/2007B;http://www.opentravel.org/OTA/2003/05'  \
--ns-map='Mercurio/OTA/V2007B/Common;'

--array-map-callback='ota'
```

Where place the files? (use `--ns-dest`)
* `http://www.opentravel.org/OTA/2003/05` will be placed into `src/Mercurio/OTA/V2007B` directory
* `` (no target namespace) will be placed into `src/Mercurio/OTA/V2007B/Common` directory

What about namespaces? (use `--ns-map`)
* `http://www.opentravel.org/OTA/2003/05` will be converted into `Mercurio/OTA/2007B` PHP namespace
* `` (no target namespace) will be converted into `Mercurio/OTA/V2007B/Common`PHP namespace

What about arrays? (use `--array-map-callback` or `--array-map`)
* `--array-map-callback='ota'` will use "ota" conventions to detect array types (built in types are `ota` and `microsoft`)




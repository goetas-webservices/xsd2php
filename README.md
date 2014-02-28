xsd2php
=======

Convert XSD into PHP classes.

With `goetas/xsd2php` you can convert any XSD/WSDL definition into PHP classes.

Installation with composer
--------------------------

`php composer.phar require goetas/xsd2php:master-dev`

Usage
-----

The syntax for php executable is: 

```sh
php bin/xsd2php.php convert  \
--ns-map="desired_php_namesapce:http://www.yournamespaceuri...." \
$SRC $DEST_DIR $TARGET_XSD_NS;
```

- `$SRC` is the location of XSD or WSDL file
- `$DEST_DIR` is the place where save generated entities
- `$TARGET_XSD_NS` the target xml ns. (one XSD can contain several types, in different namespaces... with this parameter we choose witch types we would convert to PHP)

The`--ns-map` is a multiple parameter. All types defined inside an XSD must have PHP equvalent class (except for XSD default types). 
The syntax is `--ns-map=PHPNS:XSDNS`

Example:
```sh
php bin/xsd2php.php convert  \
--ns-map='mycompany\\myproject:http://www.company.com/projectOne' \
--ns-map='mycompany\\myproject\\subproject\:http://www.company.com/projectTwo' \

'http://www.example.com/data.xsd' '/var/www/classes' 'http://www.company.com/projectTwo'
```

- This command will download `http://www.example.com/data.xsd`;
- Bind `http://www.company.com/projectOne` xsd data types to `mycompany\myproject` php namespace;
- Bind `http://www.company.com/projectTwo` xsd data types to `mycompany\myproject\subproject` php namespace;
- Save `mycompany\myproject\subproject` classes into `/var/www/classes` dir.

You have always to specify `--ns-map='mycompany\\myproject:http://www.company.com/projectOne' ` because `http://www.company.com/projectTwo` should use some types contained into `http://www.company.com/projectOne`


When some properties are arrays you can hint it:


Example:
```sh
php bin/xsd2php.php convert  \
--ns-map='mycompany\\myproject:http://www.company.com/projectOne' \
--array-map='ArrayOfReservations:http://www.company.com/projectTwo' \
--array-map='ArrayOf*:http://www.company.com/projectTwo' \

'http://www.example.com/data.xsd' '/var/www/classes' 'http://www.company.com/projectTwo'
```


[![Bitdeli Badge](https://d2weczhvl823v0.cloudfront.net/goetas/xsd2php/trend.png)](https://bitdeli.com/free "Bitdeli Badge")


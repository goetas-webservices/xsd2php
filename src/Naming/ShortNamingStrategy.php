<?php

namespace GoetasWebservices\Xsd\XsdToPhp\Naming;

use GoetasWebservices\XML\XSDReader\Schema\Item;
use GoetasWebservices\XML\XSDReader\Schema\Type\Type;

class ShortNamingStrategy extends AbstractNamingStrategy
{
    public function getTypeName(Type $type)
    {
        $name = $this->classify($type->getName());
        if ($name && substr($name, -4) !== 'Type') {
            $name .= 'Type';
        }

        return $name;
    }

    public function getAnonymousTypeName(Type $type, $parentName)
    {
        return $this->classify($parentName) . 'AType';
    }
}

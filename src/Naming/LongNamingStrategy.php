<?php

namespace GoetasWebservices\Xsd\XsdToPhp\Naming;

use Doctrine\Inflector\Inflector;
use GoetasWebservices\XML\XSDReader\Schema\Item;
use GoetasWebservices\XML\XSDReader\Schema\Type\Type;

class LongNamingStrategy extends AbstractNamingStrategy
{
    public function getTypeName(Type $type)
    {
        return $this->classify($type->getName()) . 'Type';
    }

    public function getAnonymousTypeName(Type $type, $parentName)
    {
        return $this->classify($parentName) . 'AnonymousPHPType';
    }
}

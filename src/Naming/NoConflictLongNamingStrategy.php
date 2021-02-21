<?php

namespace GoetasWebservices\Xsd\XsdToPhp\Naming;

use Doctrine\Inflector\Inflector;
use GoetasWebservices\XML\XSDReader\Schema\Item;
use GoetasWebservices\XML\XSDReader\Schema\Type\Type;

class NoConflictLongNamingStrategy extends AbstractNamingStrategy
{
    public function getTypeName(Type $type)
    {
        return $this->classify($type->getName()) . 'PHPType';
    }

    public function getAnonymousTypeName(Type $type, $parentName)
    {
        return $this->classify($parentName) . 'AnonymousPHPType';
    }
}

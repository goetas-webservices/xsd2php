<?php
namespace Goetas\Xsd\XsdToPhp\Naming;

use Doctrine\Common\Inflector\Inflector;
use GoetasWebservices\XML\XSDReader\Schema\Item;
use GoetasWebservices\XML\XSDReader\Schema\Type\Type;

class LongNamingStrategy implements NamingStrategy
{

    public function getTypeName(Type $type)
    {
        return $this->classify($type->getName()) . "Type";
    }

    public function getAnonymousTypeName(Type $type, $parentName)
    {
        return $this->classify($parentName) . "AnonymousType";
    }

    public function getItemName(Item $item)
    {
        return $this->classify($item->getName());
    }

    public function getPropertyName($item)
    {
        return Inflector::camelize(str_replace(".", " ", $item->getName()));
    }

    private function classify($name)
    {
        return Inflector::classify(str_replace(".", " ", $name));
    }
}
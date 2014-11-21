<?php
namespace Goetas\Xsd\XsdToPhp\Naming;

use Doctrine\Common\Inflector\Inflector;
use Goetas\XML\XSDReader\Schema\Type\Type;
use Goetas\XML\XSDReader\Schema\Item;

class LongNamingStrategy implements NamingStrategy
{

    public function getTypeName(Type $type)
    {
        return Inflector::classify($type->getName()) . "Type";
    }

    public function getAnonymousTypeName(Type $type, $parentName)
    {
        return Inflector::classify($parentName) . "AnonymousType";
    }

    public function getItemName(Item $item)
    {
        return Inflector::classify($item->getName());
    }
}
<?php
namespace Goetas\Xsd\XsdToPhp\Naming;

use Doctrine\Common\Inflector\Inflector;
use Goetas\XML\XSDReader\Schema\Type\Type;
use Goetas\XML\XSDReader\Schema\Item;

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

    private function classify($name)
    {
    	return Inflector::classify(str_replace(".", " ", $name));
    }
}
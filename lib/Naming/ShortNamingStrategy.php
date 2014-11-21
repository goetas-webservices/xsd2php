<?php
namespace Goetas\Xsd\XsdToPhp\Naming;

use Doctrine\Common\Inflector\Inflector;
use Goetas\XML\XSDReader\Schema\Type\Type;
use Goetas\XML\XSDReader\Schema\Item;

class ShortNamingStrategy implements NamingStrategy
{

    public function getTypeName(Type $type)
    {
        $name = Inflector::classify($type->getName());
        if ($name && substr($name, - 4) !== 'Type') {
            $name .= "Type";
        }
        return $name;
    }

    public function getAnonymousTypeName(Type $type, $parentName)
    {
        return Inflector::classify($parentName) . "AType";
    }

    public function getItemName(Item $item)
    {
        return Inflector::classify($item->getName());
    }
}
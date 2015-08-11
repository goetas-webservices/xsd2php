<?php
namespace Goetas\Xsd\XsdToPhp\Naming;

use Goetas\XML\XSDReader\Schema\Type\Type;
use Goetas\XML\XSDReader\Schema\Item;

interface NamingStrategy
{

    public function getTypeName(Type $type);

    public function getAnonymousTypeName(Type $type, $parentName);

    public function getItemName(Item $item);

    //@todo introduce common type for attributes and elements
    public function getPropertyName($item);
}
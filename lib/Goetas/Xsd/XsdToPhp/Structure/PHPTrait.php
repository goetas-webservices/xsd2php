<?php
namespace Goetas\Xsd\XsdToPhp\Structure;

class PHPTrait extends PHPType
{
    use PHPObject;

    public function __toString()
    {
        return "trait ".$this->getFullName();
    }

}
<?php
namespace Goetas\Xsd\XsdToPhp\Structure;

class PHPTrait extends PHPType
{
    use PHPObject;

    /**
     * @return string
     */
    public function __toString()
    {
        return "trait " . $this->getFullName();
    }
}

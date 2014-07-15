<?php
namespace Goetas\Xsd\XsdToPhp\Structure;

class PHPProperty extends PHPArg
{
    protected $visibility = 'protected';

    public function getVisibility()
    {
        return $this->visibility;
    }

    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;
        return $this;
    }

}
<?php
namespace GoetasWebservices\Xsd\XsdToPhp\Php\Structure;

class PHPProperty extends PHPArg
{

    /**
     * @var string
     */
    protected $visibility = 'protected';

    /**
     * @return string
     */
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * @param string $visibility
     * @return $this
     */
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;
        return $this;
    }
}

<?php
namespace GoetasWebservices\Xsd\XsdToPhp\Php\Structure;

class PHPProperty extends PHPArg
{
    /**
     * @var string
     */
    protected $visibility = 'protected';

    /** @var bool */
    protected $required = false;

    /** @var string[]  */
    protected $enum = [];

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

    /** @return bool */
    public function isRequired()
    {
        return $this->required;
    }

    /** @param bool $required */
    public function setRequired($required)
    {
        $this->required = $required;
    }

    /**
     * @param string[] $values
     *
     * @return void
     */
    public function setEnum(array $values)
    {
        $this->enum = $values;
    }

    /** @return bool */
    public function hasEnum()
    {
        return count($this->enum) > 0;
    }

    /** @return array */
    public function getEnum()
    {
        return $this->enum;
    }
}

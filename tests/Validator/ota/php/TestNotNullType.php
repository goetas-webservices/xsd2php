<?php

namespace ota;

/**
 * Class representing TestNotNull
 *
 * 
 * XSD Type: testNotNull
 */
class TestNotNullType
{

    /**
     * @property string
     * $value
     */
    private $value = null;

    /**
     * Gets as value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Sets a new value
     *
     * @param string $value
     * @return self
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

}


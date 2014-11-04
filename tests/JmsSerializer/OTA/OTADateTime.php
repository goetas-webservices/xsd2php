<?php
namespace Goetas\Xsd\XsdToPhp\Tests\JmsSerializer\OTA;

class OTADateTime extends \DateTime
{

    const TYPE_DATE = 1;

    const TYPE_TIME = 2;

    protected $type = 3;
    protected $original;

    public function __construct($time, $object, $type = 3)
    {
        parent::__construct($time, $object);
        $this->type = $type;
        $this->original = $time;
    }

    public function getType()
    {
        return $this->type;
    }
}
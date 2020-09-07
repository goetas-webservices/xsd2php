<?php

namespace GoetasWebservices\Xsd\XsdToPhp\Writer;

abstract class Writer
{
    abstract public function write(array $items);
}

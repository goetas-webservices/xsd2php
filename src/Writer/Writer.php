<?php
namespace GoetasWebservices\Xsd\XsdToPhp\Writer;

abstract class Writer
{
    public abstract function write(array $items);
}
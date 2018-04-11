<?php

namespace GoetasWebservices\Xsd\XsdToPhp\Tests;

use Doctrine\Common\Inflector\Inflector;
use GoetasWebservices\XML\XSDReader\Schema\Type\Type;
use GoetasWebservices\Xsd\XsdToPhp\Naming\ShortNamingStrategy;

/**
 * The OTA psr4 class paths can exceed windows max dir length
 */
class VeryShortNamingStrategy extends ShortNamingStrategy
{
    /**
     * Suffix with T instead of Type
     * @param Type $type
     * @return string
     */
    public function getTypeName(Type $type)
    {
        if ($name = $this->classify($type->getName())) {
            if (substr($name, -4) !== 'Type') {
                $name .= "T";
            } elseif (substr($name, -4) === 'Type') {
                $name = substr($name, 0, -3);
            }
        }
        return $name;
    }

    /**
     * Suffix with A instead of AType
     * @param Type $type
     * @param string $parentName
     * @return string
     */
    public function getAnonymousTypeName(Type $type, $parentName)
    {
        return $this->classify($parentName) . "A";
    }

    private function classify($name)
    {
        return Inflector::classify(str_replace(".", " ", $name));
    }
}

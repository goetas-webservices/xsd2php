<?php


namespace GoetasWebservices\Xsd\XsdToPhp\Tests;


use PHPUnit\Framework\Exception;
use PHPUnit\Framework\InvalidArgumentException;
use ReflectionObject;

class ReflectionUtils
{
    /**
     * Returns the value of an object's attribute.
     * This also works for attributes that are declared protected or private.
     *
     * @param object $object
     *
     * @throws Exception
     *
     * @codeCoverageIgnore
     */
    public static function getObjectAttribute($object, string $attributeName)
    {
        if (!\is_object($object)) {
            throw InvalidArgumentException::create(1, 'object');
        }

        $reflector = new ReflectionObject($object);

        do {
            try {
                $attribute = $reflector->getProperty($attributeName);

                if (!$attribute || $attribute->isPublic()) {
                    return $object->$attributeName;
                }

                $attribute->setAccessible(true);
                $value = $attribute->getValue($object);
                $attribute->setAccessible(false);

                return $value;
            } catch (\ReflectionException $e) {
            }
        } while ($reflector = $reflector->getParentClass());

        throw new Exception(
            \sprintf(
                'Attribute "%s" not found in object.',
                $attributeName
            )
        );
    }
}
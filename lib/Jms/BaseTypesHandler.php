<?php
namespace Goetas\Xsd\XsdToPhp\Jms;

use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\Context;
use JMS\Serializer\XmlSerializationVisitor;
use JMS\Serializer\XmlDeserializationVisitor;

class BaseTypesHandler implements SubscribingHandlerInterface
{

    public static function getSubscribingMethods()
    {
        return array(
            array(
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'xml',
                'type' => 'Goetas\Xsd\XsdToPhp\Jms\BaseTypeValue',
                'method' => 'serializeBaseToXML'
            ),
            array(
                'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
                'format' => 'xml',
                'type' => 'Goetas\Xsd\XsdToPhp\Jms\BaseTypeValue',
                'method' => 'unserializeBaseToXML'
            )
        );
    }

    public function serializeBaseToXML(XmlSerializationVisitor $visitor, $object, array $type, Context $context)
    {
        $newType = array(
            'name' => $type["params"][1],
            'params' => array_slice($type["params"], 2)
        );

        return $context->accept($object->value(), $newType);
    }

    public function unserializeBaseToXML(XmlDeserializationVisitor $visitor, $node, array $type, Context $context)
    {
        $newType = array(
            'name' => $type["params"][0],
            'params' => array_slice($type["params"], 2)
        );
        return $context->accept($node, $newType);
    }
}


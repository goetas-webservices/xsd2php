<?php
namespace Goetas\Xsd\XsdToPhp\Jms;

use RuntimeException;
use JMS\Serializer\Handler\DateHandler as JMSDateHandler;
use JMS\Serializer\XmlDeserializationVisitor;
use JMS\Serializer\Handler\DateHandler;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\VisitorInterface;
use JMS\Serializer\XmlSerializationVisitor;
use JMS\Serializer\Context;

class XmlSchemaDateHandler implements SubscribingHandlerInterface
{

    protected $defaultTimezone;

    protected $jmsDateHandler;

    public static function getSubscribingMethods()
    {
        return array(
            array(
                'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
                'format' => 'xml',
                'type' => 'DateTime',
                'method' => 'deserializeDateTimeFromXml'
            ),
            array(
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'xml',
                'type' => 'DateTime',
                'method' => 'serializeDateTimeFromXml'
            )
        );
    }

    public function __construct(DateHandler $jmsDateHandler, $defaultTimezone = 'UTC')
    {
        $this->defaultTimezone = new \DateTimeZone($defaultTimezone);
        $this->jmsDateHandler = $jmsDateHandler;
    }

    public function serializeDateTimeFromXml(XmlSerializationVisitor $visitor, \DateTime $date, array $type, Context $context)
    {
        if (isset($type['params'][0]) && $type['params'][0] == "XMLSchema#dateTime") {
            $v = $date->format(\DateTime::W3C);
            if (substr($v, - 5) == "00:00") {
                $v = substr($v, 0, - 6);
            }
            return $visitor->visitSimpleString($v, $type, $context);
        } else {
            return $this->jmsDateHandler->serializeDateTime($visitor, $date, $type, $context);
        }
    }

    public function deserializeDateTimeFromXml(XmlDeserializationVisitor $visitor, $data, array $type)
    {
        $attributes = $data->attributes('xsi', true);
        if (isset($attributes['nil'][0]) && (string) $attributes['nil'][0] === 'true') {
            return null;
        }

        if (isset($type['params'][0]) && $type['params'][0] == "XMLSchema#dateTime") {
            return $this->parseDateTime($data, $type);
        } else {
            return $this->jmsDateHandler->deserializeDateTimeFromXml($visitor, $data, $type);
        }
    }

    private function parseDateTime($data, array $type)
    {
        $timezone = isset($type['params'][1]) ? new \DateTimeZone($type['params'][1]) : $this->defaultTimezone;
        $datetime = new \DateTime((string) $data, $timezone);
        if (false === $datetime) {
            throw new RuntimeException(sprintf('Invalid datetime "%s", expected valid XML Schema dateTime string.', $data));
        }

        return $datetime;
    }
}


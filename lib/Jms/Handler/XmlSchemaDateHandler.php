<?php
namespace Goetas\Xsd\XsdToPhp\Jms\Handler;

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

    public static function getSubscribingMethods()
    {
        return array(
            array(
                'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
                'format' => 'xml',
                'type' => 'Goetas\Xsd\XsdToPhp\XMLSchema\DateTime',
                'method' => 'deserializeDateTime'
            ),
            array(
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'xml',
                'type' => 'Goetas\Xsd\XsdToPhp\XMLSchema\DateTime',
                'method' => 'serializeDateTime'
            ),
            array(
                'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
                'format' => 'xml',
                'type' => 'Goetas\Xsd\XsdToPhp\XMLSchema\Time',
                'method' => 'deserializeTime'
            ),
            array(
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'xml',
                'type' => 'Goetas\Xsd\XsdToPhp\XMLSchema\Time',
                'method' => 'serializeTime'
            )
        );
    }

    public function __construct($defaultTimezone = 'UTC')
    {
        $this->defaultTimezone = new \DateTimeZone($defaultTimezone);

    }

    public function serializeDateTime(XmlSerializationVisitor $visitor, \DateTime $date, array $type, Context $context)
    {

        $v = $date->format(\DateTime::W3C);
        if (substr($v, - 5) == "00:00") {
            $v = substr($v, 0, - 6);
        }
        return $visitor->visitSimpleString($v, $type, $context);
    }

    public function deserializeDateTime(XmlDeserializationVisitor $visitor, $data, array $type)
    {
        $attributes = $data->attributes('xsi', true);
        if (isset($attributes['nil'][0]) && (string) $attributes['nil'][0] === 'true') {
            return null;
        }

        return $this->parseDateTime($data, $type);

    }

    public function serializeTime(XmlSerializationVisitor $visitor, \DateTime $date, array $type, Context $context)
    {
        $v = $date->format('H:i:sP');
        if ($date->getTimezone()->getOffset($date)!==$this->defaultTimezone->getOffset($date)){
            $v.= $date->format('P');
        }
        return $visitor->visitSimpleString($v, $type, $context);
    }

    public function deserializeTime(XmlDeserializationVisitor $visitor, $data, array $type)
    {
        $attributes = $data->attributes('xsi', true);
        if (isset($attributes['nil'][0]) && (string) $attributes['nil'][0] === 'true') {
            return null;
        }

        $data = (string) $data;

        return new \DateTime( $data, $this->defaultTimezone );
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


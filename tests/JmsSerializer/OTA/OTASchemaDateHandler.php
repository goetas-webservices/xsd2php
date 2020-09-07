<?php

namespace GoetasWebservices\Xsd\XsdToPhp\Tests\JmsSerializer\OTA;

use JMS\Serializer\Context;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\XmlDeserializationVisitor;
use JMS\Serializer\XmlSerializationVisitor;
use RuntimeException;

class OTASchemaDateHandler implements SubscribingHandlerInterface
{
    protected $defaultTimezone;

    public static function getSubscribingMethods()
    {
        return [
            [
                'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
                'format' => 'xml',
                'type' => 'GoetasWebservices\Xsd\XsdToPhp\Tests\JmsSerializer\OTA\OTADateTime',
                'method' => 'deserializeDateTime',
            ],
            [
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'xml',
                'type' => 'GoetasWebservices\Xsd\XsdToPhp\Tests\JmsSerializer\OTA\OTADateTime',
                'method' => 'serializeDateTime',
            ],
        ];
    }

    public function __construct($defaultTimezone = 'UTC')
    {
        $this->defaultTimezone = new \DateTimeZone($defaultTimezone);
    }

    public function serializeDateTime(XmlSerializationVisitor $visitor, OTADateTime $date, array $type, Context $context)
    {
        $format = '';
        if ($date->getType() & OTADateTime::TYPE_DATE) {
            $format .= 'Y-m-d';
        }
        if ($date->getType() & OTADateTime::TYPE_DATE && $date->getType() & OTADateTime::TYPE_TIME) {
            $format .= '\T';
        }
        if ($date->getType() & OTADateTime::TYPE_TIME) {
            $format .= 'H:i:s';
        }
        $v = $date->format($format);

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

    private function parseDateTime($data, array $type)
    {
        $timezone = isset($type['params'][1]) ? new \DateTimeZone($type['params'][1]) : $this->defaultTimezone;

        $data = strval($data);

        $type = OTADateTime::TYPE_DATE | OTADateTime::TYPE_TIME;
        if (preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $data)) {
            $type = OTADateTime::TYPE_DATE;
        } elseif (preg_match('/^\d{2}\:\d{2}\:\d{2}/', $data)) {
            $type = OTADateTime::TYPE_TIME;
        }

        $datetime = new OTADateTime($data, $timezone, $type);
        if (false === $datetime) {
            throw new RuntimeException(sprintf('Invalid datetime "%s", expected valid XML Schema dateTime string.', $data));
        }

        return $datetime;
    }
}

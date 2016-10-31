<?php
namespace GoetasWebservices\Xsd\XsdToPhp\Tests\Php\PathGenerator;

use Composer\Autoload\ClassLoader;
use OTA\AirTravelerType\FlightSegmentRPHsAType;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ValidatorInterface
     */
    protected $validator;

    public function setUp()
    {
        $loader = new ClassLoader();
        $loader->addPsr4("OTA\\", "/home/goetas/projects/goetas-webservices-xsd2php/ota/php");
        $loader->register();

        $builder = Validation::createValidatorBuilder();

        foreach (glob('/home/goetas/projects/goetas-webservices-xsd2php/ota/validator/*.yml') as $file) {
            $builder->addYamlMapping($file);
        }

        $this->validator = $builder->getValidator();
    }

    public function testValidator()
    {

        $object = new FlightSegmentRPHsAType();
        /**
         * @var $violations ConstraintViolationListInterface
         */
        $violations = $this->validator->validate($object);
        $this->assertGreaterThan(0, count($violations));

        $object = new FlightSegmentRPHsAType();
        $object->addToFlightSegmentRPH("---");
        /**
         * @var $violations ConstraintViolationListInterface
         */
        $violations = $this->validator->validate($object);
        $this->assertGreaterThan(0, count($violations));

        $object = new FlightSegmentRPHsAType();
        $object->addToFlightSegmentRPH("12345678");
        /**
         * @var $violations ConstraintViolationListInterface
         */
        $violations = $this->validator->validate($object);
        $this->assertEquals(0, count($violations));
    }

}

<?php

namespace GoetasWebservices\Xsd\XsdToPhp\Tests\Php\PathGenerator;

use Composer\Autoload\ClassLoader;
use ota\TestNotNullType;
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
        $loader->addPsr4('ota\\', __DIR__ . '/ota/php');
        $loader->register();

        $builder = Validation::createValidatorBuilder();

        foreach (glob(__DIR__ . '/ota/validator/*.yml') as $file) {
            $builder->addYamlMapping($file);
        }

        $this->validator = $builder->getValidator();
    }

    public function testNotNullViolations()
    {
        $object = new TestNotNullType();
        $violations = $this->validator->validate($object);

        $this->assertEquals(1, count($violations));

        $object->setValue('My value');
        $violations = $this->validator->validate($object);

        $this->assertEquals(0, count($violations));
    }
}

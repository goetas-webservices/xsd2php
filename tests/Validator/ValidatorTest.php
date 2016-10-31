<?php
namespace GoetasWebservices\Xsd\XsdToPhp\Tests\Php\PathGenerator;

use Metadata\MetadataFactory;
use JMS\Serializer\Metadata\Driver\AnnotationDriver;
use Doctrine\Common\Annotations\AnnotationReader;
use GoetasWebservices\Xsd\XsdToPhp\Validator\Validator;

class ValidatorTest extends \PHPUnit_Framework_TestCase
{
    protected $validator;

    public function setUp()
    {
        $metadataFactory = new MetadataFactory(new AnnotationDriver(new AnnotationReader()));
        $this->validator = new Validator( $metadataFactory );
    }

    public function testValidator() {
        
        // do tests        
        
    }
    
}

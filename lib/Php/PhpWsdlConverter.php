<?php
namespace GoetasWebservices\Xsd\XsdToPhp\Php;

use Doctrine\Common\Inflector\Inflector;
use Exception;
use GoetasWebservices\Xsd\XsdToPhp\Php\Structure\PHPClass;
use GoetasWebservices\Xsd\XsdToPhp\Php\Structure\PHPProperty;
use GoetasWebservices\XML\SOAPReader\Soap\OperationMessage;
use GoetasWebservices\XML\SOAPReader\SoapReader;
use GoetasWebservices\XML\WSDLReader\DefinitionsReader;
use GoetasWebservices\XML\WSDLReader\Wsdl\Binding\Operation;
use GoetasWebservices\XML\WSDLReader\Wsdl\Definitions;
use GoetasWebservices\XML\WSDLReader\Wsdl\Service;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\AttributeItem;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\Group as AttributeGroup;
use GoetasWebservices\XML\XSDReader\Schema\Element\Element;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementDef;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementRef;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementSingle;
use GoetasWebservices\XML\XSDReader\Schema\Element\Group;
use GoetasWebservices\XML\XSDReader\Schema\Item;
use GoetasWebservices\XML\XSDReader\Schema\Schema;
use GoetasWebservices\XML\XSDReader\Schema\Type\BaseComplexType;
use GoetasWebservices\XML\XSDReader\Schema\Type\ComplexType;
use GoetasWebservices\XML\XSDReader\Schema\Type\SimpleType;
use GoetasWebservices\XML\XSDReader\Schema\Type\Type;
use Symfony\Component\EventDispatcher\EventDispatcher;

class PhpWsdlConverter
{
    private $classes = [];

    private $phpConverter;

    public function __construct(PhpConverter $phpConverter)
    {
        $this->phpConverter = $phpConverter;
    }

    private function convert(array $services)
    {
        $visited = array();
        $this->classes = array();
        foreach ($services as $service) {
            $this->visitService($service, $visited);
        }
        return $this->classes;
    }

    public function run(array $src)
    {
        $dispatcher = new EventDispatcher();
        $wsdlReader = new DefinitionsReader(null, $dispatcher);
        $soapReader = new SoapReader();
        $dispatcher->addSubscriber($soapReader);
        foreach ($src as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === "wsdl") {
                $wsdlReader->readFile($file);
            }
        }
        return $this->convert($soapReader->getSoapServices());
    }

    private function visitService(\GoetasWebservices\XML\SOAPReader\Soap\Service $service, array &$visited)
    {
        if (isset($visited[spl_object_hash($service)])) {
            return;
        }
        $visited[spl_object_hash($service)] = true;

        foreach ($service->getOperations() as $operation) {
            $this->visitOperation($operation, $service);
        }
    }

    private function visitOperation(\GoetasWebservices\XML\SOAPReader\Soap\Operation $operation, $service)
    {
        $this->visitMessage($operation->getInput(), 'input', $operation, $service);
        $this->visitMessage($operation->getOutput(), 'output', $operation, $service);
    }

    private function visitMessage(OperationMessage $message, $hint = '', \GoetasWebservices\XML\SOAPReader\Soap\Operation $operation, $service)
    {
        if (!isset($this->classes[spl_object_hash($message)])) {

            $this->classes[spl_object_hash($message)] = $bodyClass = new PHPClass();

            list ($name, $ns) = $this->findPHPName($message, Inflector::classify($hint));
            $bodyClass->setName(Inflector::classify($name));
            $bodyClass->setNamespace($ns . '\\Envelope\\Parts');

            $this->visitMessageParts($bodyClass, $message->getBody()->getParts());

            $this->classes[] = $envelopeClass = new PHPClass();
            $envelopeClass->setName(Inflector::classify($name));
            $envelopeClass->setNamespace($ns . '\\Envelope\\Messages');
            $property = new PHPProperty('body', $bodyClass);
            $envelopeClass->addProperty($property);

            $property = new PHPProperty('header');
            $envelopeClass->addProperty($property);
            if (count($message->getHeaders())) {

                $this->classes[] = $headerClass = new PHPClass();
                $headerClass->setName(Inflector::classify($name));
                $headerClass->setNamespace($ns . '\\Envelope\\Headers');
                $property->setType($headerClass);

                foreach ($message->getHeaders() as $k => $header) {
                    $this->visitMessageParts($headerClass, [$header->getPart()]);
                }
            }
        }
        return $this->classes[spl_object_hash($message)];
    }

    private function visitMessageParts(PHPClass $class, array $parts)
    {
        /**
         * @var $part \GoetasWebservices\XML\WSDLReader\Wsdl\Message\Part
         */
        foreach ($parts as $part) {
            $property = new PHPProperty();
            $property->setName(Inflector::camelize($part->getName()));

            if ($part->getElement()) {
                $property->setType($this->phpConverter->visitElementDef($part->getElement(), true));
            } else {
                $property->setType($this->phpConverter->visitType($part->getType()));
            }

            $class->addProperty($property);
        }
    }

    private function findPHPName(OperationMessage $message, $hint = '')
    {
        $name = $message->getMessage()->getOperation()->getName() . ucfirst($hint);
        $targetNs = $message->getMessage()->getDefinition()->getTargetNamespace();
        $namespaces = $this->phpConverter->getNamespaces();
        if (!isset($namespaces[$targetNs])) {
            throw new Exception(sprintf("Can't find a PHP namespace to '%s' namespace", $targetNs));
        }
        $ns = $namespaces[$targetNs];
        return [
            $name,
            $ns
        ];
    }
}

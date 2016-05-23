<?php
namespace GoetasWebservices\Xsd\XsdToPhp\Jms;

use Doctrine\Common\Inflector\Inflector;
use GoetasWebservices\XML\SOAPReader\Soap\OperationMessage;
use GoetasWebservices\XML\SOAPReader\SoapReader;
use GoetasWebservices\XML\WSDLReader\DefinitionsReader;
use Symfony\Component\EventDispatcher\EventDispatcher;

class YamlSoapConverter
{
    protected $converter;
    private $classes = [];

    public function __construct(YamlConverter $converter)
    {
        $this->converter = $converter;
    }

    private function convert(array $services)
    {
        $visited = array();
        $this->classes = array();
        foreach ($services as $service) {
            $this->visitService($service, $visited);
        }
        return $this->getTypes();
    }

    /**
     *
     * @return PHPClass[]
     */
    public function getTypes()
    {
        uasort($this->classes, function ($a, $b) {
            return strcmp(key($a), key($b));
        });

        $ret = array();

        foreach ($this->classes as $definition) {
            $classname = key($definition);
            if (strpos($classname, '\\') !== false) {
                $ret[$classname] = $definition;
            }
        }

        return $ret;
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
        return $this->convert($soapReader->getServices());
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
            $className = $this->findPHPName($message, Inflector::classify($hint), '\\Envelope\\Parts');
            $class = array();
            $data = array();
            $envelopeData["xml_namespaces"] = ['SOAP' => 'http://schemas.xmlsoap.org/soap/envelope/'];
            $class[$className] = &$data;

            if ($message->getMessage()->getDefinition()->getTargetNamespace()) {
                $data["xml_root_namespace"] = $message->getMessage()->getDefinition()->getTargetNamespace();
            }
            $this->visitMessageParts($data, $message->getBody()->getParts());

            $this->classes[spl_object_hash($message)] = &$class;

            $messageClassName = $this->findPHPName($message, Inflector::classify($hint), '\\Envelope\\Messages');
            $envelopeClass = array();
            $envelopeData = array();
            $envelopeClass[$messageClassName] = &$envelopeData;
            $envelopeData["xml_root_name"] = 'SOAP:Envelope';
            $envelopeData["xml_root_namespace"] = 'http://schemas.xmlsoap.org/soap/envelope/';
            $envelopeData["xml_namespaces"] = ['SOAP' => 'http://schemas.xmlsoap.org/soap/envelope/'];

            $property = [];
            $property["expose"] = true;
            $property["access_type"] = "public_method";
            $property["type"] = $className;
            $property["serialized_name"] = 'Body';
            $property["xml_element"]["namespace"] = 'http://schemas.xmlsoap.org/soap/envelope/';

            $property["accessor"]["getter"] = "getBody";
            $property["accessor"]["setter"] = "setBody";

            $envelopeData["properties"]['body'] = $property;
            $this->classes[] = &$envelopeClass;


            if (count($message->getHeaders())) {
                $headersClass = array();
                $headersData = array();

                $headersData["xml_namespaces"] = ['SOAP' => 'http://schemas.xmlsoap.org/soap/envelope/'];

                $className = $this->findPHPName($message, Inflector::classify($hint), '\\Envelope\\Headers');

                $headersClass[$className] = &$headersData;
                $this->classes[] = &$headersClass;

                $property = [];
                $property["expose"] = true;
                $property["access_type"] = "public_method";
                $property["type"] = $className;
                $property["serialized_name"] = 'Header';
                $property["xml_element"]["namespace"] = 'http://schemas.xmlsoap.org/soap/envelope/';

                $property["accessor"]["getter"] = "getHeader";
                $property["accessor"]["setter"] = "setHeader";

                $envelopeData["properties"]['header'] = $property;

                foreach ($message->getHeaders() as $k => $header) {
                    $this->visitMessageParts($headersData, [$header->getPart()]);
                }
            }

        }
        return $this->classes[spl_object_hash($message)];
    }

    private function visitMessageParts(&$data, array $parts)
    {
        /**
         * @var $part \GoetasWebservices\XML\WSDLReader\Wsdl\Message\Part
         */
        foreach ($parts as $part) {
            $property = [];
            $property["expose"] = true;
            $property["access_type"] = "public_method";


            $property["accessor"]["getter"] = "get" . Inflector::classify($part->getName());
            $property["accessor"]["setter"] = "set" . Inflector::classify($part->getName());


            if ($part->getElement()) {
                $property["xml_element"]["namespace"] = $part->getElement()->getSchema()->getTargetNamespace();
                $property["serialized_name"] = $part->getElement()->getName();
                $c = $this->converter->visitElementDef($part->getElement()->getSchema(), $part->getElement());
                $property["type"] = key($c);
            } else {
                $property["serialized_name"] = $part->getName();
                $property["xml_element"]["namespace"] = null;

                $c = $this->converter->visitType($part->getType());
                $property["type"] = key($c);
            }

            $data['properties'][Inflector::camelize($part->getName())] = $property;
        }
    }

    private function findPHPName(OperationMessage $message, $hint = '', $nsadd = '')
    {
        $name = Inflector::classify($message->getMessage()->getOperation()->getName()) . $hint;
        $targetNs = $message->getMessage()->getDefinition()->getTargetNamespace();
        $namespaces = $this->converter->getNamespaces();
        if (!isset($namespaces[$targetNs])) {
            throw new Exception(sprintf("Can't find a PHP namespace to '%s' namespace", $targetNs));
        }
        $ns = $namespaces[$targetNs];
        return $ns . $nsadd . "\\" . $name;
    }
}
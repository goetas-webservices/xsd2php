<?php

namespace Goetas\Xsd\XsdToPhp;

use Goetas\Xsd\XsdToPhp\Utils\UrlUtils;

use Goetas\Xsd\XsdToPhp\Generator\ClassGenerator;

use XSLTProcessor;
use DOMDocument;

abstract class Xsd2PhpBase
{
    protected $proc;
    protected $generator;
    public function __construct()
    {
        $this->proc = new XSLTProcessor();
        $this->proc->registerPHPFunctions();
        $this->generator = new ClassGenerator();
    }
    protected function getFullSchema($src)
    {
        $xsd = new DOMDocument();
        $el = $xsd->createElementNS("goetas:envelope", "env:env");
        $xsd->appendChild($el);

        $this->parseIncludes($el, $this->loadXsd($src));

        $this->uniqueTypes($xsd);

        return $xsd;
    }
    public function getNamespace($namespace)
    {
        return $this->generator->getNamespace($namespace);
    }
    public function addNamespace($namesapce, $phpNamespace)
    {
        $this->generator->addNamespace($namesapce, $phpNamespace);
    }
    public function addAlias($xsdNs, $xsdType,$xsdType)
    {
        $this->generator->addAlias($xsdNs, $xsdType, $xsdType);
    }
    public function addArrayType($xsdNs, $xsdType)
    {
        $this->generator->addArrayType($xsdNs, $xsdType);
    }
    public static function splitPart($node, $base, $find)
    {
        if (strpos($base,':')===false) {
            $name = $base;
        } else {
            list($prefix, $name)=explode(":", $base);
        }
        if ($find=='ns') {
            return $node[0]->lookupNamespaceUri($prefix?:null);
        } else {
            return $name;
        }
    }
    public function parseIncludes(\DOMElement $root, \DOMDocument $src)
    {

        $cloned = $root->ownerDocument->importNode($src->documentElement, true);

        $root->appendChild($cloned);

        $xp = new \DOMXPath($root->ownerDocument);
        $xp->registerNamespace("xsd", "http://www.w3.org/2001/XMLSchema");
        $xp->registerNamespace("wsdl", "http://schemas.xmlsoap.org/wsdl/");

        $res = $xp->query("
                xsd:schema/xsd:import[@schemaLocation]|
                xsd:schema/xsd:include[@schemaLocation]|

                xsd:import[@schemaLocation]|
                xsd:include[@schemaLocation]|

                wsdl:types/xsd:schema/xsd:import[@schemaLocation]|
                wsdl:types/xsd:schema/xsd:include[@schemaLocation]


                ", $cloned);

        $nodes = array();
        foreach ($res as $node) {
            $nodes[]=$node;
        }

        foreach ($nodes as $node) {
            $url = UrlUtils::resolve_url($src->documentURI, $node->getAttribute("schemaLocation"));

            $ci = $this->loadXsd($url);

            $this->parseIncludes($root, $ci);

            $node->parentNode->removeChild($node);
        }

    }
    protected function uniqueTypes(\DOMDocument $dom)
    {
        $xp = new \DOMXPath($dom);
        $xp->registerNamespace("xsd", "http://www.w3.org/2001/XMLSchema");

        $res = $xp->query("//xsd:schema/*[@name]");
        $nodes = array();
        $remove = array();
        foreach ($res as $node) {
            if (!isset($nodes[$node->parentNode->getAttribute("targetNamespace")][$node->getAttribute("name")])) {
                $nodes[$node->parentNode->getAttribute("targetNamespace")][$node->getAttribute("name")]=$node;
            } else {
                $remove[]=$node;
            }
        }
        foreach ($remove as $node) {
            $node->parentNode->removeChild($node);
        }
    }
    protected function loadXsd($src)
    {
        $ci = new DOMDocument();
        if(!@$ci->load($src)){
            throw new \Exception("Can not load/find $src");
        }

        $xp = new \DOMXPath($ci);
        $xp->registerNamespace("xsd", "http://www.w3.org/2001/XMLSchema");

        $nodes = $xp->query("//*[contains(@type,':') and namespace-uri()='http://www.w3.org/2001/XMLSchema']");
        foreach ($nodes as $node) {
            list($prefix, $name ) = explode(":", $node->getAttribute("type"));
            $ns = $node->lookupNamespaceUri($prefix);
            $node->setAttributeNs($ns, $prefix.':ns', $ns);
        }

        return $ci;
    }
}

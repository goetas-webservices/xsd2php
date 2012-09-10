<?php

namespace Goetas\Xsd\XsdToPhp;

use Goetas\Xsd\XsdToPhp\Generator\ClassGenerator;

use XSLTProcessor;
use DOMDocument;

class Xsd2PhpConverter {
	protected $proc;
	protected $generator;
	public function __construct() {
		$this->proc = new XSLTProcessor();
		$this->proc->registerPHPFunctions();
		$this->generator = new ClassGenerator();
	}
	public function addNamespace($namesapce, $phpNamespace) {
		$this->generator->addNamespace($namesapce, $phpNamespace);
	}
	public function addAlias($xsdNs, $xsdType,$xsdType) {
		$this->generator->addAlias($xsdNs, $xsdType, $xsdType);
	}
	public function addArrayType($xsdNs, $xsdType) {
		$this->generator->addArrayType($xsdNs, $xsdType);
	}	
	static function splitPart($node, $base, $find){
		if (strpos($base,':')===false){
			$base=":$base";
		}
		list($prefix, $name)=explode(":", $base);
	
		if($find=='ns'){
			return $node[0]->lookupNamespaceUri($prefix?:null);
		}else{
			return $name;
		}
	}
	public function convert($src, $tns, $destinationDir) {
		$destinationDir = rtrim($destinationDir,"\\/");
		if(!is_dir($destinationDir)){
			throw new \Exception("Invalid destination dir '$destinationDir'");
		}
		
		$xsd = new DOMDocument();
		$xsd->load($src);
		
		
		$merge = new DOMDocument();
		$merge->load(__DIR__."/Resources/mergeXSD.xsl");
		
		$sort = new DOMDocument();
		$sort->load(__DIR__."/Resources/sortXSD.xsl");
		
		$convert = new DOMDocument();
		$convert->load(__DIR__."/Resources/xschema2php2.0.xsl");
		
		$fix = new DOMDocument();
		$fix->load(__DIR__."/Resources/fixPHP.xsl");
		
		$refix = new DOMDocument();
		$refix->load(__DIR__."/Resources/refixPHP.xsl");
		
		$this->proc->importStylesheet($merge);
		$xsd = $this->proc->transformToDoc($xsd);
		
		$this->proc->importStylesheet($sort);
		$xsd = $this->proc->transformToDoc($xsd);
		
		
		$this->proc->importStylesheet($convert);
		$xsd = $this->proc->transformToDoc($xsd);
		
		$this->proc->importStylesheet($fix);
		$xsd = $this->proc->transformToDoc($xsd);
		
		$this->proc->importStylesheet($refix);
		$xsd = $this->proc->transformToDoc($xsd);
		
		$files = $this->generator->generate($xsd, $tns);
		$generated = array();
		foreach ($files as $fullClass => $content){
			
			$fileName = basename(strtr($fullClass,"\\","//"));
			
			$dst = "$destinationDir/$fileName.php";
			
			$generated[$fullClass] = $dst;
			file_put_contents($dst, $content);
		}
		ksort($generated);
		return $generated;
		
	}
}

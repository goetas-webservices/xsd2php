<?php

namespace Goetas\Xsd\XsdToPhp\Generator;
use DOMXPath;
use DOMElement;
use DOMDocument;
class ClassGenerator {
	protected $namespaces = array ();
	protected $skip = array ();
	protected $arrays = array ();
	protected $alias = array ();
	public function __construct(){
		$this->addDefaultPart();
	}
	protected function addDefaultPart(){
		
		//$this->addAlias('http://www.w3.org/2001/XMLSchema', "string", "string");
		$this->addAlias('http://www.w3.org/2001/XMLSchema', "anyURI", "string");
		$this->addAlias('http://www.w3.org/2001/XMLSchema', "anySimpleType", "string");
		
		$this->addAlias('http://www.w3.org/2001/XMLSchema', "dateTime", "DateTime");
		$this->addAlias('http://www.w3.org/2001/XMLSchema', "date", "DateTime");
		
		$this->addAlias('http://www.w3.org/2001/XMLSchema', "gYear", "integer");
		$this->addAlias('http://www.w3.org/2001/XMLSchema', "short", "integer");
		$this->addAlias('http://www.w3.org/2001/XMLSchema', "int", "integer");
		$this->addAlias('http://www.w3.org/2001/XMLSchema', "integer", "integer");
		$this->addAlias('http://www.w3.org/2001/XMLSchema', "integer", "integer");
		
		$this->addAlias('http://www.w3.org/2001/XMLSchema', "decimal", "float");
		$this->addAlias('http://www.w3.org/2001/XMLSchema', "double", "float");
		
		$this->addAlias('http://www.w3.org/2001/XMLSchema', "boolean", "boolean");
		
	}
	public function addNamespace($xsdNs, $phpNs) {
		$this->namespaces [$xsdNs] = $phpNs;
	}
	public function addSkip($xsdNs, $xsdType = '*') {
		$this->skip [$xsdNs] [$xsdType] = $xsdType;
	}
	public function addArrayType($xsdNs, $xsdType) {
		$this->arrays [$xsdNs] [$xsdType] = $xsdType;
	}
	public function addAlias($xsdNs, $xsdType, $phpClass) {
		$this->alias [$xsdNs] [$xsdType] = $phpClass;
	}
	protected function isArrayType(DOMElement $node) {
		if ($node->getAttribute("array")=="true"){
			//return true;
		}
		
		if($node->getAttribute ( "ns" )){
			$ns = $node->getAttribute ( "ns" );
			$name = $node->getAttribute ( "name" );
		}else{
			$ns = $node->getAttribute ( "type-ns" );
			$name = $node->getAttribute ( "type-name" );
		}

		if (isset($this->arrays[$ns])){
			foreach ($this->arrays[$ns] as $match => $class){
				
				
				$match = "/".str_replace("\\*",".*",preg_quote($match,"/"))."/";
								
				if(preg_match($match, $name)){
					return true;
				}
			}
		}
		return false;
	}
	protected function getAlias($ns, $name) {
		if (isset($this->alias[$ns])){
			foreach ($this->alias[$ns] as $match => $class){
				
				$match = "/".str_replace("\\*",".*",preg_quote($match,"/"))."/";
								
				if(preg_match($match, $name)){
					return $class;
				}
			}
		}
		return false;
	}
	protected function hasToSkip(DOMElement $node) {
		$ns = $node->getAttribute ( "ns" );
		$name = $node->getAttribute ( "name" );
		if (isset($this->skip[$ns])){
			foreach ($this->skip[$ns] as $match => $class){
				if(preg_match("/".str_replace("\\*",".*",preg_quote($match,"/"))."/", $name)){
					return $class;
				}
			}
		}
		return false;
	}
	public function generate(DOMDocument $doc, $tns) {
		$xp = new DOMXPath ( $doc );
		
		$files = array ();
		foreach ( $xp->query ( "//class" ) as $node ) {
			
			$ns = $node->getAttribute("ns");

			
			$name = $node->getAttribute("name");
			
			if($tns==$ns && !$this->hasToSkip($node) && !$this->getAlias($ns, $name) && !$this->isArrayType($node)){
				$fullName = $this->getFullClassName ( $node );
				$files [$fullName] = $this->generateClass($node, $xp);
			}
		}
		return $files;
	}
	protected static function calmelCase($name, $lower= false){
		$name = preg_replace ( "/[^a-z0-9]/i", " ", $name );
		$name = ucwords ( $name );
		$name = str_replace ( " ", "", $name );
		if($lower){
			$name = strtolower($name[0]).substr($name, 1);
		}
		return $name;
	}
	protected function getExtends(DOMElement $node, DOMXPath $xp) {
		$extnodeset = $xp->evaluate("extension", $node);
		if($extnodeset->length){
			return $this->getFullClassName($extnodeset->item(0));
		}
	}
	protected function isPhpNative($type){
		$type = trim($type , "\\");
		if(in_array($type, array("integer", "string", "float", "boolean"))){
			return $type;
		}
		return false;
	}
	protected function generateClass(DOMElement $node, DOMXPath $xp) {
		$fullClass = $this->getFullClassName ( $node );
		$pos = strrpos($fullClass, "\\");
		
		$ns = substr($fullClass, 0, $pos);
		$class = substr($fullClass, $pos+1);
		
		
		$content = '<?php'.PHP_EOL.
		'namespace '.$ns.';'.PHP_EOL;
		
		$content .= '/**'.PHP_EOL;
		if($typ = $xp->evaluate("string(@complexity)", $node)){
			$content .=' * @XSDType '.$typ.PHP_EOL;
		}
		if($doc = $xp->evaluate("string(doc)", $node)){
			$content .=' * '.str_replace("\n", "\n * ", trim($doc)).PHP_EOL;
		}
		$content .= ' */ '.PHP_EOL;
		$content .= 'class '.$class;
		
		$fullExtends = $this->getExtends($node, $xp);
		
		if ($fullExtends){	
			$content .= ' extends \\'.$fullExtends;	
		}elseif ($this->isArrayType($node)){
			$content .= ' extends \ArrayObject';
		}
		
		$content .= " {".PHP_EOL;
		
		if (!$this->isArrayType($node)){
		
			$content .= implode(PHP_EOL, $this->geneateConstants($node, $xp)).PHP_EOL;
			
			$content .= implode(PHP_EOL, $this->geneateProperties($node, $xp)).PHP_EOL;
			
			$content .= $this->geneateConstructor($node, $xp).PHP_EOL;
			
			$content .= implode(PHP_EOL, $this->geneatePropertiesMethods($node, $xp)).PHP_EOL;
		}
		
		$content.="}";
		
		return $content;
	}
	protected function geneateConstructor(DOMElement $node, DOMXPath $xp){
		
		$content = '';
		
		$content.= 'public function __construct';
		$content.= '(';
		
		if($node->getAttribute("complexity")=="simpleType"){
			$content.= '$value';	
		}
		
		$content.= ')';
		$content.= '{'.PHP_EOL;
		if($this->getExtends($node, $xp)){
			if($node->getAttribute("complexity")=="simpleType"){
				$content.=$this->tabize('parent::__construct($value);').PHP_EOL;
			}else{
				$content.=$this->tabize('parent::__construct();').PHP_EOL;
			}
		}
		foreach ($xp->query("prop", $node) as $snode){
			if($this->isArrayType($snode) && $snode->getAttribute("required")=="true"){
				$content.=$this->tabize('$this->'.self::calmelCase($snode->getAttribute("name"), true).' = new \ArrayObject();').PHP_EOL;
			}
		}
		
		$content.= '}';
				
		return $this->tabize($content);
	}
	protected function geneateConstants(DOMElement $node, DOMXPath $xp) {
		$props = array();
		foreach ($xp->query("const", $node) as $snode){
			$props[]=$this->generateConstant($snode, $xp);
		}
		return $props;
	}
	protected function generateConstant(DOMElement $node, DOMXPath $xp) {
		$content = '';
			
		$constName = $node->getAttribute("name")?:$node->getAttribute("value");
		$constName = preg_replace("/[a-z0-9_]i/", "", $constName);
		$constName = substr($constName,0, 50);
		
		$content.= 'const '.strtoupper($constName).' = \''.addcslashes($node->getAttribute("value"),"\n'").'\'';
		
		
		$content.=";";
		
		return $this->tabize($content);
	}
	
	protected function geneateProperties(DOMElement $node, DOMXPath $xp) {
		$props = array();
		foreach ($xp->query("prop", $node) as $snode){
			$props[]=$this->geneateProperty($snode, $xp);
		}
		return $props;
	}
	protected function geneatePropertiesMethods(DOMElement $node, DOMXPath $xp) {
		$props = array();
		foreach ($xp->query("prop", $node) as $snode){
			$props[]=$this->geneatePropertyMethods($snode, $xp);
		}
		return $props;
	}
	
	
	protected function getFullClassName(DOMElement $node) {
		
		if($node->getAttribute ( "ns" )){
			$ns = $node->getAttribute ( "ns" );
			$name = $node->getAttribute ( "name" );
		}else{
			$ns = $node->getAttribute ( "type-ns" );
			$name = $node->getAttribute ( "type-name" );
		}	
		
		if ($type = $this->getAlias($ns, $name)){
			$return  = $type; 
			var_dump($return);
		}elseif (isset ( $this->namespaces [$ns] )) {
			$return  = rtrim ( $this->namespaces [$ns], "\\" ) . "\\" . $this->fixClassName ( $name );

		} else {
			throw new \Exception ( "Non trovo nessun associazione al namespace '$ns' per il tipo '$name'" );
		}
		return $return;
	}
	protected function tabize($str, $indent=1) {
		$tabs = str_repeat("\t", $indent);
		return $tabs.str_replace("\n", "\n".$tabs, $str);
	}
	protected function getArrayTypeNode($node, DOMXPath $xp) {
		if($node->getAttribute ( "ns" )){
			$ns = $node->getAttribute ( "ns" );
			$name = $node->getAttribute ( "name" );
		}else{
			$ns = $node->getAttribute ( "type-ns" );
			$name = $node->getAttribute ( "type-name" );
		}
		$res = $xp->query("//class[@name='$name' and @ns='$ns']/prop");
		if(!$res->length){
			throw new \Exception("Non trovo $ns#$name");
		}
		return $res->item(0);
	}
	protected function geneatePropertyMethods(DOMElement $node, DOMXPath $xp) {
		
		$content = '';
		$content .= '/**'.PHP_EOL;
		if($this->isArrayType($node)){
			$content .= ' * @return \ArrayObject';
		}else{
			$cls = $this->getFullClassName($node);
			
			if($this->isPhpNative($cls)){
				$content .= ' * @return '.$cls;
			}else{
				$content .= ' * @return \\'.$cls;
			}
		}
		$content .= PHP_EOL;
		$content .= ' */'.PHP_EOL;
		$content.= 'public function get'.self::calmelCase($node->getAttribute("name"));
		$content.= '(';
		$content.= ')';
		$content.= '{'.PHP_EOL;
		
		
		$content.= $this->tabize('return $this->'.self::calmelCase($node->getAttribute("name"), true).';').PHP_EOL;
		
		$content.= '}'.PHP_EOL;
		
		
		$content .= PHP_EOL;
		

		
		
		if($this->isArrayType($node)){
			
			
			$arrayNode = $this->getArrayTypeNode($node, $xp);
			
			
			
			
			$cls = $this->getFullClassName($arrayNode);
			
			$content .= '/**'.PHP_EOL;
			if($this->isPhpNative($cls)){
				$content .= ' * @param '.$cls;
			}else{
				$content .= ' * @param \\'.$cls;
			}
				
			$content .= PHP_EOL;
			$content .= ' */'.PHP_EOL;
			
			$content.= 'public function add'.self::calmelCase($node->getAttribute("name"));
			$content.= '(';
			if(!$this->isPhpNative($cls)){
				$content.= '\\'.$cls.' ';
			}
			$content.= '$'.self::calmelCase($node->getAttribute("name"), true);
			
			$content.= ')';
			$content.= '{'.PHP_EOL;
			
			$content2 .= '$this->'.self::calmelCase($node->getAttribute("name"), true);
			$content2 .= '[] = $'.self::calmelCase($node->getAttribute("name"), true);
			$content2 .=  ';';
			
		}else{
			$cls = $this->getFullClassName($node);
			$content .= '/**'.PHP_EOL;
			if($this->isPhpNative($cls)){
				$content .= ' * @param '.$this->getFullClassName($node);
			}else{
				$content .= ' * @param \\'.$this->getFullClassName($node);
			}
			
			$content .= PHP_EOL;
			$content .= ' */'.PHP_EOL;
			
			$content.= 'public function set'.self::calmelCase($node->getAttribute("name"));
			$content.= '(';
			
			if(!$this->isPhpNative($cls)){
				$content.= '\\'.$cls.' ';
			}
			$content.= '$'.self::calmelCase($node->getAttribute("name"), true);
			
			
			if (!$node->getAttribute("required")!=='true'){
				$content.=" = null "; 
			}
			
			
			$content.= ')';
			$content.= '{'.PHP_EOL;
			
			$content2 .= '$this->'.self::calmelCase($node->getAttribute("name"), true);
			
			$content2 .= ' = $'.self::calmelCase($node->getAttribute("name"), true);
			$content2 .=  ';';
			
		}
		
		$content.= $this->tabize($content2).PHP_EOL;
		
		$content.= $this->tabize('return $this;').PHP_EOL;
			
		$content.= '}'.PHP_EOL;
		
		return $this->tabize($content);
	}
	protected function geneateProperty(DOMElement $node, DOMXPath $xp) {
		$content = '';	
		$content .= '/**'.PHP_EOL;
		
		if($doc = $xp->evaluate("string(doc)", $node)){
			$content .=' * '.str_replace("\n", "\n * ", trim($doc)).PHP_EOL;
		}
		$cls = $this->getFullClassName($node);
		
		if($this->isPhpNative($cls)){
			$content .= ' * @var '.$cls;
		}elseif($this->isArrayType($node)){
			$content .= ' * @var \ArrayObject[]';
		}else{
			$content .= ' * @var \\'.$cls;
		}
		
		$content .= PHP_EOL;
		
		$content .= ' */'.PHP_EOL;
		$content.= 'protected $'.self::calmelCase($node->getAttribute("name"), true);
		
		if($node->hasAttribute("default") && $this->isPhpNative($cls)=="string"){
			$content .= ' = \''.addslashes($node->getAttribute("default"), "'").'\'';
		}elseif($node->hasAttribute("default") && $this->isPhpNative($cls)=="integer"){
			$content .= ' = '.intval($node->getAttribute("default"));
		}elseif($node->hasAttribute("default") && $this->isPhpNative($cls)=="float"){
			$content .= ' = '.floatval($node->getAttribute("default"));
		}
		
		$content.=";";
		return $this->tabize($content);
	}
	private function fixClassName($class) {
		$ns = '';
		if (strpos ( $class, '#' ) !== false) {
			$parts = explode ( "#", $class );
			$class = array_pop ( $parts );
			$ns = implode ( "\\", $parts );
		}
		
		$fix = function ($name) {
			
			return $name;
		};
		
		$class = self::calmelCase( $class );
		if ($ns) {
			$ns = self::calmelCase( $ns ) . "\\";
		}
		
		return $ns . $class;
	
	}
}


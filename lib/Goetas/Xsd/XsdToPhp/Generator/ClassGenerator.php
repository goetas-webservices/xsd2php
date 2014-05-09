<?php

namespace Goetas\Xsd\XsdToPhp\Generator;
use DOMXPath;
use DOMElement;
use DOMDocument;
class ClassGenerator
{
    protected $namespaces = array ();
    protected $skip = array ();
    protected $arrays = array ();
    protected $alias = array ();
    protected $primitive = array ();

    public function __construct()
    {
        $this->addDefaultPart();

    }
    protected function addDefaultPart()
    {
        $this->primitive['http://www.w3.org/2001/XMLSchema'] = array(
                'integer'=>'integer',
                'string'=>'string',
                'float'=>'float',
                'boolean'=>'boolean',
                'dateTime'=>'DateTime'
        );

        $this->addAlias('http://www.w3.org/2001/XMLSchema', "base64Binary", "string");
        $this->addAlias('http://www.w3.org/2001/XMLSchema', "anyURI", "string");
        $this->addAlias('http://www.w3.org/2001/XMLSchema', "anySimpleType", "string");
        $this->addAlias('http://www.w3.org/2001/XMLSchema', "language", "string");

        $this->addAlias('http://www.w3.org/2001/XMLSchema', "date", "dateTime");

        $this->addAlias('http://www.w3.org/2001/XMLSchema', "gYear", "integer");
        $this->addAlias('http://www.w3.org/2001/XMLSchema', "short", "integer");
        $this->addAlias('http://www.w3.org/2001/XMLSchema', "int", "integer");
        $this->addAlias('http://www.w3.org/2001/XMLSchema', "nonNegativeInteger", "integer");

        $this->addAlias('http://www.w3.org/2001/XMLSchema', "decimal", "float");
        $this->addAlias('http://www.w3.org/2001/XMLSchema', "double", "float");

    }
    public function getNamespace($xsdNs)
    {
        return $this->namespaces [$xsdNs];
    }
    public function addNamespace($xsdNs, $phpNs)
    {
        $this->namespaces [$xsdNs] = $phpNs;
    }
    public function addSkip($xsdNs, $xsdType = '*')
    {
        $this->skip [$xsdNs] [$xsdType] = $xsdType;
    }
    public function addArrayType($xsdNs, $xsdType)
    {
        $this->arrays [$xsdNs] [$xsdType] = $xsdType;
    }
    public function addAlias($ns, $name, $name2, $ns2 = null)
    {
        if ($ns2===null) {
            $ns2 = $ns;
        }
        $this->alias [$ns] [$name] = array("ns"=>$ns2, "name"=>$name2);
    }

    protected function isArrayTypeProp(DOMElement $node)
    {
        if ($node->getAttribute("array")=="true") {
            return 1;
        }

        return $this->isArrayType($node);
    }
    protected function isArrayType(DOMElement $node, $check = true)
    {
        if ($node->getAttribute ( "ns" )) {
            $ns = $node->getAttribute ( "ns" );
            $name = $node->getAttribute ( "name" );
        } else {
            $ns = $node->getAttribute ( "type-ns" );
            $name = $node->getAttribute ( "type-name" );
        }

        if (isset($this->arrays[$ns])) {
            foreach ($this->arrays[$ns] as $match => $class) {

                $match = "/".str_replace("\\*",".+",preg_quote($match,"/"))."/";

                if (preg_match($match, $name)) {
                    return true;
                }
            }
        }

        return false;
    }
    protected function getAlias($ns, $name)
    {
        if (isset($this->alias[$ns])) {
            foreach ($this->alias[$ns] as $match => $class) {
                $match = "/".str_replace("\\*",".*",preg_quote($match,"/"))."/";
                if (preg_match($match, $name)) {
                    return $class;
                }
            }
        }

        return false;
    }
    protected function hasToSkip(DOMElement $node)
    {
        $ns = $node->getAttribute ( "ns" );
        $name = $node->getAttribute ( "name" );
        if (isset($this->skip[$ns])) {
            foreach ($this->skip[$ns] as $match => $class) {
                if (preg_match("/".str_replace("\\*",".*",preg_quote($match,"/"))."/", $name)) {
                    return $class;
                }
            }
        }

        return false;
    }
    public function generateServer(\DOMDocument $doc, $tns, $destDir, $destinationPHP, $extends, $isClient = false)
    {
        $this->addNamespace("wsdl:portType#".$tns, $destinationPHP);

        $xp = new DOMXPath ( $doc );

        $files = array ();
        foreach ( $xp->query ( "//class" ) as $node ) {

            $ns = $node->getAttribute("ns");
            $name = $node->getAttribute("name");

            if (("wsdl:portType#".$tns)==$ns && !$this->isArrayType($node)) {
                $fullClass = $this->getFullClassName ( $node );

                $fileName = basename(strtr($fullClass,"\\","//"));
                $this->generateServerClass($node, $xp, "$destDir/$fileName.php", $extends, $isClient);

                $files [$fullClass] = "$destDir/$fileName.php";

            }
        }

        return $files;
    }
    public function generate(DOMDocument $doc, $tns)
    {
        $xp = new DOMXPath ( $doc );

        $files = array ();
        foreach ( $xp->query ( "//class" ) as $node ) {

            $ns = $node->getAttribute("ns");

            $name = $node->getAttribute("name");
            if ($tns==$ns && !$this->hasToSkip($node) && !isset($this->alias[$ns][$name]) && !$this->isArrayType($node)) {
                $fullName = $this->getFullClassName ( $node );

                $files [$fullName] = $this->generateClass($node, $xp);
            }
        }

        return $files;
    }
    protected static function calmelCase($name, $lower= false)
    {
        $name = preg_replace ( "/[^a-z0-9#]/i", " ", $name );
        $name = ucwords ( $name );
        $name = str_replace ( " ", "", $name );
        if ($lower) {
            $name = strtolower($name[0]).substr($name, 1);
        }

        return $name;
    }
    protected function getExtends(DOMElement $node, DOMXPath $xp)
    {
        $extnodeset = $xp->evaluate("extension", $node);
        if ($extnodeset->length) {
            return $this->getFullClassName($extnodeset->item(0));
        }
    }
    protected function getExtendsNode(DOMElement $node, DOMXPath $xp)
    {
        $extnodeset = $xp->evaluate("extension", $node);
        if ($extnodeset->length) {
            return $extnodeset->item(0);
        }
    }
    protected function isPhpNative($type)
    {
        if (in_array($type, array("integer", "string", "float", "boolean"))) {
            return $type;
        }

        return false;
    }
    protected function generateServerClass(\DOMElement $node, DOMXPath $xp, $save, $fullExtends = null, $isClient = false)
    {
        $fullClass = $this->getFullClassName ( $node );

        if (!$isClient && file_exists($save)) {
            $currentCode = file_get_contents($save);

            $this->_parseTokensInEntityFile($currentCode);

            $methods = array();

            foreach ($xp->query("method", $node) as $methodNode) {
                if (!isset($this->_staticReflection[$fullClass][$methodNode->getAttribute("name")])) {
                    $methods [] = $this->generateServerMethods($methodNode, $xp, $isClient);
                }
            }
            $body = implode(PHP_EOL, $methods).PHP_EOL;
            $last = strrpos($currentCode, '}');
            $content = substr($currentCode, 0, $last) . $body . (strlen($body) > 0 ? "\n" : ''). "}";

            file_put_contents($save, $content);

        } else {
            $pos = strrpos($fullClass, "\\");

            $ns = substr($fullClass, 0, $pos);
            $class = substr($fullClass, $pos+1);

            $content = '<?php'.PHP_EOL;
            $content .='namespace '.$ns.';'.PHP_EOL;
            $content .= '/**'.PHP_EOL;

            $content .= ' * XSD NS: '.$node->getAttribute("ns")."#".$node->getAttribute("name").PHP_EOL;

            $content.= ' * \\'.$this->getFullClassName($node).PHP_EOL;

            if ($doc = $xp->evaluate("string(doc)", $node)) {
                $content .=' * '.str_replace("\n", "\n * ", trim($doc)).PHP_EOL;
            }
            $content .= ' */ '.PHP_EOL;
            $content .= 'class '.$class;

            if (!$fullExtends) {
                $fullExtends = $this->getExtends($node, $xp);
            }

            if ($fullExtends && !$this->isPhpNative($fullExtends)) {
                $content .= ' extends \\'.$fullExtends;
            }

            $content .= " {".PHP_EOL;

            if ($isClient) {

                $construct = 'private $proxy;'.PHP_EOL.PHP_EOL;
                $construct .= 'public function __construct($proxy) {'.PHP_EOL;
                $construct .= $this->tabize('$this->proxy = $proxy;').PHP_EOL;
                $construct .= '}';
                $content.=$this->tabize($construct).PHP_EOL;

            }

            $methods = array();

            foreach ($xp->query("method", $node) as $methodNode) {
                $methods [] = $this->generateServerMethods($methodNode, $xp,  $isClient);
            }

            $content .= implode(PHP_EOL, $methods).PHP_EOL;
            $content.="}";

            file_put_contents($save, $content);
        }

        return $content;
    }

    protected $_staticReflection = array();
    /**
     * @todo this won't work if there is a namespace in brackets and a class outside of it.
     * @param string $src
     */
    private function _parseTokensInEntityFile($src)
    {
        $tokens = token_get_all($src);
        $lastSeenNamespace = "";
        $lastSeenClass = false;

        $inNamespace = false;
        $inClass = false;
        for ($i = 0; $i < count($tokens); $i++) {
            $token = $tokens[$i];
            if (in_array($token[0], array(T_WHITESPACE, T_COMMENT, T_DOC_COMMENT))) {
                continue;
            }

            if ($inNamespace) {
                if ($token[0] == T_NS_SEPARATOR || $token[0] == T_STRING) {
                    $lastSeenNamespace .= $token[1];
                } elseif (is_string($token) && in_array($token, array(';', '{'))) {
                    $inNamespace = false;
                }
            }

            if ($inClass) {
                $inClass = false;
                $lastSeenClass = $lastSeenNamespace . ($lastSeenNamespace ? '\\' : '') . $token[1];
                $this->_staticReflection[$lastSeenClass] = array();
            }

            if ($token[0] == T_NAMESPACE) {
                $lastSeenNamespace = "";
                $inNamespace = true;
            } elseif ($token[0] == T_CLASS) {
                $inClass = true;
            } elseif ($token[0] == T_FUNCTION) {
                if ($tokens[$i+2][0] == T_STRING) {
                    $this->_staticReflection[$lastSeenClass][$tokens[$i+2][1]]= true;
                } elseif ($tokens[$i+2] == "&" && $tokens[$i+3][0] == T_STRING) {
                    $this->_staticReflection[$lastSeenClass][$tokens[$i+3][1]]=true;
                }
            } elseif (in_array($token[0], array(T_VAR, T_PUBLIC, T_PRIVATE, T_PROTECTED)) && $tokens[$i+2][0] != T_FUNCTION) {
                //$this->_staticReflection[$lastSeenClass]['properties'][] = substr($tokens[$i+2][1], 1);
            }
        }
    }
    protected function generateClass(DOMElement $node, DOMXPath $xp)
    {
        $fullClass = $this->getFullClassName ( $node );
        $pos = strrpos($fullClass, "\\");

        $ns = substr($fullClass, 0, $pos);
        $class = substr($fullClass, $pos+1);

        $content = '<?php'.PHP_EOL.
        'namespace '.$ns.';'.PHP_EOL;

        $content .= '/**'.PHP_EOL;
        $content.= ' * \\'.$this->getFullClassName($node).PHP_EOL;

        if ($doc = $xp->evaluate("string(doc)", $node)) {
            $content .=' * '.str_replace("\n", "\n * ", trim($doc)).PHP_EOL;
        }
        $content .= ' */ '.PHP_EOL;
        $content .= 'class '.$class;

        $fullExtends = $this->getExtends($node, $xp);
        $extendsNode = $this->getExtendsNode($node, $xp);

        if ($extendsNode && $this->isArrayType($extendsNode)) {
            $content .= ' extends \ArrayObject';
        } elseif ($fullExtends && !$this->isPhpNative($fullExtends)) {
            $content .= ' extends \\'.$fullExtends;
        }

        $content .= " {".PHP_EOL;


        if (!$this->isArrayType($node)) {

            $content .= implode(PHP_EOL, $this->geneateConstants($node, $xp)).PHP_EOL;
            $content .= implode(PHP_EOL, $this->geneateProperties($node, $xp)).PHP_EOL;

            $content .= $this->geneateConstructor($node, $xp).PHP_EOL;

            if ($this->isPhpNative($fullExtends)) {
                $content .= $this->generateNativeData($node, $xp).PHP_EOL;
            }

            $content .= implode(PHP_EOL, $this->geneatePropertiesMethods($node, $xp)).PHP_EOL;
        }

        $content.="}";

        return $content;
    }
    protected function generateNativeData(DOMElement $node, DOMXPath $xp)
    {
        $content  = PHP_EOL;
        $content .= 'private $__value = \'\';'.PHP_EOL.PHP_EOL;

        $content .= '/**'.PHP_EOL;
        $content .= ' * @return $this'.PHP_EOL;
        $content .= ' */'.PHP_EOL;
        $content .= 'public function set($value) {'.PHP_EOL;
        $content .= $this->tabize('$this->__value = $value;').PHP_EOL;
        $content .= $this->tabize('return $this;').PHP_EOL;
        $content .= '}'.PHP_EOL;

        $content .= 'public function get() {'.PHP_EOL;
        $content .= $this->tabize('return $this->__value;').PHP_EOL;
        $content .= '}'.PHP_EOL;

        $content .= 'public function __toString() {'.PHP_EOL;
        $content .= $this->tabize('return strval($this->__value);').PHP_EOL;
        $content .= '}'.PHP_EOL;

        $content.= '/**'.PHP_EOL;
        $content.= ' * @return \\'.$this->getFullClassName($node).PHP_EOL;
        $content.= '*/'.PHP_EOL;

        $content.= 'public static function create($value) {'.PHP_EOL;
        $content.= $this->tabize('$i = new static();').PHP_EOL;
        $content.= $this->tabize('$i->set($value);').PHP_EOL;
        $content.= $this->tabize('return $i;').PHP_EOL;

        $content.= '}'.PHP_EOL;

        return $this->tabize($content);
    }
    protected function geneateConstructor(DOMElement $node, DOMXPath $xp)
    {
        $content = '';
        $content.= 'public function __construct() {'.PHP_EOL;

        if (($ext = $this->getExtends($node, $xp)) && !$this->isPhpNative($ext)) {
            $content.=$this->tabize('parent::__construct();').PHP_EOL;
        }

        foreach ($xp->query("prop", $node) as $snode) {
            if ($this->isArrayTypeProp($snode)) {
                $content.=$this->tabize('$this->'.self::calmelCase($snode->getAttribute("name"), true).' = new \ArrayObject();').PHP_EOL;
            }
        }

        $content.= '}';

        return $this->tabize($content);
    }
    protected function geneateConstants(DOMElement $node, DOMXPath $xp)
    {
        $props = array();
        foreach ($xp->query("const", $node) as $snode) {
            $props[]=$this->generateConstant($snode, $xp);
        }

        return $props;
    }
    protected function generateConstant(DOMElement $node, DOMXPath $xp)
    {
        $content = '';

        $constName = $node->getAttribute("name")?:$node->getAttribute("value");
        $constName = strtr($constName, "-\\/", "___");
        $constName = preg_replace("/[^a-z0-9_]/i", "", $constName);
        $constName = substr($constName,0, 50);

        $keywords = array('__halt_compiler', 'abstract', 'and', 'array', 'as', 'break', 'callable', 'case', 'catch', 'class', 'clone', 'const', 'continue', 'declare', 'default', 'die', 'do', 'echo', 'else', 'elseif', 'empty', 'enddeclare', 'endfor', 'endforeach', 'endif', 'endswitch', 'endwhile', 'eval', 'exit', 'extends', 'final', 'for', 'foreach', 'function', 'global', 'goto', 'if', 'implements', 'include', 'include_once', 'instanceof', 'insteadof', 'interface', 'isset', 'list', 'namespace', 'new', 'or', 'print', 'private', 'protected', 'public', 'require', 'require_once', 'return', 'static', 'switch', 'throw', 'trait', 'try', 'unset', 'use', 'var', 'while', 'xor');

        if ($constName && !in_array(strtolower($constName), $keywords)) {

            $content.= 'const '.strtoupper($constName).' = \''.addcslashes($node->getAttribute("value"),"\n'").'\';'.PHP_EOL;

            $content.= '/**'.PHP_EOL;
            $content.= ' * @return \\'.$this->getFullClassName($node->parentNode).PHP_EOL;
            $content.= '*/'.PHP_EOL;
            $content.= 'public static function '.strtoupper($constName).'() {'.PHP_EOL;
            $content.= $this->tabize('$i = new static();').PHP_EOL;
            $content.= $this->tabize('$i->set(self::'.strtoupper($constName).');').PHP_EOL;
            $content.= $this->tabize('return $i;').PHP_EOL;
            $content.= '}'.PHP_EOL;

            return $this->tabize($content);
        }
    }

    protected function geneateProperties(DOMElement $node, DOMXPath $xp)
    {
        $props = array();
        foreach ($xp->query("prop", $node) as $snode) {
            $props[]=$this->geneateProperty($snode, $xp);
        }

        return $props;
    }
    protected function geneatePropertiesMethods(DOMElement $node, DOMXPath $xp)
    {
        $props = array();
        foreach ($xp->query("prop", $node) as $snode) {
            $props[]=$this->geneatePropertyMethods($snode, $xp);
        }

        return $props;
    }

    protected function getFullClassName(DOMElement $node)
    {
        if ($node->hasAttribute ( "element-ns" )) {
            $ns = $node->getAttribute ( "element-ns" );
            $name = $node->getAttribute ( "element-name" );
        } elseif ($node->hasAttribute ( "ns" )) {
            $ns = $node->getAttribute ( "ns" );
            $name = $node->getAttribute ( "name" );
        } else {
            $ns = $node->getAttribute ( "type-ns" );
            $name = $node->getAttribute ( "type-name" );
        }

        return $this->getFullClassNamePhp($ns,$name);
    }
    protected function hasPrimitive($ns, $name)
    {
        if (isset ( $this->namespaces [$ns] )) {
            return rtrim ( $this->namespaces [$ns], "\\" ) . "\\" . $this->fixClassName ( $name );
        } elseif (isset ( $this->primitive [$ns]  [$name])) {
            return $this->primitive [$ns]  [$name];

        } elseif (isset ( $this->alias [$ns]  [$name])) {
            return $this->getFullClassNamePhp($this->alias [$ns]  [$name]["ns"], $this->alias [$ns] [$name] ["name"]);
        } else {
            throw new \Exception ( "Non trovo nessun associazione al namespace '$ns' per il tipo '$name'" );
        }
    }
    protected function getFullClassNamePhp($ns, $name)
    {
        if (isset ( $this->namespaces [$ns] )) {
            return rtrim ( $this->namespaces [$ns], "\\" ) . "\\" . $this->fixClassName ( $name );
        } elseif (isset ( $this->primitive [$ns]  [$name])) {
            return $this->primitive [$ns]  [$name];

        } elseif (isset ( $this->alias [$ns]  [$name])) {
            return $this->getFullClassNamePhp($this->alias [$ns]  [$name]["ns"], $this->alias [$ns] [$name] ["name"]);
        } else {
            throw new \Exception ( "Non trovo nessun associazione al namespace '$ns' per il tipo '$name'" );
        }
    }
    protected function tabize($str, $indent=1)
    {
        $tabs = str_repeat("\t", $indent);

        return $tabs.str_replace("\n", "\n".$tabs, $str);
    }
    protected function getArrayTypeNode($node, DOMXPath $xp)
    {
        if ($node->getAttribute ( "ns" )) {
            $ns = $node->getAttribute ( "ns" );
            $name = $node->getAttribute ( "name" );
        } else {
            $ns = $node->getAttribute ( "type-ns" );
            $name = $node->getAttribute ( "type-name" );
        }
        $res = $xp->query("//class[@name='$name' and @ns='$ns']/prop");
        if (!$res->length) {
            throw new \Exception("Non trovo $ns#$name");
        }

        return $res->item(0);
    }
    protected function getArrayTypeNode1($node, DOMXPath $xp)
    {
        if ($node->getAttribute ( "ns" )) {
            $ns = $node->getAttribute ( "ns" );
            $name = $node->getAttribute ( "name" );
        } else {
            $ns = $node->getAttribute ( "type-ns" );
            $name = $node->getAttribute ( "type-name" );
        }
        $res = $xp->query("//class[@name='$name' and @ns='$ns']");
        if (!$res->length) {
            throw new \Exception("Non trovo $ns#$name");
        }

        return $res->item(0);
    }
    protected function generateServerMethods(DOMElement $node, DOMXPath $xp, $isClient = false)
    {
        $content = '';
        $content .= '/**'.PHP_EOL;

        $doc = trim($xp->evaluate("string(doc)", $node));
        if ($doc) {
            $content .= ' * '.str_replace("\n", "\n * ",$doc).PHP_EOL;
        }

        $rets = $xp->query("return/param", $node);

        if (!$rets->length) {
            $content .= ' * @return void';
        } elseif ($rets->length==1) {
            $cls = $this->getFullClassName($rets->item(0));

            if ($this->isPhpNative($cls)) {
                $content .= ' * @return '.$cls;
            } else {
                $content .= ' * @return \\'.$cls;
            }
        } else {
            $content .= ' * @return array';
        }

        $content .= PHP_EOL;
        $content .= ' */'.PHP_EOL;
        $content.= 'public function '.self::calmelCase($node->getAttribute("name"), true);
        $content.= '(';

        $contentParams = array();

        foreach ($xp->query("params/param", $node) as $paramNode) {
            $contentParams[]=$this->generateParam($paramNode, $xp);
        }

        $content.= implode(", ", $contentParams);
        $content.= ')';
        $content.= '{'.PHP_EOL;

        if ($isClient) {
            $content .= $this->tabize('return $this->proxy->__call(__FUNCTION__, func_get_args());').PHP_EOL;
        }

        $content.= '}'.PHP_EOL;
        $content .= PHP_EOL;

        return $this->tabize($content);
    }
    protected function generateParam(DOMElement $node, DOMXPath $xp)
    {
        $cls = $this->getFullClassName($node);
        $content = '';
        if (!$this->isPhpNative($cls)) {
            $content.= '\\'.$cls.' ';
        }
        $content.= '$'.self::calmelCase($node->getAttribute("name"), true);

        return $content;
    }
    protected function geneatePropertyMethods(DOMElement $node, DOMXPath $xp)
    {
        $content = '';
        $content .= '/**'.PHP_EOL;
        if ($this->isArrayTypeProp($node)) {
            $content .= ' * @return \ArrayObject';
        } else {
            $cls = $this->getFullClassName($node);

            if ($this->isPhpNative($cls)) {
                $content .= ' * @return '.$cls;
            } else {
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
        $content2 = '';

        $atype = $this->isArrayTypeProp($node);

        $varName = '$'.self::calmelCase($node->getAttribute("name"), true);
        if ($atype) {

            if ($atype===1) {
                try {
                    $arrayNode = $this->getArrayTypeNode1($node, $xp);
                } catch (\Exception $e) {
                    throw new \Exception("May be array type?", 0, $e);
                }
            } else {
                $arrayNode = $this->getArrayTypeNode($node, $xp);
            }
            $cls = $this->getFullClassName($arrayNode);



            $content .= '/**'.PHP_EOL;
            if ($this->isPhpNative($cls)) {
                $content .= ' * @param '.$varName.' '.$cls;
            } else {
                $content .= ' * @param '.$varName.' \\'.$cls;
            }

            $content .= PHP_EOL;
            $content .= ' */'.PHP_EOL;

            $content.= 'public function add'.self::calmelCase($node->getAttribute("name"));
            $content.= '(';
            if (!$this->isPhpNative($cls)) {
                $content.= '\\'.$cls.' ';
            }
            $content.= '$'.self::calmelCase($node->getAttribute("name"), true);

            $content.= ')';
            $content.= '{'.PHP_EOL;

            $content2 .= '$this->'.self::calmelCase($node->getAttribute("name"), true);
            $content2 .= '[] = $'.self::calmelCase($node->getAttribute("name"), true);
            $content2 .=  ';';

        } else {
            $cls = $this->getFullClassName($node);
            $content .= '/**'.PHP_EOL;
            if ($this->isPhpNative($cls)) {
                $content .= ' * @param '.$varName.' '.$this->getFullClassName($node) . PHP_EOL;
            } else {
                $content .= ' * @param '.$varName.' \\'.$this->getFullClassName($node) . PHP_EOL;
            }

            $content .= ' * @return $this' . PHP_EOL;
            $content .= ' */' . PHP_EOL;

            $content.= 'public function set'.self::calmelCase($node->getAttribute("name"));
            $content.= '(';

            if (!$this->isPhpNative($cls)) {
                $content.= '\\'.$cls.' ';
            }
            $content.= $varName;

            if (!$node->getAttribute("required")!=='true') {
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
    protected function geneateProperty(DOMElement $node, DOMXPath $xp)
    {
        $content = '';
        $content .= '/**'.PHP_EOL;

        if ($doc = $xp->evaluate("string(doc)", $node)) {
            $content .=' * '.str_replace("\n", "\n * ", trim($doc)).PHP_EOL;
        }

        $cls = $this->getFullClassName($node);
        $content .= ' * XSD NS: '.$node->getAttribute("type-ns")."#".$node->getAttribute("type-name").PHP_EOL;
        if ($this->isPhpNative($cls)) {
            $content .= ' * @var '.$cls;
        } elseif ($this->isArrayTypeProp($node)) {
            $content .= ' * @var \ArrayObject[]';
        } else {
            $content .= ' * @var \\'.$cls;
        }

        $content .= PHP_EOL;

        $content .= ' */'.PHP_EOL;
        $content.= 'protected $'.self::calmelCase($node->getAttribute("name"), true);

        if ($node->hasAttribute("default") && $this->isPhpNative($cls)=="string") {
            $content .= ' = \''.addslashes($node->getAttribute("default"), "'").'\'';
        } elseif ($node->hasAttribute("default") && $this->isPhpNative($cls)=="integer") {
            $content .= ' = '.intval($node->getAttribute("default"));
        } elseif ($node->hasAttribute("default") && $this->isPhpNative($cls)=="float") {
            $content .= ' = '.floatval($node->getAttribute("default"));
        }

        $content.=";";
        return $this->tabize($content);
    }
    private function fixClassName($class)
    {
        $class = str_replace("#", "# ", $class);
        $class = self::calmelCase( $class );
        return strtr($class, "#", "\\");

    }
}

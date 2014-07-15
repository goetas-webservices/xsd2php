<?php
namespace Goetas\Xsd\XsdToPhp\Writer;

use Goetas\Xsd\XsdToPhp\Structure\PHPType;
use goetas\xml\wsdl\Exception;

class Psr0Writer implements ClassWriter
{
    protected $dir;
    protected $dir;
    public function __construct($dir)
    {
        $this->dir = $dir;
        if(!is_dir($dir)){
            throw new Exception("La cartella $dir non esiste");
        }
        if(!is_writable($dir)){
            throw new Exception("La cartella $dir non è scrivibile");
        }
    }
    public function write(PHPType $php, $content){
        $d = strtr($php->getNamespace(), "\\", "/");
        $dir = rtrim($this->dir, "/")."/".$d;
        if(!is_dir($dir) && !mkdir ($dir, 0777, true)){
            throw new Exception("Non riesco a creare la cartella $dir");
        }
        return file_put_contents($dir."/".$php->getName().".php", $content);
    }
}
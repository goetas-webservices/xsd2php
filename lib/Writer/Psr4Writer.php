<?php
namespace Goetas\Xsd\XsdToPhp\Writer;

use Goetas\Xsd\XsdToPhp\Structure\PHPType;
use goetas\xml\wsdl\Exception;

class Psr4Writer implements ClassWriter
{

    protected $namespaces;

    public function __construct($namespaces)
    {
        $this->namespaces = $namespaces;

        foreach ($this->namespaces as $namespace => $dir) {

            if ($namespace[strlen($namespace) - 1] !== "\\") {
                throw new Exception("Il namesspace ($namespace) deve terminare con '\\'");
            }
            if (! is_dir($dir)) {
                throw new Exception("La cartella $dir non esiste");
            }
            if (! is_writable($dir)) {
                throw new Exception("La cartella $dir non è scrivibile");
            }
        }
    }

    public function write(PHPType $php, $content)
    {
        foreach ($this->namespaces as $namespace => $dir) {

            if (strpos($php->getNamespace() . "\\", $namespace) === 0) {
                $d = strtr(substr($php->getNamespace(), strlen($namespace)), "\\", "/");
                $dir = rtrim($dir, "/") . "/" . $d;
                if (! is_dir($dir) && ! mkdir($dir, 0777, true)) {
                    throw new Exception("Non riesco a creare la cartella $dir");
                }
                return file_put_contents($dir . "/" . $php->getName() . ".php", $content);
            }
        }
        throw new Exception("Non trovo dove salvare $php");
    }
}


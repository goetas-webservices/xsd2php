<?php
namespace Goetas\Xsd\XsdToPhp\YamlWriter;

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

    public function write($yaml, $content)
    {
        $ns = key($yaml);

        foreach ($this->namespaces as $namespace => $dir) {

            $pos = strpos($ns, $namespace);

            if ($pos === 0) {
                if (! is_dir($dir) && ! mkdir($dir, 0777, true)) {
                    throw new Exception("Non riesco a creare la cartella $dir");
                }
                $f = strtr(substr($ns, strlen($namespace)), "\\/", "..");
                return 1;
                return file_put_contents($dir . "/" . $f . ".yml", $content);
            }
        }
        throw new Exception("Non trovo dove salvare $ns $content");
    }
}


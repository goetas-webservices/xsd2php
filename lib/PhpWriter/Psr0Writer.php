<?php
namespace Goetas\Xsd\XsdToPhp\PhpWriter;

use Goetas\Xsd\XsdToPhp\Structure\PHPType;
use goetas\xml\wsdl\Exception;

/**
 * INCOMPLETE
 * @author Asmir Mustafic <goetas@gmail.com>
 *
 */
class Psr0Writer implements ClassWriter
{

    public function write(PHPType $php, $content)
    {
        foreach ($this->namespaces as $namespace => $dir) {
            if (strpos($php->getNamespace() . "\\", $namespace) === 0) {
                $d = strtr($php->getNamespace(), "\\", "/");
                $dir = rtrim($dir, "/") . "/" . $d;
                if (! is_dir($dir) && ! mkdir($dir, 0777, true)) {
                    throw new WriterException("Can't create the '$dir' directory");
                }

                return file_put_contents($dir . "/" . $php->getName() . ".php", $content);
            }
        }

        throw new WriterException("Can't find a defined location where save '$php' object");
    }


}
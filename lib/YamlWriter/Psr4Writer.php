<?php
namespace Goetas\Xsd\XsdToPhp\YamlWriter;

use Goetas\Xsd\XsdToPhp\Structure\PHPType;
use Goetas\Xsd\XsdToPhp\Writer\Psr4Writer as BasePsr4Writer;
use Goetas\Xsd\XsdToPhp\Writer\WriterException;


class Psr4Writer extends BasePsr4Writer implements ClassWriter
{
    public function write($yaml, $content)
    {
        $ns = key($yaml);

        foreach ($this->namespaces as $namespace => $dir) {

            $pos = strpos($ns, $namespace);

            if ($pos === 0) {
                if (! is_dir($dir) && ! mkdir($dir, 0777, true)) {
                    throw new WriterException("Can't create the folder '$dir'");
                }
                $f = strtr(substr($ns, strlen($namespace)), "\\/", "..");
                return file_put_contents($dir . "/" . $f . ".yml", $content);
            }
        }

        throw new WriterException("Can't find a defined location where save '$content' YAML");
    }
}


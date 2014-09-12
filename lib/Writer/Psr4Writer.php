<?php
namespace Goetas\Xsd\XsdToPhp\Writer;

use Goetas\Xsd\XsdToPhp\Structure\PHPType;

abstract class Psr4Writer
{

    protected $namespaces = array();

    public function __construct(array $namespaces)
    {
        $this->namespaces = $namespaces;

        foreach ($this->namespaces as $namespace => $dir) {
            if ($namespace[strlen($namespace) - 1] !== "\\") {
                throw new WriterException("A non-empty PSR-4 prefix must end with a namespace separator, you entered '$namespace'.");
            }
            if (! is_dir($dir)) {
                throw new WriterException("The folder '$dir' does not exist.");
            }
            if (! is_writable($dir)) {
                throw new WriterException("The folder '$dir' is not writable.");
            }
        }
    }

}


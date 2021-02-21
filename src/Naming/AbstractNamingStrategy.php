<?php

namespace GoetasWebservices\Xsd\XsdToPhp\Naming;

use Doctrine\Inflector\InflectorFactory;
use GoetasWebservices\XML\XSDReader\Schema\Item;
use GoetasWebservices\XML\XSDReader\Schema\Type\Type;

abstract class AbstractNamingStrategy implements NamingStrategy
{
    protected $reservedWords = [
        '__halt_compiler',
        'abstract',
        'and',
        'array',
        'as',
        'bool',
        'break',
        'callable',
        'case',
        'catch',
        'class',
        'clone',
        'const',
        'continue',
        'declare',
        'default',
        'die',
        'do',
        'echo',
        'else',
        'elseif',
        'empty',
        'enddeclare',
        'endfor',
        'endforeach',
        'endif',
        'endswitch',
        'endwhile',
        'eval',
        'exit',
        'extends',
        'false',
        'final',
        'float',
        'for',
        'foreach',
        'function',
        'global',
        'goto',
        'if',
        'implements',
        'include',
        'include_once',
        'instanceof',
        'insteadof',
        'int',
        'interface',
        'isset',
        'list',
        'mixed',
        'namespace',
        'new',
        'null',
        'numeric',
        'object',
        'or',
        'print',
        'private',
        'protected',
        'public',
        'require',
        'require_once',
        'resource',
        'return',
        'static',
        'string',
        'switch',
        'throw',
        'trait',
        'true',
        'try',
        'unset',
        'use',
        'var',
        'while',
        'xor',
    ];

    public function getItemName(Item $item)
    {
        $name = $this->classify($item->getName());
        if (in_array(strtolower($name), $this->reservedWords)) {
            $name .= 'Xsd';
        }

        return $name;
    }

    public function getPropertyName($item)
    {
        return InflectorFactory::create()->build()->camelize(str_replace('.', ' ', $item->getName()));
    }

    protected function classify($name)
    {
        return InflectorFactory::create()->build()->classify(str_replace('.', ' ', $name));
    }
}

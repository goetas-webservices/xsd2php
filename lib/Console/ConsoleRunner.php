<?php
namespace Goetas\Xsd\XsdToPhp\Console;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\HelperSet;

class ConsoleRunner
{

    /**
     * Run console with the given helperset.
     *
     * @param \Symfony\Component\Console\Helper\HelperSet $helperSet
     * @param \Symfony\Component\Console\Command\Command[] $commands
     * @return void
     */
    public function run()
    {
        $cli = new Application('Convert XSD to PHP classes Command Line Interface', "1.0");
        $cli->setCatchExceptions(true);
        $cli->addCommands(array(
            new \Goetas\Xsd\XsdToPhp\Command\ConvertToPHP(),
            new \Goetas\Xsd\XsdToPhp\Command\ConvertToYaml()
        ));
        $cli->run();
    }
}

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
    static public function run($commands = array())
    {
        $cli = new Application('Convert XSD to PHP classes Command Line Interface', "1.0");
        $cli->setCatchExceptions(true);
        self::addCommands($cli);
        $cli->addCommands($commands);
        $cli->run();
    }

    /**
     * @param Application $cli
     */
    static public function addCommands(Application $cli)
    {
        $cli->addCommands(array(
            new \Goetas\Xsd\XsdToPhp\Command\Convert()
        ));
    }
}
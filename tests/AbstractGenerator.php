<?php
namespace GoetasWebservices\Xsd\XsdToPhp\Tests;

use Composer\Autoload\ClassLoader;
use GoetasWebservices\Xsd\XsdToPhp\AbstractConverter;
use GoetasWebservices\Xsd\XsdToPhp\Jms\PathGenerator\Psr4PathGenerator as JmsPsr4PathGenerator;
use GoetasWebservices\Xsd\XsdToPhp\Naming\ShortNamingStrategy;
use GoetasWebservices\Xsd\XsdToPhp\Php\ClassGenerator;
use GoetasWebservices\Xsd\XsdToPhp\Php\PathGenerator\Psr4PathGenerator as PhpPsr4PathGenerator;
use GoetasWebservices\Xsd\XsdToPhp\Writer\JMSWriter;
use GoetasWebservices\Xsd\XsdToPhp\Writer\PHPClassWriter;
use GoetasWebservices\Xsd\XsdToPhp\Writer\PHPWriter;
use JMS\Serializer\Handler\HandlerRegistryInterface;

abstract class AbstractGenerator
{
    protected $targetNs = array();
    protected $aliases = array();

    protected $phpDir;
    protected $jmsDir;

    protected $namingStrategy;

    private $loader;

    public function __construct(array $targetNs, array $aliases = array(), $tmp = null)
    {
        $tmp = $tmp ?: sys_get_temp_dir();

        $this->targetNs = $targetNs;
        $this->aliases = $aliases;

        $this->phpDir = "$tmp/php";
        $this->jmsDir = "$tmp/jms";

        $this->namingStrategy = defined('PHP_WINDOWS_VERSION_BUILD') ? new VeryShortNamingStrategy() : new ShortNamingStrategy();

        $this->loader = new ClassLoader();
        foreach ($this->targetNs as $phpNs) {
            $this->loader->addPsr4($phpNs . "\\", $this->phpDir . "/" . $this->slug($phpNs));
        }
    }

    private static function delTree($dir)
    {
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? self::delTree("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }

    protected function setNamespaces(AbstractConverter $converter)
    {
        foreach ($this->targetNs as $xmlNs => $phpNs) {
            $converter->addNamespace($xmlNs, $phpNs);
        }
        foreach ($this->aliases as $alias) {
            $converter->addAliasMapType(isset($alias[0]) ? $alias[0] : $alias['ns'], isset($alias[1]) ? $alias[1] : $alias['name'], isset($alias[2]) ? $alias[2] : $alias['php']);
        }
    }

    private function slug($str)
    {
        return preg_replace('/[^a-z0-9]/', '_', strtolower($str));
    }

    public function cleanDirectories()
    {
        foreach ($this->targetNs as $phpNs) {
            $phpDir = $this->phpDir . "/" . $this->slug($phpNs);
            $jmsDir = $this->jmsDir . "/" . $this->slug($phpNs);

            foreach ([$phpDir, $jmsDir] as $dir) {
                if (is_dir($dir)) {
                    self::delTree($dir);
                }
                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }
            }
        }
    }

    public function buildSerializer($callback = null, array $metadataDirs = array())
    {
        $serializerBuilder = \JMS\Serializer\SerializerBuilder::create();
        $serializerBuilder->configureHandlers(function (HandlerRegistryInterface $h) use ($callback, $serializerBuilder) {
            $serializerBuilder->addDefaultHandlers();
            if ($callback) {
                call_user_func($callback, $h);
            }
        });

        foreach ($this->targetNs as $phpNs) {
            $metadataDirs[$phpNs] = $this->jmsDir . "/" . $this->slug($phpNs);
        }

        foreach ($metadataDirs as $php => $dir) {
            if (!is_dir($dir)){
                mkdir($dir, 0777, true);
            }
            $serializerBuilder->addMetadataDir($dir, $php);
        }

        return $serializerBuilder->build();
    }

    public function registerAutoloader()
    {
        $this->loader->register();
        return $this->loader;
    }

    public function unRegisterAutoloader()
    {
        //$this->loader->unregister();
    }

    /**
     * @param $items
     */
    protected function writePHP(array $items)
    {
        $paths = array();
        foreach ($this->targetNs as $phpNs) {
            $paths[$phpNs . "\\"] = $this->phpDir . "/" . $this->slug($phpNs);
        }

        $pathGenerator = new PhpPsr4PathGenerator($paths);

        $classWriter = new PHPClassWriter($pathGenerator);
        $writer = new PHPWriter($classWriter, new ClassGenerator());
        $writer->write($items);
    }

    /**
     * @param $items
     */
    protected function writeJMS(array $items)
    {
        $paths = array();
        foreach ($this->targetNs as $phpNs) {
            $paths[$phpNs . "\\"] = $this->jmsDir . "/" . $this->slug($phpNs);
        }

        $pathGenerator = new JmsPsr4PathGenerator($paths);

        $writer = new JMSWriter($pathGenerator);
        $writer->write($items);
    }
}

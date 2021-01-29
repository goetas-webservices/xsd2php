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
use JMS\Serializer\EventDispatcher\EventDispatcherInterface;
use JMS\Serializer\Handler\HandlerRegistryInterface;
use JMS\Serializer\SerializerBuilder;

abstract class AbstractGenerator
{
    protected $targetNs = [];
    protected $aliases = [];
    protected $strictTypes;

    protected $phpDir;
    protected $jmsDir;
    protected $validationDir;

    protected $namingStrategy;

    private $loader;


    public function __construct(array $targetNs, array $aliases = [], $tmp = null, bool $strictTypes = false)
    {
        $tmp = $tmp ?: sys_get_temp_dir();

        $this->targetNs = $targetNs;
        $this->aliases = $aliases;
        $this->strictTypes = $strictTypes;

        $this->phpDir = "$tmp/php";
        $this->jmsDir = "$tmp/jms";
        $this->validationDir = "$tmp/validation";

        $this->namingStrategy = defined('PHP_WINDOWS_VERSION_BUILD') ? new VeryShortNamingStrategy() : new ShortNamingStrategy();

        $this->loader = new ClassLoader();
        foreach ($this->targetNs as $phpNs) {
            $this->loader->addPsr4($phpNs . '\\', $this->phpDir . '/' . $this->slug($phpNs));
        }
    }

    private static function delTree($dir)
    {
        $files = array_diff(scandir($dir), ['.', '..']);
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
            $phpDir = $this->phpDir . '/' . $this->slug($phpNs);
            $jmsDir = $this->jmsDir . '/' . $this->slug($phpNs);
            $validationDir = $this->validationDir . '/' . $this->slug($phpNs);

            foreach ([$phpDir, $jmsDir, $validationDir] as $dir) {
                if (is_dir($dir)) {
                    self::delTree($dir);
                }
                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }
            }
        }
    }

    public function buildSerializer($handlersCallback = null, array $metadataDirs = [], $listenersCallback = null)
    {
        $serializerBuilder = SerializerBuilder::create();
        $serializerBuilder->configureHandlers(function (HandlerRegistryInterface $h) use ($handlersCallback, $serializerBuilder) {
            $serializerBuilder->addDefaultHandlers();
            if ($handlersCallback) {
                call_user_func($handlersCallback, $h);
            }
        });

        $serializerBuilder->configureListeners(function (EventDispatcherInterface $d) use ($listenersCallback, $serializerBuilder) {
            $serializerBuilder->addDefaultListeners();
            if ($listenersCallback) {
                call_user_func($listenersCallback, $d);
            }
        });

        foreach ($this->targetNs as $phpNs) {
            $metadataDirs[$phpNs] = $this->jmsDir . '/' . $this->slug($phpNs);
        }

        foreach ($metadataDirs as $php => $dir) {
            if (!is_dir($dir)) {
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
        $paths = [];
        foreach ($this->targetNs as $phpNs) {
            $paths[$phpNs . '\\'] = $this->phpDir . '/' . $this->slug($phpNs);
        }

        $pathGenerator = new PhpPsr4PathGenerator($paths);

        $classWriter = new PHPClassWriter($pathGenerator);
        $writer = new PHPWriter($classWriter, new ClassGenerator($this->strictTypes));
        $writer->write($items);
    }

    /**
     * @param $items
     */
    protected function writeJMS(array $items)
    {
        $paths = [];
        foreach ($this->targetNs as $phpNs) {
            $paths[$phpNs . '\\'] = $this->jmsDir . '/' . $this->slug($phpNs);
        }

        $pathGenerator = new JmsPsr4PathGenerator($paths);

        $writer = new JMSWriter($pathGenerator);
        $writer->write($items);
    }

    /**
     * @param $items
     */
    protected function writeValidation(array $items)
    {
        $paths = [];
        foreach ($this->targetNs as $phpNs) {
            $paths[$phpNs . '\\'] = $this->validationDir;
        }

        $pathGenerator = new JmsPsr4PathGenerator($paths);

        $writer = new JMSWriter($pathGenerator);
        $writer->write($items);
    }
}

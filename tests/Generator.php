<?php
namespace GoetasWebservices\Xsd\XsdToPhp\Tests;

use GoetasWebservices\Xsd\XsdToPhp\AbstractConverter;
use GoetasWebservices\Xsd\XsdToPhp\Naming\ShortNamingStrategy;

class Generator
{
    protected static $serializer;

    protected $targetNs = array();

    protected $phpDir;
    protected $jmsDir;

    protected $loader;

    private $namingStrategy;

    public function __construct(array $targetNs, array $aliases)
    {
        $tmp = sys_get_temp_dir();

        $this->targetNs = $targetNs;
        $this->aliases = $aliases;

        $this->phpDir = "$tmp/php";
        $this->jmsDir = "$tmp/jms";
        
        $this->namingStrategy = new ShortNamingStrategy();
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
        foreach ( $this->aliases as $alias){
            $converter->addAliasMapType(isset($alias[0])?$alias[0]:$alias['ns'], isset($alias[1])?$alias[1]:$alias['name'], isset($alias[2])?$alias[2]:$alias['php']);
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

            foreach ([$phpDir, $jmsDir] as $dir){
                if (is_dir($dir)) {
                    self::delTree($dir);
                }
                if (!is_dir($dir)) {
                    mkdir($dir);
                }
            }
        }
    }

    public function generate(array $schemas)
    {
        $this->cleanDirectories();
        $this->generatePHPFiles($schemas);
        $this->generateJMSFiles($schemas);
    }
    protected function generatePHPFiles(array $schemas)
    {
        $phpcreator = new PhpConverter();
        $this->setNamespaces($phpcreator);
        $items = $phpcreator->convert($schemas);

        $paths = array();
        foreach ($this->targetNs as $phpNs) {
            $paths[$phpNs . "\\"] = $this->phpDir . "/" . $this->slug($phpNs);
        }

        $pathGenerator = new Psr4PathGenerator($paths);

        $writer = new PHPWriter($pathGenerator);
        $writer->write($items);
    }

    protected function generateJMSFiles(array $schemas)
    {
        $yamlcreator = new YamlConverter(new ShortNamingStrategy());
        $this->setNamespaces($yamlcreator);
        $items = $yamlcreator->convert($schemas);

        $paths = array();
        foreach ($this->targetNs as $phpNs) {
            $paths[$phpNs . "\\"] = $this->jmsDir . "/" . $this->slug($phpNs);
        }

        $pathGenerator = new JmsPsr4PathGenerator($paths);

        $writer = new JMSWriter($pathGenerator);
        $writer->write($items);
    }

    public function buildSerializer($callback)
    {
        $serializerBuiler = \JMS\Serializer\SerializerBuilder::create();
        $serializerBuiler->configureHandlers(function (HandlerRegistryInterface $h) use ($callback, $serializerBuiler) {
            $serializerBuiler->addDefaultHandlers();
            call_user_func($callback, $h);
        });

        foreach ($this->targetNs as $phpNs) {
            $serializerBuiler->addMetadataDir($this->jmsDir . "/" . $this->slug($phpNs), $phpNs);
        }

        return $serializerBuiler->build();
    }

    public function registerAutoloader()
    {
        $loader = new ClassLoader();
        foreach ($this->targetNs as $phpNs) {
            $loader->addPsr4($phpNs . "\\", $this->phpDir . "/" . $this->slug($phpNs));
        }
        $loader->register();
        return $loader;
    }

    public function unRegisterAutoloader()
    {
        $loader = new ClassLoader();
        $loader->unload();
        return $loader;
    }
}
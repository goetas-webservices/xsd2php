<?php

namespace GoetasWebservices\Xsd\XsdToPhp\Tests;

use GoetasWebservices\Xsd\XsdToPhp\Jms\YamlConverter;
use GoetasWebservices\Xsd\XsdToPhp\Jms\YamlValidatorConverter;
use GoetasWebservices\Xsd\XsdToPhp\Php\PhpConverter;
use Symfony\Component\Validator\Validation;

class Generator extends AbstractGenerator
{
    public function generate(array $schemas)
    {
        $this->cleanDirectories();

        $this->writeJMS($this->generateJMSFiles($schemas));
        $this->writePHP($this->generatePHPFiles($schemas));
        $this->writeValidation($this->generateValidationFiles($schemas));
    }

    public function getData(array $schemas)
    {
        $php = $this->generatePHPFiles($schemas);
        $jms = $this->generateJMSFiles($schemas);
        $validation = $this->generateValidationFiles($schemas);

        return [$php, $jms, $validation];
    }

    protected function generatePHPFiles(array $schemas)
    {
        $converter = new PhpConverter($this->namingStrategy);
        $this->setNamespaces($converter);
        $items = $converter->convert($schemas);

        return $items;
    }

    protected function generateJMSFiles(array $schemas)
    {
        $converter = new YamlConverter($this->namingStrategy);
        $this->setNamespaces($converter);
        $items = $converter->convert($schemas);

        return $items;
    }

    protected function generateValidationFiles(array $schemas)
    {
        $converter = new YamlValidatorConverter($this->namingStrategy);
        $this->setNamespaces($converter);
        $items = $converter->convert($schemas);

        return $items;
    }

    /**
     * @return \Symfony\Component\Validator\Validator\RecursiveValidator|\Symfony\Component\Validator\Validator\ValidatorInterface
     */
    public function getValidator()
    {
        $builder = Validation::createValidatorBuilder();

        foreach (glob($this->validationDir . '/*.yml') as $file) {
            $builder->addYamlMapping($file);
        }

        return $builder->getValidator();
    }
}

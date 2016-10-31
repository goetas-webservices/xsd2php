<?php

namespace GoetasWebservices\Xsd\XsdToPhp\Validator;

use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Mapping\Loader\YamlFileLoader;
use JMS\Serializer\Serializer;
use Metadata\MetadataFactoryInterface;

/**
 * This class inherits from YamlFileLoader because we need to call the
 * parseNodes() protected method.
 */
class Validator extends YamlFileLoader 
{
    
    /* @var \Symfony\Component\Validator\Validator\ValidatorInterface the validator service */
    private $validator;

    /* @var array */
    private $loadedConstraint;

    /* @var \Metadata\MetadataFactoryInterface */
    private $metadataFactory;

    /**
     * 
     * @param \Metadata\MetadataFactoryInterface $metadataFactory
     * @param \Symfony\Component\Validator\Validator\ValidatorInterface $validator
     */
    public function __construct(MetadataFactoryInterface $metadataFactory, ValidatorInterface $validator = null) {
        $this->metadataFactory = $metadataFactory;
        $this->validator = isset($validator) ? $validator : Validation::createValidator();
        $this->loadedConstraint = [];
    }

    protected function getMetadataProperties($className) 
    {

        $metadata = $this->metadataFactory->getMetadataForClass($className);

        foreach ($metadata->fileResources as $path) {

            if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'yml') {
                continue;
            }

            $yaml = file_get_contents($path);

            // We parse the yaml validation file
            $parser = new Parser();
            $parsedYaml = $parser->parse($yaml);

            // We transform this validation array to a Constraint array
            $arrayYaml = $this->parseNodes($parsedYaml);

            if (isset($arrayYaml[$className])) {
                $definitions = $arrayYaml[$className];
                if (isset($definitions['properties'])) {
                    return $definitions['properties'];
                }
                break;
            }
        }

        return [];
    }

    /**
     * Read YML and return all class' constraints
     * 
     * @param string $className
     * @return return
     */
    protected function getMetadataConstraints($className) 
    {

        if (isset($this->loadedConstraint[$className])) {
            return $this->loadedConstraint[$className];
        }

        $properties = $this->getMetadataProperties($className);

        $arrayConstraints = [];
        foreach ($properties as $propertyName => $nodes) {
            if (isset($nodes['validator'])) {
                $getter = $nodes['accessor']['getter'];
                $arrayConstraints[$propertyName] = [
                    'getter' => $getter,
                    'validator' => $nodes['validator']
                ];
            }
        }

        $this->loadedConstraint[$className] = $arrayConstraints;

        return $arrayConstraints;
    }

    /**
     * Return recursively list erros by yml validator
     * 
     * @param mixed $data
     * @return array
     */
    protected function recursiveValidate($data) 
    {
        $errors = [];

        if (is_object($data)) {

            $arrayConstraints = $this->getMetadataConstraints(get_class($data));
            foreach ($arrayConstraints as $property => $constraints) {

                $value = $data->$constraints['getter']();

                $violationList = $this->validator->validate($value, $constraints['validator']);

                if (count($violationList) > 0) {
                    $valueErrors = [];
                    foreach ($violationList as $violation) {
                        $valueErrors[] = $violation->getMessage();
                    }
                    if (count($valueErrors)) {
                        $errors[$property] = $valueErrors;
                    }
                }

                if (is_array($value)) {
                    foreach ($value as $index => $item) {
                        $valueErrors = $this->recursiveValidate($item);
                        if (count($valueErrors)) {
                            $errors[$property][$index] = $valueErrors;
                        }
                    }
                } else {
                    $valueErrors = $this->recursiveValidate($value);
                    if (count($valueErrors)) {
                        if (isset($errors[$property])) {
                            $valueErrors = array_merge($errors[$property], $valueErrors);
                        }
                        $errors[$property] = $valueErrors;
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Return all list erros by yml validator
     * 
     * @param object $object
     * @return array
     */
    public function validate($object) 
    {
        return $this->recursiveValidate($object);
    }

}

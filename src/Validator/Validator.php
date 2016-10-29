<?php

namespace GoetasWebservices\Xsd\XsdToPhp\Validator;

use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Mapping\Loader\YamlFileLoader;
use JMS\Serializer\Serializer;

/**
 * This class inherits from YamlFileLoader because we need to call the
 * parseNodes() protected method.
 */
class Validator extends YamlFileLoader
{

    /* @var \Symfony\Component\Validator\Validator\ValidatorInterface the validator service */
    private $validator;

    /* @var object */
    private $object;

    /* @var array */
    private $constraintCache;
    
    /* @var \JMS\Serializer\Serializer */
    private $serializer;

    /**
     * 
     * @param object $object
     * @param \JMS\Serializer\Serializer $serializer
     * @param \Symfony\Component\Validator\Validator\ValidatorInterface $validator
     */
    public function __construct($object, Serializer $serializer, ValidatorInterface $validator = null)
    {
        $this->object = $object;
        $this->serializer = $serializer;
        $this->validator = isset($validator) ? $validator : Validation::createValidator();
        $this->constraintCache = [];
    }
    
    /**
     * Read YML and return all class' constraints
     * 
     * @param string $className
     * @return return
     */
    protected function getMetadataConstraints($className) 
    {
        
        if (isset($this->constraintCache[$className])) {
            return $this->constraintCache[$className];
        }
        
        $metadata = $this->serializer->getMetadataFactory()->getMetadataForClass($className);
        $path = $metadata->fileResources[0];
        
        if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'yml') {
            return [];
        }
        
        $yaml = file_get_contents($path);
        
        // We parse the yaml validation file
        $parser = new Parser();
        $parsedYaml = $parser->parse($yaml);

        // We transform this validation array to a Constraint array
        $arrayYaml = $this->parseNodes($parsedYaml);
        
        $arrayConstraints = [];
        if (($definitions = $arrayYaml[$className]) && ($properties = $definitions['properties'])) {
            foreach ($properties as $propertyName => $nodes) {
                if (isset($nodes['validator'])) {
                    $getter = $nodes['accessor']['getter'];
                    $arrayConstraints[$propertyName] = [
                        'getter' => $getter,
                        'validator' => $nodes['validator']
                    ];
                }
            }
        }
        
        $this->constraintCache[$className] = $arrayConstraints;
        
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
                        $valueErrors = $violation->getMessage();
                    }
                    if (count($valueErrors)) {
                        $errors[$property][] = $valueErrors;
                    }
                }
                
                if (is_object($value) || is_array($value)) {
                    $valueErrors = $this->recursiveValidate($value);
                    if (count($valueErrors)) {
                        $errors[$property][] = $valueErrors;
                    }
                }
                
            }
            
        } 
        
        return $errors;
    }
    
    /**
     * Return all list erros by yml validator
     * 
     * @return array
     */
    public function validate()
    {
        return $this->recursiveValidate( $this->object );
    }

}
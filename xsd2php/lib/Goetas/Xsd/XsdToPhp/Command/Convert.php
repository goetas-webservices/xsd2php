<?php


namespace Goetas\Xsd\XsdToPhp\Command;

use Goetas\DoctrineToXsd\Convert\ConvertToXsd;

use Goetas\DoctrineToXsd\Mapper\TypeMapper;

use Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console,
    Doctrine\ORM\Tools\Console\MetadataFilter,
    Doctrine\ORM\Tools\EntityRepositoryGenerator;

class Convert extends Console\Command\Command
{
    /**
     * @see Console\Command\Command
     */
    protected function configure()
    {
        $this
        ->setName('xsdtophp:convert')
        ->setDescription('Convert XSD into PHP.')
        ->setDefinition(array(
        	new InputArgument(
        		'src', InputArgument::REQUIRED, 'The path where save your XSD.'
        	),
            new InputArgument(
                'destination', InputArgument::REQUIRED, 'The path where save your XSD.'
            ),
            new InputArgument(
                'target-ns', InputArgument::REQUIRED, 'The target namespace for your XSD'
            ),
            new InputOption(
                'ns-map', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'PHP namespaces - XML namepsaces map Syntax = XMLns:PHPns'
            ),
        	new InputOption(
        		'alias-map', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
        		'alias map XMLns:type:XMLns:type'
        	),  
        	new InputOption(
        		'array-map', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
        		'Array map Syntax = XMLns:*'
        	)           
        ))
        ->setHelp("Generate repository classes from your mapping information.");
    }

    /**
     * @see Console\Command\Command
     */
    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {    	
    	
    	
    	$destination = $input->getArgument('destination');
    	$destinationNs = $input->getArgument('target-ns');
    	
    	$allowMap = $input->getOption('ns-map');
		
		if(is_dir($destination)){
			throw new \RuntimeException("Destination could not be a directory.");
		}
		
    	$nsMap = $input->getOption('ns-map');
		if(!$nsMap){
			throw new \RuntimeException(__CLASS__." requires at least one ns-map (for {$destinationNs} namespace).");
		}
		
		$converter = new ConvertToXsd();
				
		$output->writeln("Target namespace: <info>$destinationNs</info>");
		
		$files = array();
		foreach ($nsMap as  $k => $value){
			list($phpNs, $dir, $xmlNs) = explode(":",$value, 3);
			
			$dir = rtrim($dir,"\\//");
			$phpNs = trim(strtr($phpNs, '.','\\'),"\\");
			
			$nsMap[$k]=array(
				"phpNs"=>$phpNs,
				"dir"=>$dir,
				"xmlNs"=>$xmlNs,
			);
			
			$output->writeln("\tDIR: <info>$dir</info>");
			$output->writeln("\tPHP: <comment>$phpNs</comment>");
			$output->writeln("\tXML: <comment>$xmlNs</comment>\n");
			
		}
		$ret = $converter->convert($destination, $destinationNs, $nsMap, $allowMap);
		
		if($ret){
			$output->writeln("Saved to <info>$destination</info>");
			return 0;
		}
		return 1;
    }
}

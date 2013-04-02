<?php

namespace Goetas\Xsd\XsdToPhp;

use Goetas\Xsd\XsdToPhp\Utils\UrlUtils;

use Goetas\Xsd\XsdToPhp\Generator\ClassGenerator;

use XSLTProcessor;
use DOMDocument;

class Xsd2PhpConverter extends Xsd2PhpBase {
	public function convert($src, $tns, $destinationDir) {
		$destinationDir = rtrim($destinationDir,"\\/");
		if(!is_dir($destinationDir)){
			throw new \Exception("Invalid destination dir '$destinationDir'");
		}


		$xsd = $this->getFullSchema($src);
		$xsd->formatOutput = true;
		$xsd->preserveWhiteSpace = false;

		$convert = new DOMDocument();
		$convert->load(__DIR__."/Resources/xschema2php2.0.xsl");

		$fix = new DOMDocument();
		$fix->load(__DIR__."/Resources/fixPHP.xsl");

		$refix = new DOMDocument();
		$refix->load(__DIR__."/Resources/refixPHP.xsl");




		$this->proc->importStylesheet($convert);
		$xsd = $this->proc->transformToDoc($xsd);


		$this->proc->importStylesheet($fix);
		$xsd = $this->proc->transformToDoc($xsd);

		$this->proc->importStylesheet($refix);
		$xsd = $this->proc->transformToDoc($xsd);




		$xp = new \DOMXPath($xsd);
		foreach ($xp->query("//@type-name[contains(., '##')]") as $attr){
			$attr->value = trim(str_replace("##", "#", $attr->value), "#");
		}

		$files = $this->generator->generate($xsd, $tns);
	
		$generated = array();
		$phpNs = $this->getNamespace($tns);
		
		


		foreach ($files as $fullClass => $content){


			if(strpos($fullClass, $phpNs)===0){

				$fileName = trim(strtr(substr($fullClass,strlen($phpNs)), "\\","//"), "/");




				$dst = "$destinationDir/$fileName.php";

				if(!is_dir(dirname($dst))){
					mkdir(dirname($dst));
				}

				$generated[$fullClass] = $dst;
				file_put_contents($dst, $content);
			}
		}
		ksort($generated);
		return $generated;

	}
}

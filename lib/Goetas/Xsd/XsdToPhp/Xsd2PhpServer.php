<?php

namespace Goetas\Xsd\XsdToPhp;

use Goetas\Xsd\XsdToPhp\Utils\UrlUtils;

use Goetas\Xsd\XsdToPhp\Generator\ClassGenerator;

use XSLTProcessor;
use DOMDocument;

class Xsd2PhpServer extends Xsd2PhpBase {
	public function convert($src, $tns, $destinationDir, $destinationPHP,  $extends, $isClient) {
		$destinationDir = rtrim($destinationDir,"\\/");
		if(!is_dir($destinationDir)){
			throw new \Exception("Invalid destination dir '$destinationDir'");
		}

		$xsd = $this->getFullSchema($src);


		$convert = new DOMDocument();
		$convert->load(__DIR__."/Resources/wsdl2class.xsl");


		$this->proc->importStylesheet($convert);
		$xsd = $this->proc->transformToDoc($xsd);

		$xsd->preserveWhiteSpace = false;
		$xsd->formatOutput = true;

		$generated = $this->generator->generateServer($xsd, $tns, $destinationDir,$destinationPHP, $extends, $isClient);
		ksort($generated);
		return $generated;

	}
}

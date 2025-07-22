<?php

namespace GoetasWebservices\Xsd\XsdToPhp\Php\Structure;

use Laminas\Code\Generator\AbstractMemberGenerator;

class PHPProperty extends PHPArg
{
    protected string $visibility = AbstractMemberGenerator::VISIBILITY_PROTECTED;
    protected ?string $fixed = null;

    public function getVisibility(): string
    {
        return $this->visibility;
    }

    public function setVisibility(string $visibility): static
    {
        $this->visibility = $visibility;

        return $this;
    }

    public function getFixed(): ?string
    {
        return $this->fixed;
    }

    public function setFixed(?string $fixed): static
    {
        $this->fixed = $fixed;

        return $this;
    }
}

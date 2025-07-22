<?php

namespace GoetasWebservices\Xsd\XsdToPhp\Php\Structure;

class PHPArg
{
    protected ?string $doc = null;

    protected ?PHPClass $type = null;

    protected ?string $name = null;

    protected bool $nullable = false;

    protected string|array|null $default = null;

    public function __construct(?string $name = null, ?PHPClass $type = null)
    {
        $this->name = $name;
        $this->type = $type;
    }

    public function getDoc(): ?string
    {
        return $this->doc;
    }

    public function setDoc(?string $doc): static
    {
        $this->doc = $doc;

        return $this;
    }

    public function getType(): ?PHPClass
    {
        return $this->type;
    }

    public function setType(?PHPClass $type): PHPArg
    {
        $this->type = $type;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): PHPArg
    {
        $this->name = $name;

        return $this;
    }

    public function getNullable(): bool
    {
        return $this->nullable;
    }

    public function setNullable(bool $nullable): PHPArg
    {
        $this->nullable = $nullable;

        return $this;
    }

    public function getDefault(): string|array|null
    {
        return $this->default;
    }

    public function setDefault(string|array|null $default): PHPArg
    {
        $this->default = $default;

        return $this;
    }
}

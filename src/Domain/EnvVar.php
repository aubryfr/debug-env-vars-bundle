<?php

namespace Aubry\EnvVarsDebug\Domain;

class EnvVar
{
    public const TYPE_STRING = "string";
    public const TYPE_BOOL = "bool";
    public const TYPE_INT =  "int";

    private const AVAILABLE_TYPES = [
        self::TYPE_STRING,
        self::TYPE_BOOL,
        self::TYPE_INT,
    ];

    /**
     * @var string
     */
    private $name;

    /**
     * @var bool
     */
    private $isMandatory;

    /**
     * @var string
     */
    private $type;

    /**
     * @param string $name
     * @param bool $isMandatory
     * @param string $type
     *
     * @throws \DomainException
     *
     */
    public function __construct(string $name, bool $isMandatory, string $type = self::TYPE_STRING)
    {
        $this->name = $name;
        $this->isMandatory = $isMandatory;
        $this->setType($type);
    }

    /**
     * @param string $type
     *
     * @return self
     *
     * @throws \DomainException
     */
    private function setType(string $type): self
    {
        if (!in_array($type, self::AVAILABLE_TYPES)) {
            throw new \DomainException(sprintf('Unhandled type : %s', $type));
        }

        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function isMandatory(): bool
    {
        return $this->isMandatory;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }
}

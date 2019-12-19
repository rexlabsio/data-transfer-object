<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject;

/**
 * Class ClassData
 * @package Rexlabs\DataTransferObject
 */
class ClassData
{
    /** @var string */
    public $namespace;

    /** @var string[] */
    public $useStatements;

    /** @var string */
    public $docComment;

    /** @var array */
    public $defaults;

    /** @var int */
    public $defaultFlags;

    /**
     * ClassData constructor.
     * @param string $namespace
     * @param array $useStatements
     * @param string $docComment
     * @param array $defaults
     * @param int $defaultFlags
     */
    public function __construct(
        string $namespace,
        array $useStatements,
        string $docComment,
        array $defaults,
        int $defaultFlags
    ) {
        $this->namespace = $namespace;
        $this->useStatements = $useStatements;
        $this->docComment = $docComment;
        $this->defaults = $defaults;
        $this->defaultFlags = $defaultFlags;
    }
}

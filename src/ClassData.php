<?php

declare(strict_types=1);

namespace Rexlabs\DataTransferObject;

use Illuminate\Support\Collection;

/**
 * Class ClassData
 * @package Rexlabs\DataTransferObject
 */
class ClassData
{
    /** @var string */
    public $namespace;

    /** @var Collection|string[] */
    public $useStatements;

    /** @var array */
    public $defaults;

    /** @var string */
    public $docComment;

    /**
     * ClassData constructor.
     * @param string $namespace
     * @param Collection $useStatements
     * @param array $defaults
     * @param string $docComment
     */
    public function __construct(
        string $namespace,
        Collection $useStatements,
        array $defaults,
        string $docComment
    ) {
        $this->namespace = $namespace;
        $this->useStatements = $useStatements;
        $this->defaults = $defaults;
        $this->docComment = $docComment;
    }
}

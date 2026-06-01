<?php

namespace Efabrica\TranslationsAutomatization\Command\CheckTranslations;

use PhpParser\Node;

class ProjectMethodDefinition
{
    public string $className;

    public string $methodName;

    /** @var string[] */
    public array $parameterNames;

    /** @var array<string, string> */
    public array $parameterTypes;

    public ?string $file;

    public ?string $returnType;

    private ?MethodBodyCache $bodyCache;

    /**
     * Eagerly-supplied statements. Kept for backward compatibility with callers that constructed
     * ProjectMethodDefinition with explicit Stmt[] (e.g. tests). When null, getStatements() falls
     * back to MethodBodyCache, which re-parses the source file on demand.
     *
     * @var Node\Stmt[]|null
     */
    private ?array $eagerStatements;

    /**
     * @param string[] $parameterNames
     * @param array<string, string> $parameterTypes
     * @param Node\Stmt[]|null $eagerStatements
     */
    public function __construct(
        string $className,
        string $methodName,
        array $parameterNames,
        array $parameterTypes,
        ?string $file = null,
        ?MethodBodyCache $bodyCache = null,
        ?string $returnType = null,
        ?array $eagerStatements = null
    ) {
        $this->className = $className;
        $this->methodName = $methodName;
        $this->parameterNames = $parameterNames;
        $this->parameterTypes = $parameterTypes;
        $this->file = $file;
        $this->bodyCache = $bodyCache;
        $this->returnType = $returnType;
        $this->eagerStatements = $eagerStatements;
    }

    /**
     * @return Node\Stmt[]
     */
    public function getStatements(): array
    {
        if ($this->eagerStatements !== null) {
            return $this->eagerStatements;
        }
        if ($this->file === null || $this->bodyCache === null) {
            return [];
        }
        return $this->bodyCache->loadMethodStatements($this->file, $this->className, $this->methodName);
    }
}

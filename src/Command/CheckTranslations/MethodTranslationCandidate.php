<?php

namespace Efabrica\TranslationsAutomatization\Command\CheckTranslations;

use PhpParser\Node\Expr;

class MethodTranslationCandidate
{
    public Expr $expression;

    /** @var string[]|null null = statically unknown */
    public ?array $parameterNames;

    public string $call;

    public ?string $declaringClassName;

    /** @var MethodTranslationGuard[] */
    public array $guards;

    /**
     * @param string[]|null $parameterNames
     * @param MethodTranslationGuard[] $guards
     */
    public function __construct(Expr $expression, string $call, ?array $parameterNames = null, array $guards = [], ?string $declaringClassName = null)
    {
        $this->expression = $expression;
        $this->parameterNames = $parameterNames;
        $this->call = $call;
        $this->guards = $guards;
        $this->declaringClassName = $declaringClassName;
    }
}

<?php

namespace Efabrica\TranslationsAutomatization\Command\CheckTranslations;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\Return_;

class MethodSummaryResolver
{
    private ProjectClassIndex $classIndex;

    private ExpressionSubstitutor $expressionSubstitutor;

    /** @var array<string, MethodTranslationCandidate[]> */
    private array $cache = [];

    /** @var array<string, bool> */
    private array $resolving = [];

    /** @var array<string, bool> */
    private array $resolvingReturnedObjectCalls = [];

    /** @var array<string, array<string, string>> */
    private array $closureTranslatedConstructorParameters = [];

    public function __construct(ProjectClassIndex $classIndex, ?ExpressionSubstitutor $expressionSubstitutor = null)
    {
        $this->classIndex = $classIndex;
        $this->expressionSubstitutor = $expressionSubstitutor ?? new ExpressionSubstitutor();
    }

    /**
     * @return MethodTranslationCandidate[]
     */
    public function resolveMethod(string $className, string $methodName): array
    {
        $cacheKey = $className . '::' . $methodName;
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        if (isset($this->resolving[$cacheKey])) {
            return [];
        }

        $definition = $this->classIndex->findMethod($className, $methodName);
        if ($definition === null) {
            return [];
        }

        $this->resolving[$cacheKey] = true;
        $candidates = $this->collectCandidatesFromStatements($definition->getStatements(), $className, [], []);
        unset($this->resolving[$cacheKey]);

        return $this->cache[$cacheKey] = $candidates;
    }

    /**
     * @param array<int, Node\Arg|Node\VariadicPlaceholder> $arguments
     * @return MethodTranslationCandidate[]
     */
    public function resolveCall(string $className, string $methodName, array $arguments, ?string $sourceClassName = null): array
    {
        return array_merge(
            $this->applyNestedMethodSummary($className, $methodName, $arguments),
            $this->resolveReturnedObjectCandidatesForCall($className, $methodName, $arguments, $sourceClassName)
        );
    }

    /**
     * @param MethodTranslationGuard[] $guards
     * @param array<string, Expr> $substitutions
     * @return MethodTranslationCandidate[]
     */
    private function collectCandidatesFromNode(Node $node, string $className, array $guards, array $substitutions = []): array
    {
        $candidates = [];

        if ($node instanceof If_) {
            return $this->collectCandidatesFromIf($node, $className, $guards, $substitutions);
        }

        if ($node instanceof Expr\Ternary) {
            return $this->collectCandidatesFromTernary($node, $className, $guards, $substitutions);
        }

        if ($node instanceof MethodCall && $node->name instanceof Node\Identifier) {
            $methodName = $node->name->toString();
            $firstArgumentValue = $this->getArgumentValue($node->args, 0);
            $secondArgumentValue = $this->getArgumentValue($node->args, 1);
            if (strtolower($methodName) === 'translate' && $firstArgumentValue instanceof Expr) {
                $resolvedExpression = $this->expressionSubstitutor->substitute($firstArgumentValue, $substitutions);
                $pluralKey = null;
                if ($secondArgumentValue instanceof Expr\Array_) {
                    $firstItem = $secondArgumentValue->items[0] ?? null;
                    if ($firstItem !== null && $firstItem->key instanceof Node\Scalar\String_) {
                        $pluralKey = $firstItem->key->value;
                    }
                }

                $candidates[] = new MethodTranslationCandidate($resolvedExpression, $methodName, $pluralKey, $this->substituteGuards($guards, $substitutions), $className);
            } else {
                $calleeClass = $this->resolveCalleeClassFromMethodCall($node, $className);
                if ($calleeClass !== null) {
                    $candidates = array_merge($candidates, $this->applyNestedMethodSummary($calleeClass, $methodName, $this->substituteArguments($node->args, $substitutions)));
                }
            }
        }

        if ($node instanceof StaticCall && $node->name instanceof Node\Identifier) {
            $methodName = $node->name->toString();
            $firstArgumentValue = $this->getArgumentValue($node->args, 0);
            $secondArgumentValue = $this->getArgumentValue($node->args, 1);
            if (strtolower($methodName) === 'translate' && $firstArgumentValue instanceof Expr) {
                $resolvedExpression = $this->expressionSubstitutor->substitute($firstArgumentValue, $substitutions);
                $pluralKey = null;
                if ($secondArgumentValue instanceof Expr\Array_) {
                    $firstItem = $secondArgumentValue->items[0] ?? null;
                    if ($firstItem !== null && $firstItem->key instanceof Node\Scalar\String_) {
                        $pluralKey = $firstItem->key->value;
                    }
                }

                $candidates[] = new MethodTranslationCandidate($resolvedExpression, $methodName, $pluralKey, $this->substituteGuards($guards, $substitutions), $className);
            } else {
                $calleeClass = $this->resolveCalleeClassFromStaticCall($node, $className);
                if ($calleeClass !== null) {
                    $candidates = array_merge($candidates, $this->applyNestedMethodSummary($calleeClass, $methodName, $this->substituteArguments($node->args, $substitutions)));
                }
            }
        }

        foreach ($node->getSubNodeNames() as $subNodeName) {
            $child = $node->$subNodeName;
            if ($child instanceof Node) {
                $candidates = array_merge($candidates, $this->collectCandidatesFromNode($child, $className, $guards, $substitutions));
                continue;
            }

            if (is_array($child)) {
                $childSubstitutions = $substitutions;
                foreach ($child as $item) {
                    if ($item instanceof Node) {
                        $candidates = array_merge($candidates, $this->collectCandidatesFromNode($item, $className, $guards, $childSubstitutions));
                        $this->collectLocalAssignmentSubstitution($item, $childSubstitutions);
                    }
                }
            }
        }

        return $candidates;
    }

    /**
     * @param Node\Stmt[] $statements
     * @param MethodTranslationGuard[] $guards
     * @param array<string, Expr> $substitutions
     * @return MethodTranslationCandidate[]
     */
    private function collectCandidatesFromStatements(array $statements, string $className, array $guards, array $substitutions): array
    {
        $candidates = [];
        foreach ($statements as $statement) {
            $candidates = array_merge($candidates, $this->collectCandidatesFromNode($statement, $className, $guards, $substitutions));
            $this->collectLocalAssignmentSubstitution($statement, $substitutions);
        }

        return $candidates;
    }

    /**
     * @param array<int, Node\Arg|Node\VariadicPlaceholder> $arguments
     * @return MethodTranslationCandidate[]
     */
    private function applyNestedMethodSummary(string $className, string $methodName, array $arguments): array
    {
        $nestedCandidates = $this->resolveMethod($className, $methodName);
        if ($nestedCandidates === []) {
            return [];
        }

        $definition = $this->classIndex->findMethod($className, $methodName);
        if ($definition === null) {
            return [];
        }

        $substitutions = [];
        foreach ($definition->parameterNames as $index => $parameterName) {
            $argumentValue = $this->getArgumentValue($arguments, $index);
            if ($argumentValue instanceof Expr) {
                $substitutions[$parameterName] = $argumentValue;
            }
        }

        $resolved = [];
        foreach ($nestedCandidates as $candidate) {
            $resolvedGuards = [];
            foreach ($candidate->guards as $guard) {
                $resolvedGuards[] = new MethodTranslationGuard(
                    $this->expressionSubstitutor->substitute($guard->expression, $substitutions),
                    $guard->expectedValue
                );
            }

            $resolved[] = new MethodTranslationCandidate(
                $this->expressionSubstitutor->substitute($candidate->expression, $substitutions),
                $candidate->call,
                $candidate->pluralKey,
                $resolvedGuards,
                $candidate->declaringClassName
            );
        }

        return $resolved;
    }

    /**
     * @param array<int, Arg|Node\VariadicPlaceholder> $arguments
     * @return MethodTranslationCandidate[]
     */
    private function resolveReturnedObjectCandidatesForCall(string $className, string $methodName, array $arguments, ?string $sourceClassName): array
    {
        $callKey = $className . '::' . $methodName;
        if (isset($this->resolvingReturnedObjectCalls[$callKey])) {
            return [];
        }

        $definition = $this->classIndex->findMethod($className, $methodName);
        if ($definition === null) {
            return [];
        }

        $this->resolvingReturnedObjectCalls[$callKey] = true;

        $declaredReturnCandidates = $this->resolveDeclaredReturnTypeCandidates($definition, $arguments, $sourceClassName);
        if ($declaredReturnCandidates !== []) {
            unset($this->resolvingReturnedObjectCalls[$callKey]);
            return $declaredReturnCandidates;
        }

        $substitutions = [];
        foreach ($definition->parameterNames as $index => $parameterName) {
            $argumentValue = $this->getArgumentValue($arguments, $index);
            if ($argumentValue instanceof Expr) {
                $substitutions[$parameterName] = $argumentValue;
            }
        }

        $candidates = [];
        $candidates = $this->collectReturnedObjectCandidatesFromStatements(
            $definition->getStatements(),
            $className,
            $substitutions,
            $sourceClassName
        );

        unset($this->resolvingReturnedObjectCalls[$callKey]);

        return $candidates;
    }

    /**
     * @param array<int, Arg|Node\VariadicPlaceholder> $arguments
     * @return MethodTranslationCandidate[]
     */
    private function resolveDeclaredReturnTypeCandidates(ProjectMethodDefinition $definition, array $arguments, ?string $sourceClassName): array
    {
        if ($definition->returnType === null) {
            return [];
        }

        $translatedParameters = $this->findClosureTranslatedConstructorParameters($definition->returnType);
        if ($translatedParameters === []) {
            return [];
        }

        $candidates = [];
        foreach (array_keys($translatedParameters) as $parameterName) {
            $parameterIndex = array_search($parameterName, $definition->parameterNames, true);
            if ($parameterIndex === false) {
                continue;
            }

            $argumentValue = $this->getArgumentValue($arguments, $parameterIndex);
            if (!$argumentValue instanceof Expr) {
                continue;
            }

            $closureExpression = $this->extractClosureBodyExpression($argumentValue);
            if ($closureExpression === null) {
                continue;
            }

            $candidates[] = new MethodTranslationCandidate(
                $closureExpression,
                'translate',
                null,
                [],
                $sourceClassName ?? $definition->className
            );
        }

        return $candidates;
    }

    /**
     * @param Node\Stmt[] $statements
     * @param array<string, Expr> $substitutions
     * @return MethodTranslationCandidate[]
     */
    private function collectReturnedObjectCandidatesFromStatements(array $statements, string $currentClassName, array $substitutions, ?string $sourceClassName): array
    {
        $candidates = [];
        foreach ($statements as $statement) {
            $candidates = array_merge(
                $candidates,
                $this->collectReturnedObjectCandidatesFromNode($statement, $currentClassName, $substitutions, $sourceClassName)
            );
            $this->collectLocalAssignmentSubstitution($statement, $substitutions);
        }

        return $candidates;
    }

    /**
     * @param array<string, Expr> $substitutions
     * @return MethodTranslationCandidate[]
     */
    private function collectReturnedObjectCandidatesFromNode(Node $node, string $currentClassName, array $substitutions, ?string $sourceClassName): array
    {
        $candidates = [];

        if ($node instanceof Return_ && $node->expr instanceof Expr) {
            return $this->resolveReturnedExpressionCandidates($node->expr, $currentClassName, $substitutions, $sourceClassName);
        }

        foreach ($node->getSubNodeNames() as $subNodeName) {
            $child = $node->$subNodeName;
            if ($child instanceof Node) {
                $candidates = array_merge($candidates, $this->collectReturnedObjectCandidatesFromNode($child, $currentClassName, $substitutions, $sourceClassName));
                continue;
            }

            if (is_array($child)) {
                foreach ($child as $item) {
                    if ($item instanceof Node) {
                        $candidates = array_merge($candidates, $this->collectReturnedObjectCandidatesFromNode($item, $currentClassName, $substitutions, $sourceClassName));
                    }
                }
            }
        }

        return $candidates;
    }

    /**
     * @param array<string, Expr> $substitutions
     * @return MethodTranslationCandidate[]
     */
    private function resolveReturnedExpressionCandidates(Expr $expression, string $currentClassName, array $substitutions, ?string $sourceClassName): array
    {
        if ($expression instanceof New_) {
            return $this->resolveCandidatesFromNewExpression($expression, $currentClassName, $substitutions, $sourceClassName);
        }

        if ($expression instanceof MethodCall && $expression->name instanceof Node\Identifier) {
            $calleeClass = $this->resolveCalleeClassFromMethodCall($expression, $currentClassName);
            if ($calleeClass === null) {
                return [];
            }

            return $this->resolveReturnedObjectCandidatesForCall(
                $calleeClass,
                $expression->name->toString(),
                $this->substituteArguments($expression->args, $substitutions),
                $sourceClassName
            );
        }

        if ($expression instanceof StaticCall && $expression->name instanceof Node\Identifier) {
            $calleeClass = $this->resolveCalleeClassFromStaticCall($expression, $currentClassName);
            if ($calleeClass === null) {
                return [];
            }

            return $this->resolveReturnedObjectCandidatesForCall(
                $calleeClass,
                $expression->name->toString(),
                $this->substituteArguments($expression->args, $substitutions),
                $sourceClassName
            );
        }

        return [];
    }

    /**
     * @param array<string, Expr> $substitutions
     * @return MethodTranslationCandidate[]
     */
    private function resolveCandidatesFromNewExpression(New_ $expression, string $currentClassName, array $substitutions, ?string $sourceClassName): array
    {
        if (!$expression->class instanceof Node\Name) {
            return [];
        }

        $className = $expression->class->toString();
        if (in_array(strtolower($className), ['self', 'static', 'parent'], true)) {
            $className = $currentClassName;
        }

        $translatedParameters = $this->findClosureTranslatedConstructorParameters($className);
        if ($translatedParameters === []) {
            return [];
        }

        $constructor = $this->classIndex->findMethod($className, '__construct');
        if ($constructor === null) {
            return [];
        }

        $arguments = $this->substituteArguments($expression->args, $substitutions);
        $candidates = [];
        foreach ($translatedParameters as $parameterName => $mode) {
            $parameterIndex = array_search($parameterName, $constructor->parameterNames, true);
            if ($parameterIndex === false) {
                continue;
            }

            $argumentValue = $this->getArgumentValue($arguments, $parameterIndex);
            if (!$argumentValue instanceof Expr) {
                continue;
            }

            $closureExpression = $this->extractClosureBodyExpression($argumentValue);
            if ($closureExpression === null) {
                continue;
            }

            $candidateExpression = $mode === 'keys'
                ? $closureExpression
                : $closureExpression;

            $candidates[] = new MethodTranslationCandidate(
                $candidateExpression,
                'translate',
                null,
                [],
                $sourceClassName ?? $currentClassName
            );
        }

        return $candidates;
    }

    /**
     * @return array<string, string>
     */
    private function findClosureTranslatedConstructorParameters(string $className): array
    {
        if (isset($this->closureTranslatedConstructorParameters[$className])) {
            return $this->closureTranslatedConstructorParameters[$className];
        }

        $classDefinition = $this->classIndex->findClass($className);
        $constructor = $this->classIndex->findMethod($className, '__construct');
        if ($classDefinition === null || $constructor === null) {
            return $this->closureTranslatedConstructorParameters[$className] = [];
        }

        $constructorParameters = array_fill_keys($constructor->parameterNames, true);
        $translatedParameters = [];
        foreach ($classDefinition->methods as $methodDefinition) {
            if ($methodDefinition->methodName === '__construct') {
                continue;
            }

            $origins = [];
            $this->scanClosureTranslationOrigins($methodDefinition->getStatements(), $constructorParameters, $origins, $translatedParameters);
        }

        return $this->closureTranslatedConstructorParameters[$className] = $translatedParameters;
    }

    /**
     * @param Node\Stmt[] $statements
     * @param array<string, bool> $constructorParameters
     * @param array<string, array{type: string, parameter: string}> $origins
     * @param array<string, string> $translatedParameters
     */
    private function scanClosureTranslationOrigins(array $statements, array $constructorParameters, array &$origins, array &$translatedParameters): void
    {
        foreach ($statements as $statement) {
            $this->scanClosureTranslationNode($statement, $constructorParameters, $origins, $translatedParameters);
        }
    }

    /**
     * @param array<string, bool> $constructorParameters
     * @param array<string, array{type: string, parameter: string}> $origins
     * @param array<string, string> $translatedParameters
     */
    private function scanClosureTranslationNode(Node $node, array $constructorParameters, array &$origins, array &$translatedParameters): void
    {
        if ($node instanceof Assign && $node->var instanceof Expr\Variable && is_string($node->var->name)) {
            $origin = $this->resolveOriginFromExpression($node->expr, $constructorParameters, $origins);
            if ($origin !== null) {
                $origins[$node->var->name] = $origin;
            }
        }

        if ($node instanceof Foreach_ && $node->expr instanceof Expr\Variable && is_string($node->expr->name)) {
            $iteratedOrigin = $origins[$node->expr->name] ?? null;
            if ($iteratedOrigin !== null && $iteratedOrigin['type'] === 'closure_result') {
                if ($node->valueVar instanceof Expr\Variable && is_string($node->valueVar->name)) {
                    $origins[$node->valueVar->name] = [
                        'type' => 'closure_result_values',
                        'parameter' => $iteratedOrigin['parameter'],
                    ];
                }

                if ($node->keyVar instanceof Expr\Variable && is_string($node->keyVar->name)) {
                    $origins[$node->keyVar->name] = [
                        'type' => 'closure_result_keys',
                        'parameter' => $iteratedOrigin['parameter'],
                    ];
                }
            }

            $this->scanClosureTranslationOrigins($node->stmts, $constructorParameters, $origins, $translatedParameters);
            return;
        }

        if ($node instanceof MethodCall
            && $node->name instanceof Node\Identifier
            && strtolower($node->name->toString()) === 'translate'
            && isset($node->args[0])
            && $node->args[0] instanceof Arg
            && $node->args[0]->value instanceof Expr\Variable
            && is_string($node->args[0]->value->name)
        ) {
            $origin = $origins[$node->args[0]->value->name] ?? null;
            if ($origin !== null && $origin['type'] === 'closure_result_values') {
                $translatedParameters[$origin['parameter']] = 'values';
            }
        }

        foreach ($node->getSubNodeNames() as $subNodeName) {
            $child = $node->$subNodeName;
            if ($child instanceof Node) {
                $this->scanClosureTranslationNode($child, $constructorParameters, $origins, $translatedParameters);
                continue;
            }

            if (is_array($child)) {
                foreach ($child as $item) {
                    if ($item instanceof Node) {
                        $this->scanClosureTranslationNode($item, $constructorParameters, $origins, $translatedParameters);
                    }
                }
            }
        }
    }

    /**
     * @param array<string, bool> $constructorParameters
     * @param array<string, array{type: string, parameter: string}> $origins
     * @return array{type: string, parameter: string}|null
     */
    private function resolveOriginFromExpression(Expr $expression, array $constructorParameters, array $origins): ?array
    {
        if ($expression instanceof MethodCall
            && $expression->name instanceof Node\Identifier
            && $expression->name->toString() === '__invoke'
            && $expression->var instanceof PropertyFetch
            && $expression->var->var instanceof Expr\Variable
            && $expression->var->var->name === 'this'
            && $expression->var->name instanceof Node\Identifier
        ) {
            $propertyName = $expression->var->name->toString();
            if (isset($constructorParameters[$propertyName])) {
                return [
                    'type' => 'closure_result',
                    'parameter' => $propertyName,
                ];
            }
        }

        if ($expression instanceof New_
            && $expression->class instanceof Node\Name
            && in_array($expression->class->toString(), ['RecursiveArrayIterator', 'RecursiveIteratorIterator'], true)
            && isset($expression->args[0])
            && $expression->args[0] instanceof Arg
            && $expression->args[0]->value instanceof Expr\Variable
            && is_string($expression->args[0]->value->name)
        ) {
            return $origins[$expression->args[0]->value->name] ?? null;
        }

        if ($expression instanceof Expr\Variable && is_string($expression->name)) {
            return $origins[$expression->name] ?? null;
        }

        return null;
    }

    private function extractClosureBodyExpression(Expr $expression): ?Expr
    {
        if ($expression instanceof Expr\ArrowFunction) {
            return $expression->expr;
        }

        if ($expression instanceof Closure && $expression->stmts !== null) {
            foreach ($expression->stmts as $statement) {
                if ($statement instanceof Return_ && $statement->expr instanceof Expr) {
                    return $statement->expr;
                }
            }
        }

        return null;
    }

    /**
     * @param array<int, Arg|Node\VariadicPlaceholder> $arguments
     * @param array<string, Expr> $substitutions
     * @return array<int, Arg|Node\VariadicPlaceholder>
     */
    private function substituteArguments(array $arguments, array $substitutions): array
    {
        $resolvedArguments = [];
        foreach ($arguments as $argument) {
            if (!$argument instanceof Arg) {
                $resolvedArguments[] = $argument;
                continue;
            }

            $resolvedArgument = clone $argument;
            $resolvedArgument->value = $this->expressionSubstitutor->substitute($argument->value, $substitutions);
            $resolvedArguments[] = $resolvedArgument;
        }

        return $resolvedArguments;
    }

    /**
     * @param array<int, Arg|Node\VariadicPlaceholder> $arguments
     */
    private function getArgumentValue(array $arguments, int $index): ?Expr
    {
        $argument = $arguments[$index] ?? null;

        return $argument instanceof Arg ? $argument->value : null;
    }

    /**
     * @param array<string, Expr> $substitutions
     */
    private function collectLocalAssignmentSubstitution(Node $statement, array &$substitutions): void
    {
        $expression = null;
        if ($statement instanceof Node\Stmt\Expression) {
            $expression = $statement->expr;
        } elseif ($statement instanceof Expr) {
            $expression = $statement;
        }

        if (!$expression instanceof Assign || !$expression->var instanceof Expr\Variable || !is_string($expression->var->name)) {
            return;
        }

        $substitutions[$expression->var->name] = $this->expressionSubstitutor->substitute($expression->expr, $substitutions);
    }

    /**
     * @param MethodTranslationGuard[] $guards
     * @param array<string, Expr> $substitutions
     * @return MethodTranslationCandidate[]
     */
    private function collectCandidatesFromTernary(Expr\Ternary $node, string $className, array $guards, array $substitutions): array
    {
        $candidates = [];
        $truthyBranch = $node->if ?? $node->cond;
        $condition = $this->expressionSubstitutor->substitute($node->cond, $substitutions);

        $candidates = array_merge(
            $candidates,
            $this->collectCandidatesFromNode(
                $this->expressionSubstitutor->substitute($truthyBranch, $substitutions),
                $className,
                array_merge($guards, [new MethodTranslationGuard($condition, true)]),
                $substitutions
            )
        );

        $candidates = array_merge(
            $candidates,
            $this->collectCandidatesFromNode(
                $this->expressionSubstitutor->substitute($node->else, $substitutions),
                $className,
                array_merge($guards, [new MethodTranslationGuard($condition, false)]),
                $substitutions
            )
        );

        return $candidates;
    }

    /**
     * @param MethodTranslationGuard[] $guards
     * @param array<string, Expr> $substitutions
     * @return MethodTranslationCandidate[]
     */
    private function collectCandidatesFromIf(If_ $node, string $className, array $guards, array $substitutions): array
    {
        $candidates = [];
        $consumedConditions = [];
        $condition = $this->expressionSubstitutor->substitute($node->cond, $substitutions);

        $truthyGuards = array_merge($guards, [new MethodTranslationGuard($condition, true)]);
        $candidates = array_merge($candidates, $this->collectCandidatesFromStatements($node->stmts, $className, $truthyGuards, $substitutions));
        $consumedConditions[] = $condition;

        foreach ($node->elseifs as $elseif) {
            $elseifGuards = $guards;
            foreach ($consumedConditions as $consumedCondition) {
                $elseifGuards[] = new MethodTranslationGuard($consumedCondition, false);
            }
            $elseifCondition = $this->expressionSubstitutor->substitute($elseif->cond, $substitutions);
            $elseifGuards[] = new MethodTranslationGuard($elseifCondition, true);
            $candidates = array_merge($candidates, $this->collectCandidatesFromStatements($elseif->stmts, $className, $elseifGuards, $substitutions));
            $consumedConditions[] = $elseifCondition;
        }

        if ($node->else !== null) {
            $elseGuards = $guards;
            foreach ($consumedConditions as $consumedCondition) {
                $elseGuards[] = new MethodTranslationGuard($consumedCondition, false);
            }
            $candidates = array_merge($candidates, $this->collectCandidatesFromStatements($node->else->stmts, $className, $elseGuards, $substitutions));
        }

        return $candidates;
    }

    /**
     * @param MethodTranslationGuard[] $guards
     * @param array<string, Expr> $substitutions
     * @return MethodTranslationGuard[]
     */
    private function substituteGuards(array $guards, array $substitutions): array
    {
        $resolvedGuards = [];
        foreach ($guards as $guard) {
            $resolvedGuards[] = new MethodTranslationGuard(
                $this->expressionSubstitutor->substitute($guard->expression, $substitutions),
                $guard->expectedValue
            );
        }

        return $resolvedGuards;
    }

    private function resolveCalleeClassFromMethodCall(MethodCall $node, string $currentClassName): ?string
    {
        if ($node->var instanceof Expr\Variable && $node->var->name === 'this') {
            return $currentClassName;
        }

        if ($node->var instanceof Expr\New_ && $node->var->class instanceof Node\Name) {
            return $node->var->class->toString();
        }

        return null;
    }

    private function resolveCalleeClassFromStaticCall(StaticCall $node, string $currentClassName): ?string
    {
        if ($node->class instanceof Node\Name) {
            $className = $node->class->toString();
            if (in_array(strtolower($className), ['self', 'static', 'parent'], true)) {
                return $currentClassName;
            }

            return $className;
        }

        return null;
    }
}

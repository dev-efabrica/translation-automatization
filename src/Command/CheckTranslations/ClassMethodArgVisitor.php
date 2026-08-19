<?php

namespace Efabrica\TranslationsAutomatization\Command\CheckFormKeys;

use Efabrica\TranslationsAutomatization\Command\CheckTranslations\BooleanExpressionEvaluator;
use Efabrica\TranslationsAutomatization\Command\CheckTranslations\ExpressionEvaluationResult;
use Efabrica\TranslationsAutomatization\Command\CheckTranslations\MethodSummaryResolver;
use Efabrica\TranslationsAutomatization\Command\CheckTranslations\MethodTranslationCandidate;
use Efabrica\TranslationsAutomatization\Command\CheckTranslations\ProjectClassIndex;
use Efabrica\TranslationsAutomatization\Command\CheckTranslations\TranslationKeyExpressionResolver;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\ClosureUse;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\NodeVisitorAbstract;
use PhpParser\PrettyPrinter\Standard;

class ClassMethodArgVisitor extends NodeVisitorAbstract
{
    private $keys = [];

    private $filePath;

    private $className;

    /** @var array<int, array<string, ExpressionEvaluationResult>> */
    private array $variableScopes = [[]];

    /** @var array<int, array<string, string>> */
    private array $typeScopes = [[]];

    /** @var array<int, array<string, ExpressionEvaluationResult>> */
    private array $methodParameterScopes = [[]];

    /** @var array<int, array<string, Node\Expr>> */
    private array $expressionScopes = [[]];

    private MethodSummaryResolver $methodSummaryResolver;

    private TranslationKeyExpressionResolver $expressionResolver;

    private BooleanExpressionEvaluator $booleanExpressionEvaluator;

    private Standard $prettyPrinter;

    /** @var array<string, ExpressionEvaluationResult> */
    private array $classConstants = [];

    private ProjectClassIndex $classIndex;

    public function __construct(
        array &$keys,
        string $filePath,
        ProjectClassIndex $classIndex,
        ?TranslationKeyExpressionResolver $expressionResolver = null,
        ?MethodSummaryResolver $methodSummaryResolver = null
    ) {
        $this->keys = &$keys;
        $this->filePath = $filePath;
        $this->className = (string)pathinfo($filePath, PATHINFO_FILENAME);
        $this->classIndex = $classIndex;
        $this->expressionResolver = $expressionResolver ?? new TranslationKeyExpressionResolver();
        $this->methodSummaryResolver = $methodSummaryResolver ?? new MethodSummaryResolver($classIndex);
        $this->booleanExpressionEvaluator = new BooleanExpressionEvaluator();
        $this->prettyPrinter = new Standard();
    }

    public function enterNode(Node $node)
    {
        $this->enterScope($node);
        if ($node instanceof Class_ && $node->name !== null) {
            $this->className = $node->name->toString();
        }
        $this->collectClassConstants($node);
        $this->collectVariableAssignment($node);
        $this->collectForeachBindings($node);
        if ($node instanceof MethodCall) {
            $firstArg = $this->findArgumentBySelector($node->args, 0);
            if ($node->name instanceof Node\Identifier &&
                strtolower($node->name->toString()) === 'translate' &&
                $firstArg !== null
            ) {
                $candidate = new MethodTranslationCandidate($firstArg->value, $node->name->toString(), $this->extractParameterNames($node), [], $this->className);
                $this->extractKeyFromCandidate($candidate, $node->getStartLine(), $this->printExpression($firstArg->value));
                return;
            }

            $this->extractKeysFromInterproceduralSummary($node);
        }
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof FunctionLike) {
            array_pop($this->variableScopes);
            array_pop($this->typeScopes);
            array_pop($this->methodParameterScopes);
            array_pop($this->expressionScopes);
        }
    }

    /**
     * @param array<int, Node\Arg|Node\VariadicPlaceholder> $args
     */
    private function findArgumentBySelector(array $args, $argSelector): ?Arg
    {
        if (is_int($argSelector)) {
            $arg = $args[$argSelector] ?? null;

            return $arg instanceof Arg ? $arg : null;
        }

        foreach ($args as $arg) {
            if (!$arg instanceof Arg) {
                continue;
            }

            if ($arg->name !== null && $arg->name->toString() === $argSelector) {
                return $arg;
            }
        }

        return null;
    }

    private function extractKeysFromInterproceduralSummary(MethodCall $node): void
    {
        if (!$node->name instanceof Node\Identifier) {
            return;
        }

        $className = $this->resolveMethodCallClass($node);
        if ($className === null) {
            return;
        }

        $candidates = $this->methodSummaryResolver->resolveCall($className, $node->name->toString(), $node->args, $this->className);
        foreach ($candidates as $candidate) {
            $this->extractKeyFromCandidate($candidate, $node->getStartLine(), $this->printExpression($candidate->expression));
        }
    }

    private function extractKeyFromCandidate(MethodTranslationCandidate $candidate, int $line, string $sourceExpression): void
    {
        if (!$this->isCandidateActive($candidate)) {
            return;
        }

        $resolver = $this->getExpressionResolver($candidate->declaringClassName ?? $this->className);
        $scope = $this->getCurrentScope();
        $result = $resolver->resolve($candidate->expression, $scope);

        $keyPattern = null;
        if (!$result->isResolved()) {
            $keyPattern = $resolver->derivePattern($candidate->expression, $scope, $this->getCurrentExpressionScope());
        }

        $this->addResolvedResult($result, $line, $candidate->call, $sourceExpression, $candidate->parameterNames, $keyPattern);
    }

    /**
     * @param string[]|null $args
     */
    private function addResolvedResult(ExpressionEvaluationResult $result, int $line, string $call, string $sourceExpression, ?array $args, ?string $keyPattern = null): void
    {
        if (!$result->isResolved()) {
            if (in_array('method_parameter', $result->getStrategies(), true)) {
                return;
            }

            $this->addKey($line, $call, [], $args, true, false, $sourceExpression, $result->getStrategies(), $result->getVariablesUsed(), $keyPattern);
            return;
        }

        foreach ($result->getValues() as $key) {
            if ($this->allowsEmptyTranslation($call, $key)) {
                continue;
            }

            $this->addKey($line, $call, [$key], $args, $result->isDynamic(), true, $sourceExpression, $result->getStrategies(), $result->getVariablesUsed());
        }
    }

    /**
     * @param string[]|null $args
     */
    private function addKey(int $line, string $call, array $resolvedKeys, ?array $args = null, bool $isDynamic = false, bool $isResolved = true, ?string $sourceExpression = null, array $resolutionStrategies = [], array $variablesUsed = [], ?string $keyPattern = null): void
    {
        $this->keys[] = [
            'file' => $this->filePath,
            'line' => $line,
            'call' => $call,
            'resolvedKeys' => $resolvedKeys,
            'args' => $args,
            'isDynamic' => $isDynamic,
            'isResolved' => $isResolved,
            'sourceExpression' => $sourceExpression,
            'resolutionStrategies' => $resolutionStrategies,
            'variablesUsed' => $variablesUsed,
            'keyPattern' => $keyPattern,
        ];
    }

    private function enterScope(Node $node): void
    {
        if ($node instanceof Closure) {
            $scope = [];
            $typeScope = [];
            $expressionScope = [];
            foreach ($node->uses as $use) {
                if ($use instanceof ClosureUse && is_string($use->var->name)) {
                    $currentScope = $this->getCurrentScope();
                    if (isset($currentScope[$use->var->name])) {
                        $scope[$use->var->name] = $currentScope[$use->var->name];
                    }
                    $currentTypeScope = $this->getCurrentTypeScope();
                    if (isset($currentTypeScope[$use->var->name])) {
                        $typeScope[$use->var->name] = $currentTypeScope[$use->var->name];
                    }
                    $currentExpressionScope = $this->getCurrentExpressionScope();
                    if (isset($currentExpressionScope[$use->var->name])) {
                        $expressionScope[$use->var->name] = $currentExpressionScope[$use->var->name];
                    }
                }
            }

            $this->variableScopes[] = $scope;
            $this->typeScopes[] = $typeScope;
            $this->methodParameterScopes[] = [];
            $this->expressionScopes[] = $expressionScope;
            return;
        }

        if ($node instanceof ClassMethod || $node instanceof Function_) {
            $valueScope = [];
            $typeScope = [];
            $parameterScope = [];
            foreach ($node->params as $parameter) {
                if (!$parameter->var instanceof Node\Expr\Variable || !is_string($parameter->var->name)) {
                    continue;
                }

                $parameterName = $parameter->var->name;
                $parameterScope[$parameterName] = ExpressionEvaluationResult::unresolved(true, ['method_parameter'], ['$' . $parameterName]);
                if ($parameter->type instanceof Node\Name) {
                    $typeScope[$parameterName] = $parameter->type->toString();
                }
            }

            $this->variableScopes[] = $valueScope;
            $this->typeScopes[] = $typeScope;
            $this->methodParameterScopes[] = $parameterScope;
            $this->expressionScopes[] = [];
            return;
        }

        if ($node instanceof FunctionLike) {
            $this->variableScopes[] = $this->getCurrentScope();
            $this->typeScopes[] = $this->getCurrentTypeScope();
            $this->methodParameterScopes[] = $this->getCurrentMethodParameterScope();
            $this->expressionScopes[] = $this->getCurrentExpressionScope();
        }
    }

    private function collectVariableAssignment(Node $node): void
    {
        if (!$node instanceof Assign || !$node->var instanceof Node\Expr\Variable || !is_string($node->var->name)) {
            return;
        }

        $this->variableScopes[array_key_last($this->variableScopes)][$node->var->name] = $this->getExpressionResolver()->resolve(
            $node->expr,
            $this->getCurrentScope()
        );
        $this->expressionScopes[array_key_last($this->expressionScopes)][$node->var->name] = $node->expr;

        $resolvedType = $this->resolveAssignedType($node->expr);
        if ($resolvedType !== null) {
            $this->typeScopes[array_key_last($this->typeScopes)][$node->var->name] = $resolvedType;
        }
    }

    private function collectForeachBindings(Node $node): void
    {
        if (!$node instanceof Foreach_) {
            return;
        }

        $iteratedResult = $this->getExpressionResolver()->resolve($node->expr, $this->getCurrentScope());
        $itemResult = $this->mergeIteratedValues($iteratedResult);
        if ($itemResult !== null && $node->valueVar instanceof Node\Expr\Variable && is_string($node->valueVar->name)) {
            $this->variableScopes[array_key_last($this->variableScopes)][$node->valueVar->name] = $itemResult;
        }

        if ($node->keyVar instanceof Node\Expr\Variable && is_string($node->keyVar->name)) {
            $this->variableScopes[array_key_last($this->variableScopes)][$node->keyVar->name] = ExpressionEvaluationResult::unresolved(true, ['foreach_key'], ['$' . $node->keyVar->name]);
        }
    }

    private function collectClassConstants(Node $node): void
    {
        if (!$node instanceof ClassConst) {
            return;
        }

        foreach ($node->consts as $const) {
            $this->classConstants[$const->name->toString()] = $this->getExpressionResolver($this->className)->resolve(
                $const->value,
                $this->getCurrentScope()
            );
        }
    }

    /**
     * @return array<string, ExpressionEvaluationResult>
     */
    private function getCurrentScope(): array
    {
        return array_merge(
            $this->methodParameterScopes[array_key_last($this->methodParameterScopes)] ?? [],
            $this->variableScopes[array_key_last($this->variableScopes)]
        );
    }

    /**
     * @return array<string, string>
     */
    private function getCurrentTypeScope(): array
    {
        return $this->typeScopes[array_key_last($this->typeScopes)];
    }

    /**
     * @return array<string, ExpressionEvaluationResult>
     */
    private function getCurrentMethodParameterScope(): array
    {
        return $this->methodParameterScopes[array_key_last($this->methodParameterScopes)];
    }

    /**
     * @return array<string, Node\Expr>
     */
    private function getCurrentExpressionScope(): array
    {
        return $this->expressionScopes[array_key_last($this->expressionScopes)] ?? [];
    }

    private function printExpression(Node $expression): string
    {
        return $this->prettyPrinter->prettyPrintExpr($expression);
    }

    private function getExpressionResolver(?string $className = null): TranslationKeyExpressionResolver
    {
        return $this->expressionResolver
            ->withClassConstants($this->classConstants)
            ->withClassContext($this->classIndex, $className ?? $this->className);
    }

    private function resolveAssignedType(Node\Expr $expression): ?string
    {
        if ($expression instanceof New_ && $expression->class instanceof Node\Name) {
            return $expression->class->toString();
        }

        if ($expression instanceof MethodCall && $expression->name instanceof Node\Identifier) {
            $className = $this->resolveMethodCallClass($expression);
            if ($className === null) {
                return null;
            }

            $method = $this->classIndex->findMethod($className, $expression->name->toString());

            return $method !== null ? $method->returnType : null;
        }

        if ($expression instanceof Node\Expr\StaticCall && $expression->name instanceof Node\Identifier) {
            $className = $this->resolveStaticCallClass($expression);
            if ($className === null) {
                return null;
            }

            $method = $this->classIndex->findMethod($className, $expression->name->toString());

            return $method !== null ? $method->returnType : null;
        }

        if ($expression instanceof Node\Expr\Variable && is_string($expression->name)) {
            return $this->getCurrentTypeScope()[$expression->name] ?? null;
        }

        return null;
    }

    private function resolveMethodCallClass(MethodCall $node): ?string
    {
        if ($node->var instanceof Node\Expr\Variable && $node->var->name === 'this') {
            return $this->className;
        }

        if ($node->var instanceof Node\Expr\Variable && is_string($node->var->name)) {
            return $this->getCurrentTypeScope()[$node->var->name] ?? null;
        }

        if ($node->var instanceof New_ && $node->var->class instanceof Node\Name) {
            return $node->var->class->toString();
        }

        return null;
    }

    private function resolveStaticCallClass(Node\Expr\StaticCall $node): ?string
    {
        if (!$node->class instanceof Node\Name) {
            return null;
        }

        $className = $node->class->toString();
        if (in_array(strtolower($className), ['self', 'static', 'parent'], true)) {
            return $this->className;
        }

        return $className;
    }

    /**
     * @return string[]|null null = statically unknown, [] = no parameters
     */
    private function extractParameterNames(MethodCall $node): ?array
    {
        $paramsArg = $this->findArgumentBySelector($node->args, 1);
        if ($paramsArg === null) {
            return [];
        }

        $value = $paramsArg->value;
        if ($value instanceof Node\Scalar\LNumber) {
            $paramsArg = $this->findArgumentBySelector($node->args, 2);
            if ($paramsArg === null) {
                return ['count'];
            }
            $value = $paramsArg->value;
        }

        if (!$value instanceof Node\Expr\Array_) {
            return null;
        }

        $names = [];
        foreach ($value->items as $item) {
            if ($item === null || $item->unpack || !$item->key instanceof String_) {
                return null;
            }
            $names[] = $item->key->value;
        }

        return $names;
    }

    private function allowsEmptyTranslation(string $call, string $key): bool
    {
        if ($key !== '' && $key !== '--') {
            return false;
        }

        return in_array($call, ['addSelect', 'addTextArea', 'dropdown'], true);
    }

    private function mergeIteratedValues(ExpressionEvaluationResult $iteratedResult): ?ExpressionEvaluationResult
    {
        $items = array_values($iteratedResult->getArrayItems());
        if ($items === []) {
            return null;
        }

        $values = [];
        $strategies = [];
        $variablesUsed = [];
        $objectProperties = null;
        foreach ($items as $item) {
            $values = array_merge($values, $item->getValues());
            $strategies = array_merge($strategies, $item->getStrategies());
            $variablesUsed = array_merge($variablesUsed, $item->getVariablesUsed());

            $currentProperties = $item->getObjectProperties();
            if ($objectProperties === null) {
                $objectProperties = $currentProperties;
                continue;
            }

            $objectProperties = $this->mergeObjectProperties($objectProperties, $currentProperties);
        }

        if ($objectProperties !== [] && $objectProperties !== null) {
            return ExpressionEvaluationResult::resolvedObject(
                $objectProperties,
                true,
                array_merge($strategies, ['foreach_value']),
                array_values(array_unique($variablesUsed))
            );
        }

        if ($values !== []) {
            return ExpressionEvaluationResult::resolved(
                $values,
                true,
                array_merge($strategies, ['foreach_value']),
                array_values(array_unique($variablesUsed))
            );
        }

        return null;
    }

    /**
     * @param array<string, ExpressionEvaluationResult> $left
     * @param array<string, ExpressionEvaluationResult> $right
     * @return array<string, ExpressionEvaluationResult>
     */
    private function mergeObjectProperties(array $left, array $right): array
    {
        $merged = [];
        foreach (array_intersect(array_keys($left), array_keys($right)) as $propertyName) {
            $leftResult = $left[$propertyName];
            $rightResult = $right[$propertyName];

            $values = array_values(array_unique(array_merge($leftResult->getValues(), $rightResult->getValues())));
            $strategies = array_values(array_unique(array_merge($leftResult->getStrategies(), $rightResult->getStrategies())));
            $variablesUsed = array_values(array_unique(array_merge($leftResult->getVariablesUsed(), $rightResult->getVariablesUsed())));

            $merged[$propertyName] = ExpressionEvaluationResult::resolved($values, true, $strategies, $variablesUsed);
        }

        return $merged;
    }

    private function isCandidateActive(MethodTranslationCandidate $candidate): bool
    {
        foreach ($candidate->guards as $guard) {
            $evaluation = $this->booleanExpressionEvaluator->evaluate($guard->expression);
            if ($evaluation === null) {
                continue;
            }

            if ($evaluation !== $guard->expectedValue) {
                return false;
            }
        }

        return true;
    }
}

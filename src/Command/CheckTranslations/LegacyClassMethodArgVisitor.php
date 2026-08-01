<?php

namespace Efabrica\TranslationsAutomatization\Command\CheckTranslations;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeVisitorAbstract;

class LegacyClassMethodArgVisitor extends NodeVisitorAbstract
{
    private array $keys;

    private string $filePath;

    private string $className;

    /** @var array<int, string> */
    private array $classArgposClassesMap = [];

    private array $config;

    /** @var array<int, array<string, string>> */
    private array $typeScopes = [[]];

    public function __construct(array &$keys, string $filePath, array $config)
    {
        $this->keys = &$keys;
        $this->filePath = $filePath;
        $this->className = (string) pathinfo($filePath, PATHINFO_FILENAME);
        $this->config = $config;
    }

    public function enterNode(Node $node)
    {
        $this->prepareUseClasses($node);
        $this->enterTypeScope($node);
        if ($node instanceof New_ && isset($node->class) && in_array($node->class->name ?? null, $this->classArgposClassesMap, true)) {
            $className = $node->class->name;
            $argIndex = array_search($className, $this->classArgposClassesMap, true);
            $args = $node->args;
            if (isset($args[$argIndex]) && $args[$argIndex]->value instanceof String_) {
                $key = $args[$argIndex]->value->value;
                $this->addKey($args[$argIndex]->getStartLine(), $className, $key);
            }
        }

        if ($node instanceof MethodCall && $node->name instanceof Identifier) {
            $this->extractFromMethodCall($node, $node->name->name);
        }
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof FunctionLike) {
            array_pop($this->typeScopes);
        }
    }

    private function extractFromMethodCall(MethodCall $node, string $methodName): void
    {
        $receiverArgposMethods = $this->config['RECEIVER_ARGPOS_METHODS'] ?? [];
        $receiverType = $this->resolveReceiverType($node);

        if ($receiverType !== null && isset($receiverArgposMethods[$receiverType])) {
            $matched = false;
            foreach ($receiverArgposMethods[$receiverType] as $argIndex => $methods) {
                if (in_array($methodName, $methods, true)) {
                    $this->extractKeyFromArgument($node, (int) $argIndex, $receiverType);
                    $matched = true;
                }
            }
            if ($matched) {
                return;
            }
        }

        $receiverPositions = $this->getReceiverPositions($methodName, $receiverArgposMethods);
        if ($receiverPositions !== [] && $receiverType !== null) {
            // known receiver type uses a different overload of this method
            return;
        }

        foreach ($this->config['CLASS_ARGPOS_METHODS'] ?? [] as $classNamePart => $argposMethods) {
            if ($classNamePart !== 'ALL' && (strpos($this->className, $classNamePart) === false || substr($this->className, -strlen($classNamePart)) !== $classNamePart)) {
                continue;
            }
            foreach ($argposMethods as $argIndex => $methods) {
                if (in_array($methodName, $methods, true)) {
                    if ($receiverPositions !== []) {
                        $candidatePositions = array_values(array_unique(array_merge($receiverPositions, [(int) $argIndex])));
                        $this->extractCandidateKeys($node, $methodName, $candidatePositions);
                        return;
                    }
                    $this->extractKeyFromArgument($node, (int) $argIndex, $classNamePart);
                }
            }
        }
    }

    private function enterTypeScope(Node $node): void
    {
        if (!$node instanceof FunctionLike) {
            return;
        }

        $scope = [];
        if ($node instanceof ArrowFunction) {
            $scope = $this->getCurrentTypeScope();
        } elseif ($node instanceof Closure) {
            $currentScope = $this->getCurrentTypeScope();
            foreach ($node->uses as $use) {
                if ($use->var instanceof Node\Expr\Variable && is_string($use->var->name) && isset($currentScope[$use->var->name])) {
                    $scope[$use->var->name] = $currentScope[$use->var->name];
                }
            }
        }

        foreach ($node->getParams() as $param) {
            if (!$param->var instanceof Node\Expr\Variable || !is_string($param->var->name)) {
                continue;
            }
            $type = $this->resolveShortTypeName($param->type);
            if ($type !== null) {
                $scope[$param->var->name] = $type;
            }
        }

        $this->typeScopes[] = $scope;
    }

    /**
     * @return array<string, string>
     */
    private function getCurrentTypeScope(): array
    {
        return $this->typeScopes[array_key_last($this->typeScopes)] ?? [];
    }

    private function resolveShortTypeName(?Node $type): ?string
    {
        if ($type instanceof Node\NullableType) {
            $type = $type->type;
        }

        return $type instanceof Node\Name ? $type->getLast() : null;
    }

    private function resolveReceiverType(MethodCall $node): ?string
    {
        if ($node->var instanceof Node\Expr\Variable && is_string($node->var->name)) {
            return $this->getCurrentTypeScope()[$node->var->name] ?? null;
        }

        return null;
    }

    /**
     * @return int[]
     */
    private function getReceiverPositions(string $methodName, array $receiverArgposMethods): array
    {
        $positions = [];
        foreach ($receiverArgposMethods as $argposMethods) {
            foreach ($argposMethods as $argIndex => $methods) {
                if (in_array($methodName, $methods, true)) {
                    $positions[] = (int) $argIndex;
                }
            }
        }

        return array_values(array_unique($positions));
    }

    /**
     * @param int[] $argIndexes
     */
    private function extractCandidateKeys(MethodCall $node, string $methodName, array $argIndexes): void
    {
        $args = $node->args;
        $candidates = [];
        foreach ($argIndexes as $argIndex) {
            if (isset($args[$argIndex]) && $args[$argIndex]->value instanceof String_) {
                $candidates[] = $args[$argIndex]->value->value;
            }
        }

        $candidates = array_values(array_unique($candidates));
        if ($candidates === []) {
            return;
        }

        if (count($candidates) === 1) {
            $this->addKey($node->getStartLine(), $methodName, $candidates[0]);
            return;
        }

        $this->keys[] = [
            'file' => $this->filePath,
            'line' => $node->getStartLine(),
            'call' => $methodName,
            'key' => null,
            'arg' => null,
            'keyCandidates' => $candidates,
        ];
    }

    private function prepareUseClasses(Node $node): void
    {
        if ($node instanceof Use_) {
            foreach ($node->uses as $use) {
                $useName = $use->name->name;
                foreach ($this->config['ARGPOS_CLASSES'] ?? [] as $argIndex => $classes) {
                    if (in_array($useName, $classes, true)) {
                        $shortName = basename(str_replace('\\', '/', $useName));
                        $this->classArgposClassesMap[(int) $argIndex] = $shortName;
                    }
                }
            }
        }
    }

    private function extractKeyFromArgument(MethodCall $node, int $argIndex, string $classNamePart): void
    {
        $args = $node->args;
        if (isset($args[$argIndex]) && $args[$argIndex]->value instanceof Closure) {
            $method = $node->name->name;
            $closure = $args[$argIndex]->value;
            if ($closure->stmts !== null) {
                $return = reset($closure->stmts);
                if ($return instanceof Return_ && $return->expr instanceof Array_ && $return->expr->items !== null) {
                    $items = $return->expr->items;
                    foreach ($items as $item) {
                        if ($item->value instanceof String_) {
                            $key = $item->value->value;
                            $this->addKey($item->value->getAttribute('startLine'), $method, $key, null);
                        }
                    }
                }
            }
        }

        if (isset($args[$argIndex]) && $args[$argIndex]->value instanceof String_) {
            $method = $node->name->name;
            $arg = null;
            if ($method === 'translate' && isset($args[$argIndex + 1]) && $args[$argIndex + 1]->value instanceof Node\Expr\Array_) {
                $arg = $args[$argIndex + 1]->value->items[0]->key->value;
            }

            $key = $args[$argIndex]->value->value;
            $allowEmptyTranslation = $this->config['ALLOW_EMPTY_TRANSLATION'] ?? [];
            if (array_key_exists($classNamePart, $allowEmptyTranslation) &&
                array_key_exists($argIndex, $allowEmptyTranslation[$classNamePart]) &&
                ($key === '' || $key === '--') &&
                in_array($method, $allowEmptyTranslation[$classNamePart][$argIndex], true)
            ) {
                return;
            }
            $this->addKey($args[$argIndex]->getStartLine(), $method, $key, $arg);
        }
    }

    private function addKey(int $line, string $call, string $key, ?string $arg = null): void
    {
        $this->keys[] = [
            'file' => $this->filePath,
            'line' => $line,
            'call' => $call,
            'key' => $key,
            'arg' => $arg,
        ];
    }
}

<?php

namespace Efabrica\TranslationsAutomatization\Command\CheckFormKeys;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeVisitorAbstract;

class ClassMethodArgVisitor extends NodeVisitorAbstract
{
    private $keys = [];

    private $filePath;

    private $className;

    private $classArgposClassesMap = [];

    private $config;

    public function __construct(array &$keys, string $filePath, array $config)
    {
        $this->keys = &$keys;
        $this->filePath = $filePath;
        $this->className = (string)pathinfo($filePath, PATHINFO_FILENAME);
        $this->config = $config;
    }

    public function enterNode(Node $node)
    {
        $this->prepareUseClasses($node);
        // find new Class(arg1, arg2, arg3)
        if ($node instanceof New_ && isset($node->class) && in_array($node->class->name ?? null, $this->classArgposClassesMap, true)) {
            $className = $node->class->name;
            $argIndex = array_search($className, $this->classArgposClassesMap);
            $args = $node->args;
            if (isset($args[$argIndex]) && $args[$argIndex]->value instanceof String_) {
                $key = $args[$argIndex]->value->value;
                $this->addKey($args[$argIndex]->getStartLine(), $className, $key);
            }
        }
        // find in Class ->method(arg1, arg2, arg3)
        if ($node instanceof MethodCall &&
            $node->name instanceof Identifier) {
            $methodName = $node->name->name;
            foreach ($this->config['CLASS_ARGPOS_METHODS'] ?? [] as $classNamePart => $argposMethods) {
                // ALL classes OR Classes end with classNamePart
                if ($classNamePart !== 'ALL' && (strpos($this->className, $classNamePart) === false || substr($this->className, -strlen($classNamePart)) !== $classNamePart)) {
                    continue;
                }
                foreach ($argposMethods as $argIndex => $methods) {
                    if (in_array($methodName, $methods, true)) {
                        $this->extractKeyFromArgument($node, $argIndex, $classNamePart);
                    }
                }
            }
        }
    }

    private function prepareUseClasses(Node $node)
    {
        if ($node instanceof Use_) {
            foreach ($node->uses as $use) {
                $useName = $use->name->name;
                foreach ($this->config['ARGPOS_CLASSES'] ?? [] as $argIndex => $classes) {
                    if (in_array($useName, $classes)) {
                        $shortName = basename(str_replace('\\', '/', $useName));
                        $this->classArgposClassesMap[$argIndex] = $shortName;
                    }
                }
            }
        }
    }

    private function extractKeyFromArgument(MethodCall $node, int $argIndex, string $classNamePart): void
    {
        $args = $node->args;
        if (isset($args[$argIndex]) && $args[$argIndex]->value instanceof String_) {
            $method = $node->name->name;
            $arg = null;
            if ($method === 'translate' && isset($args[$argIndex + 1]) && $args[$argIndex + 1]->value instanceof Node\Expr\Array_) {
                $arg = $args[$argIndex + 1]->value->items[0]->key->value;
            }

            $key = $args[$argIndex]->value->value;
            $allowEmptyTranslation = $this->config['ALLOW_EMPTY_TRANSLATION'] ?? [];
            if (
                array_key_exists($classNamePart, $allowEmptyTranslation) &&
                array_key_exists($argIndex, $allowEmptyTranslation[$classNamePart]) &&
                ($key === '' || $key === '--') &&
                (in_array($method, $allowEmptyTranslation[$classNamePart][$argIndex], true))
            ) {
                return;
            }
            $this->addKey($args[$argIndex]->getStartLine(), $method, $key, $arg);
        }
    }

    private function addKey(int $line, string $call, string $key, string $arg = null): void
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

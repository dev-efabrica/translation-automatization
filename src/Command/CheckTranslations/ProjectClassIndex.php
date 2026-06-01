<?php

namespace Efabrica\TranslationsAutomatization\Command\CheckTranslations;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;

class ProjectClassIndex
{
    /** @var array<string, ProjectClassDefinition> */
    private array $classes = [];

    private ?MethodBodyCache $bodyCache = null;

    public function setBodyCache(?MethodBodyCache $bodyCache): void
    {
        $this->bodyCache = $bodyCache;
    }

    public function addClass(ProjectClassDefinition $classDefinition): void
    {
        $this->classes[$classDefinition->className] = $classDefinition;
    }

    public function findClass(string $className): ?ProjectClassDefinition
    {
        if (isset($this->classes[$className])) {
            return $this->classes[$className];
        }

        $shortClassName = $this->normalizeClassName($className);

        return $this->classes[$shortClassName] ?? null;
    }

    public function findMethod(string $className, string $methodName): ?ProjectMethodDefinition
    {
        $class = $this->findClass($className);
        while ($class !== null) {
            if (isset($class->methods[$methodName])) {
                return $class->methods[$methodName];
            }

            $class = $class->parentClassName !== null ? $this->findClass($class->parentClassName) : null;
        }

        return null;
    }

    /**
     * @return array<string, ExpressionEvaluationResult>
     */
    public function findClassConstants(string $className): array
    {
        $class = $this->findClass($className);

        return $class !== null ? $class->constantValues : [];
    }

    public function findParentClassName(string $className): ?string
    {
        $class = $this->findClass($className);

        return $class !== null ? $class->parentClassName : null;
    }

    public function findConstantValue(string $className, string $constantName): ?ExpressionEvaluationResult
    {
        $class = $this->findClass($className);
        while ($class !== null) {
            if (isset($class->constantValues[$constantName])) {
                return $class->constantValues[$constantName];
            }

            $class = $class->parentClassName !== null ? $this->findClass($class->parentClassName) : null;
        }

        return null;
    }

    /**
     * @param Node[] $ast
     */
    public static function fromAst(array $ast, TranslationKeyExpressionResolver $resolver): self
    {
        $index = new self();
        $index->collectFromAst($ast);
        $index->resolveAllConstants($resolver);

        return $index;
    }

    /**
     * Register classes from a single file's AST. Designed to be called per-file so the caller
     * can discard each AST before parsing the next one (keeps peak memory bounded on big projects).
     *
     * The optional $file is stored on each ProjectMethodDefinition so the body can be lazily
     * re-parsed later via MethodBodyCache instead of being pinned in memory.
     *
     * @param Node[] $ast
     */
    public function collectFromAst(array $ast, ?string $file = null): void
    {
        foreach ($ast as $node) {
            $this->collectClasses($node, $file);
        }
    }

    public function resolveAllConstants(TranslationKeyExpressionResolver $resolver): void
    {
        foreach ($this->classes as $classDefinition) {
            $this->resolveClassConstants($classDefinition->className, $resolver, []);
        }
    }

    private function resolveClassConstants(string $className, TranslationKeyExpressionResolver $resolver, array $resolving): array
    {
        $classDefinition = $this->findClass($className);
        if ($classDefinition === null) {
            return [];
        }

        if ($classDefinition->constantValues !== []) {
            return $classDefinition->constantValues;
        }

        if (isset($resolving[$classDefinition->className])) {
            return [];
        }

        $resolving[$classDefinition->className] = true;
        $constants = $classDefinition->parentClassName !== null
            ? $this->resolveClassConstants($classDefinition->parentClassName, $resolver, $resolving)
            : [];

        foreach ($classDefinition->constantExpressions as $constantName => $expression) {
            $constants[$constantName] = $resolver
                ->withClassConstants($constants)
                ->withClassContext($this, $classDefinition->className)
                ->resolve($expression);
        }

        $classDefinition->constantValues = $constants;
        $classDefinition->constantExpressions = [];

        return $constants;
    }

    private function normalizeClassName(string $className): string
    {
        $parts = explode('\\', $className);

        return end($parts) ?: $className;
    }

    private function collectClasses(Node $node, ?string $file): void
    {
        if ($node instanceof Class_) {
            $className = $node->name !== null ? $node->name->toString() : null;
            if ($className !== null) {
                $parentClassName = $node->extends !== null ? $node->extends->toString() : null;
                $classDefinition = new ProjectClassDefinition($className, $parentClassName);
                foreach ($node->stmts as $statement) {
                    if ($statement instanceof Node\Stmt\ClassConst) {
                        foreach ($statement->consts as $const) {
                            $classDefinition->constantExpressions[$const->name->toString()] = $const->value;
                        }
                    }

                    if ($statement instanceof ClassMethod) {
                        $parameterNames = [];
                        $parameterTypes = [];
                        foreach ($statement->params as $parameter) {
                            if (!$parameter->var instanceof Node\Expr\Variable || !is_string($parameter->var->name)) {
                                continue;
                            }

                            $parameterNames[] = $parameter->var->name;
                            if ($parameter->type instanceof Node\Name) {
                                $parameterTypes[$parameter->var->name] = $parameter->type->toString();
                            }
                        }

                        // Body of the method is NOT stored here — it stays only inside the AST
                        // that the caller is about to unset. MethodBodyCache re-parses on demand.
                        $classDefinition->methods[$statement->name->toString()] = new ProjectMethodDefinition(
                            $className,
                            $statement->name->toString(),
                            $parameterNames,
                            $parameterTypes,
                            $file,
                            $this->bodyCache,
                            $statement->returnType instanceof Node\Name ? $statement->returnType->toString() : null
                        );
                    }
                }

                $this->addClass($classDefinition);
            }
        }

        foreach ($node->getSubNodeNames() as $subNodeName) {
            $child = $node->$subNodeName;
            if ($child instanceof Node) {
                $this->collectClasses($child, $file);
                continue;
            }

            if (is_array($child)) {
                foreach ($child as $item) {
                    if ($item instanceof Node) {
                        $this->collectClasses($item, $file);
                    }
                }
            }
        }
    }
}

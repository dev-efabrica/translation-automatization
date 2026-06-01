<?php

namespace Efabrica\TranslationsAutomatization\Command\CheckTranslations;

use PhpParser\Error;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Parser;

/**
 * On-demand loader for method bodies. ProjectMethodDefinition stores only the file path and
 * signature; the actual Stmt[] for the method is parsed lazily when MethodSummaryResolver
 * needs it. A small LRU cache amortises repeated lookups inside the same file.
 */
class MethodBodyCache
{
    private const MAX_CACHED_FILES = 1;

    private Parser $parser;

    /** @var array<string, Node[]|null> file path => parsed AST (null = parse failed, cached so we don't retry) */
    private array $astCache = [];

    public function __construct(Parser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * @return Node\Stmt[]
     */
    public function loadMethodStatements(string $file, string $className, string $methodName): array
    {
        $ast = $this->getAst($file);
        if ($ast === null) {
            return [];
        }

        $shortClassName = $this->shortClassName($className);
        foreach ($ast as $node) {
            $stmts = $this->findMethodInNode($node, $shortClassName, $methodName);
            if ($stmts !== null) {
                return $stmts;
            }
        }

        return [];
    }

    /**
     * @return Node[]|null
     */
    private function getAst(string $file): ?array
    {
        if (array_key_exists($file, $this->astCache)) {
            $cached = $this->astCache[$file];
            unset($this->astCache[$file]);
            $this->astCache[$file] = $cached;
            return $cached;
        }

        if (!is_file($file)) {
            $this->astCache[$file] = null;
            $this->trim();
            return null;
        }

        try {
            $ast = $this->parser->parse((string) file_get_contents($file));
        } catch (Error $e) {
            $ast = null;
        }

        $this->astCache[$file] = $ast;
        $this->trim();

        return $ast;
    }

    private function trim(): void
    {
        while (count($this->astCache) > self::MAX_CACHED_FILES) {
            array_shift($this->astCache);
        }
    }

    /**
     * @return Node\Stmt[]|null null means "not found in this subtree" (continue searching siblings)
     */
    private function findMethodInNode(Node $node, string $shortClassName, string $methodName): ?array
    {
        if ($node instanceof Class_ && $node->name !== null && $node->name->toString() === $shortClassName) {
            foreach ($node->stmts as $stmt) {
                if ($stmt instanceof ClassMethod && $stmt->name->toString() === $methodName) {
                    return $stmt->stmts ?? [];
                }
            }
            return null;
        }

        foreach ($node->getSubNodeNames() as $subNodeName) {
            $child = $node->$subNodeName;
            if ($child instanceof Node) {
                $result = $this->findMethodInNode($child, $shortClassName, $methodName);
                if ($result !== null) {
                    return $result;
                }
                continue;
            }
            if (is_array($child)) {
                foreach ($child as $item) {
                    if ($item instanceof Node) {
                        $result = $this->findMethodInNode($item, $shortClassName, $methodName);
                        if ($result !== null) {
                            return $result;
                        }
                    }
                }
            }
        }

        return null;
    }

    private function shortClassName(string $className): string
    {
        $parts = explode('\\', $className);
        return end($parts) ?: $className;
    }
}

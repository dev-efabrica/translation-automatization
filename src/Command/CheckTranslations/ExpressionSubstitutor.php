<?php

namespace Efabrica\TranslationsAutomatization\Command\CheckTranslations;

use PhpParser\Node;
use PhpParser\Node\Expr;

class ExpressionSubstitutor
{
    /**
     * @param array<string, Expr> $substitutions
     */
    public function substitute(Expr $expression, array $substitutions): Expr
    {
        // Candidates and guards never mutate expressions, so when there is nothing to
        // substitute the original node can be shared instead of deep-cloned. This keeps
        // interprocedural summary resolution from allocating clones for every call site.
        if ($substitutions === [] || !$this->containsSubstitutableVariable($expression, $substitutions)) {
            return $expression;
        }

        $substituted = $this->substituteNode($expression, $substitutions);

        return $substituted instanceof Expr ? $substituted : clone $expression;
    }

    public function containsVariable(Node $node, string $variableName): bool
    {
        if ($node instanceof Expr\Variable && $node->name === $variableName) {
            return true;
        }

        foreach ($node->getSubNodeNames() as $childName) {
            $child = $node->$childName;
            if ($child instanceof Node) {
                if ($this->containsVariable($child, $variableName)) {
                    return true;
                }
                continue;
            }

            if (!is_array($child)) {
                continue;
            }

            foreach ($child as $item) {
                if ($item instanceof Node && $this->containsVariable($item, $variableName)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param array<string, Expr> $substitutions
     */
    private function containsSubstitutableVariable(Node $node, array $substitutions): bool
    {
        if ($node instanceof Expr\Variable && is_string($node->name) && isset($substitutions[$node->name])) {
            return true;
        }

        foreach ($node->getSubNodeNames() as $childName) {
            $child = $node->$childName;
            if ($child instanceof Node) {
                if ($this->containsSubstitutableVariable($child, $substitutions)) {
                    return true;
                }
                continue;
            }

            if (!is_array($child)) {
                continue;
            }

            foreach ($child as $item) {
                if ($item instanceof Node && $this->containsSubstitutableVariable($item, $substitutions)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param array<string, Expr> $substitutions
     */
    private function substituteNode(Node $node, array $substitutions, ?Node $parent = null, ?string $subNodeName = null): Node
    {
        if ($node instanceof Expr\Variable
            && is_string($node->name)
            && isset($substitutions[$node->name])
            && $this->canSubstituteVariable($parent, $subNodeName)
        ) {
            return clone $substitutions[$node->name];
        }

        $clone = clone $node;
        foreach ($clone->getSubNodeNames() as $childName) {
            $child = $clone->$childName;
            if ($child instanceof Node) {
                $clone->$childName = $this->substituteNode($child, $substitutions, $clone, $childName);
                continue;
            }

            if (!is_array($child)) {
                continue;
            }

            foreach ($child as $index => $item) {
                if ($item instanceof Node) {
                    $child[$index] = $this->substituteNode($item, $substitutions, $clone, $childName);
                }
            }

            $clone->$childName = $child;
        }

        return $clone;
    }

    private function canSubstituteVariable(?Node $parent, ?string $subNodeName): bool
    {
        if ($parent instanceof Node\Expr\ClosureUse && $subNodeName === 'var') {
            return false;
        }

        if ($parent instanceof Node\Param && $subNodeName === 'var') {
            return false;
        }

        if (($parent instanceof Node\Expr\Assign || $parent instanceof Node\Expr\AssignRef) && $subNodeName === 'var') {
            return false;
        }

        if ($parent instanceof Node\Stmt\StaticVar && $subNodeName === 'var') {
            return false;
        }

        if ($parent instanceof Node\Stmt\Foreach_ && in_array($subNodeName, ['keyVar', 'valueVar'], true)) {
            return false;
        }

        if ($parent instanceof Node\Stmt\Catch_ && $subNodeName === 'var') {
            return false;
        }

        return true;
    }
}

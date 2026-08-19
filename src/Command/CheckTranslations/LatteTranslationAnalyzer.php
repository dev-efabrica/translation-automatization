<?php

namespace Efabrica\TranslationsAutomatization\Command\CheckTranslations;

use SplFileInfo;

class LatteTranslationAnalyzer
{
    private LatteTranslationExpressionResolver $expressionResolver;

    public function __construct(?LatteTranslationExpressionResolver $expressionResolver = null)
    {
        $this->expressionResolver = $expressionResolver ?? new LatteTranslationExpressionResolver();
    }

    public function analyze(SplFileInfo $file): array
    {
        $translateCalls = [];
        $variables = [];
        $lines = file($file->getPathname()) ?: [];

        foreach ($lines as $lineNumber => $lineContent) {
            $this->collectVariables($lineContent, $variables);

            if (preg_match_all('/\{_\s*([^}]+)\}/', $lineContent, $underscoreMatches) !== false) {
                foreach ($underscoreMatches[1] as $expression) {
                    $translateCalls = array_merge(
                        $translateCalls,
                        $this->buildTranslationCalls($file->getPathname(), $lineNumber + 1, trim($expression), $variables)
                    );
                }
            }

            if (preg_match_all('/\{[^}]*?((?:\'[^\']*\'|"[^"]*"|\$[A-Za-z_][A-Za-z0-9_]*)(?:\s*\.\s*(?:\'[^\']*\'|"[^"]*"|\$[A-Za-z_][A-Za-z0-9_]*))*)\s*\|\s*translate\b[^}]*\}/', $lineContent, $filterMatches) === false) {
                continue;
            }

            foreach ($filterMatches[1] as $expression) {
                $translateCalls = array_merge(
                    $translateCalls,
                    $this->buildTranslationCalls($file->getPathname(), $lineNumber + 1, trim($expression), $variables)
                );
            }
        }

        return $translateCalls;
    }

    /**
     * @param array<string, string> $variables
     * @return array<int, array<string, mixed>>
     */
    private function buildTranslationCalls(string $filePath, int $lineNumber, string $expression, array $variables): array
    {
        $result = $this->expressionResolver->resolve($expression, $variables);
        if ($result->isResolved()) {
            $translateCalls = [];
            foreach ($result->getValues() as $key) {
                $translateCalls[] = [
                    'resolvedKeys' => [$key],
                    'file' => $filePath,
                    'line' => $lineNumber,
                    'call' => 'in_latte',
                    'args' => null,
                    'isDynamic' => $result->isDynamic(),
                    'isResolved' => true,
                    'sourceExpression' => $expression,
                ];
            }

            return $translateCalls;
        }

        return [[
            'resolvedKeys' => [],
            'file' => $filePath,
            'line' => $lineNumber,
            'call' => 'in_latte',
            'args' => null,
            'isDynamic' => true,
            'isResolved' => false,
            'sourceExpression' => $expression,
            'keyPattern' => $this->expressionResolver->derivePattern($expression, $variables),
        ]];
    }

    /**
     * @param array<string, string> $variables
     */
    private function collectVariables(string $lineContent, array &$variables): void
    {
        if (preg_match_all('/\{var\s+\$([A-Za-z_][A-Za-z0-9_]*)\s*=\s*([^}]+)\}/', $lineContent, $matches, PREG_SET_ORDER) === false) {
            return;
        }

        foreach ($matches as $match) {
            $result = $this->expressionResolver->resolve(trim($match[2]), $variables);
            if ($result->isResolved() && count($result->getValues()) === 1) {
                $variables[$match[1]] = $result->getValues()[0];
            }
        }
    }

    /**
     * Detects visible plain-text content in Latte templates that is not wrapped in a
     * translation macro/filter (e.g. <strong>Typ:</strong>).
     *
     * @return array<int, array{file: string, line: int, text: string}>
     */
    public function findHardcodedTexts(SplFileInfo $file): array
    {
        $content = file_get_contents($file->getPathname());
        if ($content === false || $content === '') {
            return [];
        }

        $content = $this->blankOutNonTextRegions($content);

        $results = [];
        $lines = explode("\n", $content);
        foreach ($lines as $idx => $line) {
            $stripped = $this->stripLatteExpressions($line);
            $stripped = $this->neutralizeQuotedAngleBrackets($stripped);
            $count = preg_match_all('/>([^<>]+)</', $stripped, $matches);
            if ($count === false || $count === 0) {
                continue;
            }

            foreach ($matches[1] as $text) {
                $decoded = trim(html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                if (!$this->isVisibleTranslatableText($decoded)) {
                    continue;
                }

                $results[] = [
                    'file' => $file->getPathname(),
                    'line' => $idx + 1,
                    'text' => $decoded,
                ];
            }
        }

        return $results;
    }

    /**
     * Replaces Latte block comments, <script> and <style> blocks with empty whitespace
     * so they cannot produce false positives while preserving line numbers.
     */
    private function blankOutNonTextRegions(string $content): string
    {
        $patterns = [
            '/\{\*.*?\*\}/s',
            '/<script\b[^>]*>.*?<\/script>/is',
            '/<style\b[^>]*>.*?<\/style>/is',
            '/<!--.*?-->/s',
            '/<(i|span)\b[^>]*\bclass\s*=\s*["\'][^"\']*\bmaterial-(?:icons|symbols)[\w-]*[^"\']*["\'][^>]*>.*?<\/\1>/is',
        ];

        foreach ($patterns as $pattern) {
            $content = preg_replace_callback($pattern, static function (array $match): string {
                return str_repeat("\n", substr_count($match[0], "\n"));
            }, $content) ?? $content;
        }

        return $content;
    }

    /**
     * Replaces `<` and `>` characters that appear inside quoted attribute values
     * (e.g. Latte `n:if="$a->start_time < new \DateTime()"`) with spaces so they are
     * not mistaken for HTML tag boundaries by the text extraction below.
     */
    private function neutralizeQuotedAngleBrackets(string $line): string
    {
        return preg_replace_callback(
            '/"[^"]*"|\'[^\']*\'/',
            static function (array $match): string {
                return strtr($match[0], ['<' => ' ', '>' => ' ']);
            },
            $line
        ) ?? $line;
    }

    private function stripLatteExpressions(string $line): string
    {
        $previous = null;
        while ($previous !== $line) {
            $previous = $line;
            $line = preg_replace('/\{[^{}]*\}/', '', $line) ?? $line;
        }
        return $line;
    }

    private function isVisibleTranslatableText(string $text): bool
    {
        if ($text === '') {
            return false;
        }

        return preg_match('/\p{L}{2,}/u', $text) === 1;
    }
}

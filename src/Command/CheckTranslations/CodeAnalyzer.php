<?php

namespace Efabrica\TranslationsAutomatization\Command\CheckTranslations;

use Efabrica\TranslationsAutomatization\Command\CheckFormKeys\ClassMethodArgVisitor;
use Exception;
use Generator;
use PhpParser\Error;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class CodeAnalyzer
{
    private array $directories;

    private LatteTranslationAnalyzer $latteTranslationAnalyzer;

    public function __construct(array $directories, ?LatteTranslationAnalyzer $latteTranslationAnalyzer = null)
    {
        $this->directories = $directories;
        $this->latteTranslationAnalyzer = $latteTranslationAnalyzer ?? new LatteTranslationAnalyzer();
    }

    public function analyzeDirectories(): array
    {
        $parser = (new ParserFactory())->createForNewestSupportedVersion();
        $bodyCache = new MethodBodyCache($parser);
        $classIndex = $this->buildClassIndex($parser, $bodyCache, $this->expandDirectories($this->directories));

        $result = [];
        foreach ($this->directories as $directory) {
            if (!is_dir($directory)) {
                continue;
            }
            $result[] = $this->analyzeDirectory($directory, $parser, $classIndex);
        }

        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }

        return array_merge(...$result);
    }

    /**
     * Stream-parse PHP files, register class signatures (not method bodies) into the index per
     * file, and discard each AST before parsing the next one. Method bodies are re-parsed lazily
     * via MethodBodyCache when MethodSummaryResolver actually queries them.
     *
     * @param string[] $indexDirectories
     */
    private function buildClassIndex(Parser $parser, MethodBodyCache $bodyCache, array $indexDirectories): ProjectClassIndex
    {
        $classIndex = new ProjectClassIndex();
        $classIndex->setBodyCache($bodyCache);
        foreach ($indexDirectories as $directory) {
            if (!is_dir($directory)) {
                continue;
            }
            foreach ($this->iteratePhpFiles($directory) as $phpFile) {
                try {
                    $ast = $parser->parse((string) file_get_contents($phpFile));
                } catch (Error $error) {
                    echo "Error analyzing file $phpFile: " . $error->getMessage() . PHP_EOL;
                    continue;
                }
                if ($ast === null) {
                    continue;
                }
                $classIndex->collectFromAst($ast, $phpFile);
                unset($ast);
            }
        }
        $classIndex->resolveAllConstants(new TranslationKeyExpressionResolver());

        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }

        return $classIndex;
    }

    private function analyzeDirectory(string $directory, Parser $parser, ProjectClassIndex $classIndex): array
    {
        $translateCalls = [];
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
        foreach ($files as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $extension = $file->getExtension();
            if ($extension === 'php') {
                $code = (string) file_get_contents($file->getPathname());
                $translateCalls[] = $this->analyzeCode($code, $file->getPathname(), $parser, $classIndex);
                unset($code);
            } elseif ($extension === 'latte') {
                $translateCalls[] = $this->findInLatte($file);
            }
        }

        return array_merge(...$translateCalls);
    }

    private function findInLatte(SplFileInfo $file): array
    {
        return $this->latteTranslationAnalyzer->analyze($file);
    }

    private function analyzeCode(string $code, string $filePath, Parser $parser, ProjectClassIndex $classIndex): array
    {
        $result = [];
        try {
            $ast = $parser->parse($code);
            if ($ast === null) {
                return $result;
            }
            $traverser = new NodeTraverser();
            $traverser->addVisitor(new ClassMethodArgVisitor($result, $filePath, $classIndex, new TranslationKeyExpressionResolver()));
            $traverser->traverse($ast);
            unset($ast, $traverser);
        } catch (Exception $e) {
            echo "Error analyzing file $filePath: " . $e->getMessage() . PHP_EOL;
        }

        return $result;
    }

    /**
     * @return Generator<int, string>
     */
    private function iteratePhpFiles(string $directory): Generator
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                yield $file->getPathname();
            }
        }
    }

    /**
     * @param string[] $directories
     * @return string[]
     */
    private function expandDirectories(array $directories): array
    {
        $expandedDirectories = $directories;
        $coreVendorDirectory = './vendor/notcms/core/src';
        if (is_dir($coreVendorDirectory)) {
            $expandedDirectories[] = $coreVendorDirectory;
        }

        return array_values(array_unique($expandedDirectories));
    }

    /**
     * @return array<int, array{file: string, line: int, text: string}>
     */
    public function findLatteHardcodedTexts(): array
    {
        $results = [];
        foreach ($this->directories as $directory) {
            if (!is_dir($directory)) {
                continue;
            }
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
            foreach ($iterator as $file) {
                if (!$file->isFile() || $file->getExtension() !== 'latte') {
                    continue;
                }
                $results = array_merge($results, $this->latteTranslationAnalyzer->findHardcodedTexts($file));
            }
        }

        return $results;
    }
}

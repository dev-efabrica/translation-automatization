<?php

namespace Efabrica\TranslationsAutomatization\Command\CheckTranslations;


use Efabrica\TranslationsAutomatization\Command\CheckFormKeys\ClassMethodArgVisitor;
use Exception;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class CodeAnalyzer
{
    private $directories;

    public function __construct(array $directories)
    {
        $this->directories = $directories;
    }

    public function analyzeDirectories(): array
    {
        $result = [];
        foreach ($this->directories as $directory) {
            if (is_dir($directory)) {
                $result[] = $this->analyzeDirectory($directory);
            }
        }
        return array_merge(...$result);
    }

    private function analyzeDirectory(string $directory): array
    {
        $translateCalls = [];
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
        foreach ($files as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $code = file_get_contents($file->getPathname());
            if ($file->getExtension() === 'php') {
                $translateCalls[] = $this->analyzeCode($code, $file->getPathname());
            }
            if ($file->getExtension() === 'latte') {
                $translateCalls[] = $this->findInLatte($file);
            }
        }

        return array_merge(...$translateCalls);
    }

    private function findInLatte(SplFileInfo $file): array
    {
        $translateCalls = [];
        $regex = "/\{_'[^']*'\}/";
        $content = file_get_contents($file->getPathname());
        if (preg_match_all($regex, $content, $matches)) {
            $lines = file($file->getPathname());
            foreach ($matches[0] as $match) {
                foreach ($lines as $lineNumber => $lineContent) {
                    if (strpos($lineContent, $match) !== false) {
                        $translateCalls[] = ['key' => substr($match, 3, -2), 'file' => $file->getPathname(), 'line' => $lineNumber + 1, 'method' => 'in_latte'];
                    }
                }
            }
        }
        return $translateCalls;
    }

    private function analyzeCode(string $code, string $filePath): array
    {
        $parser = (new ParserFactory())->createForNewestSupportedVersion();
        $traverser = new NodeTraverser();
        $result = [];

        $traverser->addVisitor(new ClassMethodArgVisitor($result, $filePath));

        try {
            $ast = $parser->parse($code);
            $traverser->traverse($ast);
        } catch (Exception $e) {
            echo "Error analyzing file $filePath: " . $e->getMessage() . PHP_EOL;
        }

        return $result;
    }
}

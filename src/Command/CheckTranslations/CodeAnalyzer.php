<?php

namespace Efabrica\TranslationsAutomatization\Command\CheckTranslations;


use Efabrica\TranslationsAutomatization\Command\CheckFormKeys\ClassMethodArgVisitor;
use Exception;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

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
            if ($file->isFile() && $file->getExtension() === 'php') {
                $code = file_get_contents($file->getPathname());
                $translateCalls[] = $this->analyzeCode($code, $file->getPathname());
            }
        }

        return array_merge(...$translateCalls);
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

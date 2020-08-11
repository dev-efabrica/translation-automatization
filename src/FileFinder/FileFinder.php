<?php

namespace Efabrica\TranslationsAutomatization\FileFinder;

use Symfony\Component\Finder\Finder;

class FileFinder implements FileFinderInterface
{
    private $dirs = [];

    private $filePatterns = [];

    public function __construct(array $dirs, array $filePatterns)
    {
        $this->dirs = $dirs;
        $this->filePatterns = $filePatterns;
    }

    public function find(): array
    {
        $finder = Finder::create()->files();
        foreach ($this->filePatterns as $filePattern) {
            $finder->name($filePattern);
        }
        $finder->in($this->dirs);

        $files = [];
        foreach ($finder as $file) {
            $files[] = (string) $file;
        }
        return $files;
    }
}

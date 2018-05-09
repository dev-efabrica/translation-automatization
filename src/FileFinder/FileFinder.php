<?php

namespace Efabrica\TranslationsAutomatization\FileFinder;

use Symfony\Component\Finder\Finder;

class FileFinder implements FileFinderInterface
{
    private $dirs = [];

    private $extensions = [];

    public function __construct(array $dirs, array $extensions)
    {
        $this->dirs = $dirs;
        $this->extensions = $extensions;
    }

    public function find(): array
    {
        $finder = Finder::create()->files();
        foreach ($this->extensions as $extension) {
            $finder->name("*.$extension");
        }
        $finder->in($this->dirs);

        $files = [];
        foreach ($finder as $file) {
            $files[] = (string) $file;
        }
        return $files;
    }
}

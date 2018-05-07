<?php

namespace Efabrica\TranslationsAutomatization\FileFinder;

interface FileFinderInterface
{
    /**
     * @return string[] list of file paths
     */
    public function find(): array;
}

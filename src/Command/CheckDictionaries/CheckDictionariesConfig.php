<?php

namespace Efabrica\TranslationsAutomatization\Command\CheckDictionaries;

use Efabrica\TranslationsAutomatization\Storage\StorageInterface;

class CheckDictionariesConfig
{
    /** @var array<string, <string, string>> */
    private $dictionaries;

    /**
     * @param array<string, <string, string>> $dictionaries [language => [key => translation]]
     */
    public function __construct(array $dictionaries)
    {
        $this->dictionaries = $dictionaries;
    }

    /**
     * @return array<string, <string, string>> [language => [key => translation]]
     */
    public function load(): array
    {
        return $this->dictionaries;
    }
}

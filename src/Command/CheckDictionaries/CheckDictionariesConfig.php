<?php

namespace Efabrica\TranslationsAutomatization\Command\CheckDictionaries;

use Efabrica\TranslationsAutomatization\Storage\StorageInterface;

class CheckDictionariesConfig
{
    /** @var array<string, StorageInterface[]> */
    private $dictionaryStorages;

    /**
     * @param array<string, StorageInterface[]> $dictionaryStorages
     */
    public function __construct(array $dictionaryStorages)
    {
        $this->dictionaryStorages = $dictionaryStorages;
    }

    /**
     * @return array<string, string>
     */
    public function load(): array
    {
        $dictionaries = [];
        foreach ($this->dictionaryStorages as $lang => $storages) {
            foreach ($storages as $storage) {
                $dictionaries[$lang] = array_merge($dictionaries[$lang] ?? [], $storage->load());
            }
        }
        return $dictionaries;
    }
}

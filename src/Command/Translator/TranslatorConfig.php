<?php

namespace Efabrica\TranslationsAutomatization\Command\Translator;

use Efabrica\TranslationsAutomatization\Storage\StorageInterface;
use Efabrica\TranslationsAutomatization\Translator\TranslatorInterface;

class TranslatorConfig
{
    private $something = [];

    public function add(StorageInterface $source, StorageInterface $target, TranslatorInterface $translator): TranslatorConfig
    {
        $this->something[] = [
            $source,
            $target,
            $translator
        ];
        return $this;
    }

    public function translate(): int
    {
        $count = 0;
        foreach ($this->something as $something) {
            $texts = $something[0]->load();
            $newTexts = $something[2]->translate(array_values($texts));

            $translations = [];
            foreach ($texts as $key => $text) {
                $translations[$key] = $newTexts[$text] ?? '';
                $count++;
            }
            $something[1]->save($translations);
        }
        return $count;
    }
}

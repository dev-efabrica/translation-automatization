<?php

namespace Efabrica\TranslationsAutomatization\Command\Translator;

use Efabrica\TranslationsAutomatization\Storage\StorageInterface;
use Efabrica\TranslationsAutomatization\Translator\TranslatorInterface;

class Translator
{
    private $something = [];

    public function add(StorageInterface $source, StorageInterface $target, TranslatorInterface $translator): Translator
    {
        $this->something[] = [
            $source,
            $target,
            $translator
        ];
        return $this;
    }

    public function translate(): void
    {
        foreach ($this->something as $something) {
            $texts = $something[0]->load();
            $newTexts = $something[2]->translate(array_values($texts));

            $translations = [];
            foreach ($texts as $key => $text) {
                $translations[$key] = $newTexts[$text] ?? '';
            }
            $something[1]->save($translations);
        }
    }
}

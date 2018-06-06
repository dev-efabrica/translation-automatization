<?php

namespace Efabrica\TranslationsAutomatization\Translator;

interface TranslatorInterface
{
    /**
     * @param array $texts - list of texts to be translated
     * @return array - list of translated texts in format old_text => new_text
     */
    public function translate(array $texts): array;
}

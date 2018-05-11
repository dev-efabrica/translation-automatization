<?php

namespace Efabrica\TranslationsAutomatization\Translator;

interface TranslatorInterface
{
    /**
     * @param array $strings - list of strings to be translated
     * @return array - list of translated strings in format old_string => new_string
     */
    public function translate(array $strings): array;
}

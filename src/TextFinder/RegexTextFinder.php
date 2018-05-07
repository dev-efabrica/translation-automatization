<?php

namespace Efabrica\TranslationsAutomatization\TextFinder;

class RegexTextFinder implements TextFinderInterface
{
    private $patterns = [];

    public function addPattern(string $pattern, int $textPosition = 1)
    {
        $this->patterns[$pattern] = $textPosition;
        return $this;
    }

    public function find(string $content): array
    {
        $texts = [];
        foreach ($this->patterns as $pattern => $textPosition) {
            preg_match_all($pattern, $content, $matches);
            for ($i = 0; $i < count($matches[0]); ++$i) {
                $text = trim($matches[$textPosition][$i]);
                if ($text === '') {
                    continue;
                }
                $texts[trim($matches[0][$i])] = $text;
            }
        }

        return $texts;
    }
}

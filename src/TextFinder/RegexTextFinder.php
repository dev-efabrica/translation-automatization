<?php

namespace Efabrica\TranslationsAutomatization\TextFinder;

class RegexTextFinder implements TextFinderInterface
{
    private $patterns = [];

    /**
     * @param string $pattern
     * @param int|null $textPosition position of text in pattern, use null to remove possible false positives only
     * @return RegexTextFinder
     */
    public function addPattern(string $pattern, ?int $textPosition = 1): RegexTextFinder
    {
        $this->patterns[$pattern] = $textPosition;
        return $this;
    }

    public function find(string $content): array
    {
        $texts = [];
        foreach ($this->patterns as $pattern => $textPosition) {
            if ($textPosition === null) {
                $content = preg_replace($pattern, '', $content);
                continue;
            }
            preg_match_all($pattern, $content, $matches);
            $matchesCount = count($matches[0]);
            for ($i = 0; $i < $matchesCount; ++$i) {
                $text = trim($matches[$textPosition][$i]);
                if ($text === '') {
                    continue;
                }
                $texts[trim($matches[0][$i])] = $text;
            }
            $content = preg_replace($pattern, '', $content);
        }

        return $texts;
    }
}

<?php

namespace Efabrica\TranslationsAutomatization\TokenModifier;

use Efabrica\TranslationsAutomatization\Tokenizer\Token;
use Efabrica\TranslationsAutomatization\TokenModifier\TokenModifier;

class TrimTokenModifier extends TokenModifier
{
    private $affectedTexts;

    private $prefixCharacterMask;

    private $suffixCharacterMask;

    public function __construct(
        int $affectedTexts = Token::TOKEN_ALL,
        ?string $prefixCharacterMask = null,
        ?string $suffixCharacterMask = null
    ) {
        $this->affectedTexts = $affectedTexts;
        $this->prefixCharacterMask = $prefixCharacterMask;
        $this->suffixCharacterMask = $suffixCharacterMask;
    }

    protected function modify(Token $token): Token
    {
        if ($this->affectedTexts & Token::TOKEN_TRANSLATION_KEY) {
            $token->changeTranslationKey($this->changeText($token->getTranslationKey()));
        }
        if ($this->affectedTexts & Token::TOKEN_TRANSLATION_CODE) {
            $token->changeTranslationCode($this->changeText($token->getTranslationCode()));
        }
        if ($this->affectedTexts & Token::TOKEN_TARGET_TEXT) {
            $token->changeTargetText($this->changeText($token->getTargetText()));
        }
        return $token;
    }

    private function changeText(string $text): string
    {
        $text = $this->prefixCharacterMask === null ? ltrim($text) : ltrim($text, $this->prefixCharacterMask);
        $text = $this->suffixCharacterMask === null ? rtrim($text) : rtrim($text, $this->suffixCharacterMask);
        return $text;
    }
}

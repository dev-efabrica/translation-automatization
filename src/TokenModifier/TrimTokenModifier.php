<?php

namespace Efabrica\TranslationsAutomatization\TokenModifier;

use Efabrica\TranslationsAutomatization\Tokenizer\Token;
use Efabrica\TranslationsAutomatization\TokenModifier\TokenModifier;

class TrimTokenModifier extends TokenModifier
{
    private $prefixCharacterMask;

    private $suffixCharacterMask;

    private $affectedTexts;

    public function __construct(
        ?string $prefixCharacterMask = null,
        ?string $suffixCharacterMask = null,
        int $affectedTexts = Token::TOKEN_ALL
    ) {
        $this->prefixCharacterMask = $prefixCharacterMask;
        $this->suffixCharacterMask = $suffixCharacterMask;
        $this->affectedTexts = $affectedTexts;
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
        $text = ltrim($text, $this->prefixCharacterMask);
        $text = rtrim($text, $this->suffixCharacterMask);
        return $text;
    }
}

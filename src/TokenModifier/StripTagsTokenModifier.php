<?php

namespace Efabrica\TranslationsAutomatization\TokenModifier;

use Efabrica\TranslationsAutomatization\Tokenizer\Token;
use Efabrica\TranslationsAutomatization\TokenModifier\TokenModifier;

class StripTagsTokenModifier extends TokenModifier
{
    private $affectedTexts;

    private $allowedTags;

    public function __construct(int $affectedTexts = Token::TOKEN_ALL, string $allowedTags = null)
    {
        $this->affectedTexts = $affectedTexts;
        $this->allowedTags = $allowedTags;
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
        return strip_tags($text, $this->allowedTags);
    }
}

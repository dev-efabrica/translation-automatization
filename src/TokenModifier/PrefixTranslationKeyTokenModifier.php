<?php

namespace Efabrica\TranslationsAutomatization\TokenModifier;

use Efabrica\TranslationsAutomatization\Tokenizer\Token;
use Efabrica\TranslationsAutomatization\TokenModifier\TokenModifier;

class PrefixTranslationKeyTokenModifier extends TokenModifier
{
    private $translationKeyPrefix;

    public function __construct(string $translationKeyPrefix)
    {
        $this->translationKeyPrefix = $translationKeyPrefix;
    }

    protected function modify(Token $token): Token
    {
        $token->changeTranslationKey($this->translationKeyPrefix . $token->getTranslationKey());
        return $token;
    }
}

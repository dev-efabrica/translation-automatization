<?php

namespace Efabrica\TranslationsAutomatization\Bridge\Latte\TokenModifier;

use Efabrica\TranslationsAutomatization\Tokenizer\Token;
use Efabrica\TranslationsAutomatization\TokenModifier\TokenModifier;

class LatteTokenModifier extends TokenModifier
{
    private $translationPattern;

    public function __construct(string $translationPattern = '{_\'%%%TRANSLATION_KEY%%%\'}')
    {
        $this->translationPattern = $translationPattern;
    }

    protected function modify(Token $token): Token
    {
        $translationPattern = str_replace('%%%TRANSLATION_KEY%%%', $token->getTranslationKey(), $this->translationPattern);
        $token->changeTranslationCode($translationPattern);
        return $token;
    }
}

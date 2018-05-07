<?php

namespace Efabrica\TranslationsAutomatization\Bridge\KdybyTranslation\TokenModifier;

use Efabrica\TranslationsAutomatization\Tokenizer\Token;
use Efabrica\TranslationsAutomatization\TokenModifier\TokenModifier;

class OneTranslationFileTokenModifier extends TokenModifier
{
    private $translationFilePrefix;

    public function __construct(string $translationFilePrefix)
    {
        $this->translationFilePrefix = $translationFilePrefix;
    }

    protected function modify(Token $token): Token
    {
        $token->changeTranslationKey($this->translationFilePrefix . '.' . $token->getTranslationKey());
        return $token;
    }
}

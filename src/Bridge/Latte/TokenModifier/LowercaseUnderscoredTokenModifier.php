<?php

namespace Efabrica\TranslationsAutomatization\Bridge\Latte\TokenModifier;

use Efabrica\TranslationsAutomatization\Tokenizer\Token;
use Efabrica\TranslationsAutomatization\TokenModifier\TokenModifier;
use Nette\Utils\Strings;

class LowercaseUnderscoredTokenModifier extends TokenModifier
{
    protected function modify(Token $token): Token
    {
        $token->changeTranslationKey(str_replace('-', '_', Strings::webalize($token->getTranslationKey())));
        return $token;
    }
}

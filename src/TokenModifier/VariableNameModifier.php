<?php

namespace Efabrica\TranslationsAutomatization\TokenModifier;

use Efabrica\TranslationsAutomatization\Tokenizer\Token;
use Exception;

class VariableNameModifier extends TokenModifier
{
    protected function modify(Token $token): Token
    {
        preg_match('#\$(.*?)=#', $token->getOriginalBlock(), $matches);
        if (!isset($matches[1])) {
            throw new Exception('Cannot find variable name in ' . $token->getOriginalBlock());
        }
        $token->changeTranslationKey(trim($matches[1]));
        return $token;
    }
}

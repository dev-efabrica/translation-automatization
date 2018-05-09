<?php

namespace Efabrica\TranslationsAutomatization\TokenModifier;

use Efabrica\TranslationsAutomatization\Tokenizer\Token;
use Efabrica\TranslationsAutomatization\Tokenizer\TokenCollection;

abstract class TokenModifier implements TokenModifierInterface
{
    abstract protected function modify(Token $token): Token;

    public function modifyAll(TokenCollection $tokenCollection): TokenCollection
    {
        foreach ($tokenCollection->getTokens() as $token) {
            $this->modify($token);
        }
        return $tokenCollection;
    }
}

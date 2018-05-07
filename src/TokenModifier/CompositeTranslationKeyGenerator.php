<?php

namespace Efabrica\TranslationsAutomatization\TokenModifier;

use Efabrica\TranslationsAutomatization\Tokenizer\TokenCollection;

class CompositeTokenModifier implements TokenModifierInterface
{
    private $tokenModifiers = [];

    public function addTokenModifier(TokenModifierInterface $tokenModifier)
    {
        $this->tokenModifiers[] = $tokenModifier;
        return $this;
    }

    public function modifyAll(TokenCollection $tokenCollection): TokenCollection
    {
        foreach ($this->tokenModifiers as $tokenModifier) {
            $tokenCollection = $tokenModifier->modifyAll($tokenCollection);
        }
        return $tokenCollection;
    }
}

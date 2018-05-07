<?php

namespace Efabrica\TranslationsAutomatization\TokenModifier;

use Efabrica\TranslationsAutomatization\Tokenizer\TokenCollection;

interface TokenModifierInterface
{
    public function modifyAll(TokenCollection $tokenCollection): TokenCollection;
}

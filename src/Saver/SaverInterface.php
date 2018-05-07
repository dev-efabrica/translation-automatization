<?php

namespace Efabrica\TranslationsAutomatization\Saver;

use Efabrica\TranslationsAutomatization\Tokenizer\TokenCollection;

interface SaverInterface
{
    public function save(TokenCollection $tokenCollection): bool;
}

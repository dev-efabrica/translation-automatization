<?php

namespace Efabrica\TranslationsAutomatization\Tests\TokenModifier;

use Efabrica\TranslationsAutomatization\Tokenizer\TokenCollection;
use Efabrica\TranslationsAutomatization\TokenModifier\TranslateTokenModifier;

class TranslateTokenModifierTest extends AbstractTokenModifierTest
{
    public function testTranslateAllKeys()
    {
        $tokenModifier = new TranslateTokenModifier($this->createTranslator());
        $originalTokens = $this->copyTokens($this->tokenCollection->getTokens());
        $newTokenCollection = $tokenModifier->modifyAll($this->tokenCollection);
        $newTokens = $newTokenCollection->getTokens();

        $this->assertInstanceOf(TokenCollection::class, $newTokenCollection);
        for ($i = 0; $i < count($originalTokens); $i++) {
            $originalToken = $originalTokens[$i];
            $newToken = $newTokens[$i];
            $this->assertNotEquals($originalToken->getTranslationKey(), $newToken->getTranslationKey());
        }
    }

    public function testEmptyTokenCollection()
    {
        $tokenModifier = new TranslateTokenModifier($this->createTranslator());
        $tokenCollection = new TokenCollection('/path/to/file');

        $this->assertEmpty($tokenCollection->getTokens());
        $newTokenCollection = $tokenModifier->modifyAll($tokenCollection);
        $this->assertInstanceOf(TokenCollection::class, $newTokenCollection);
        $this->assertEmpty($newTokenCollection->getTokens());
    }
}

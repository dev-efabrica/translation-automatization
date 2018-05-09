<?php

namespace Efabrica\TranslationsAutomatization\Tests\TokenModifier;

use Efabrica\TranslationsAutomatization\Tokenizer\TokenCollection;
use Efabrica\TranslationsAutomatization\TokenModifier\BingTranslateTokenModifier;

class BingTranslateTokenModifierTest extends AbstractTokenModifierTest
{
    public function testTranslateSkToEn()
    {
        $tokenModifier = new BingTranslateTokenModifier('sk', 'en');
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
        $tokenModifier = new BingTranslateTokenModifier('sk', 'en');
        $tokenCollection = new TokenCollection('/path/to/file');
        $this->assertEmpty($tokenCollection->getTokens());
        $newTokenCollection = $tokenModifier->modifyAll($tokenCollection);
        $this->assertInstanceOf(TokenCollection::class, $newTokenCollection);
        $this->assertEmpty($newTokenCollection->getTokens());
    }
}

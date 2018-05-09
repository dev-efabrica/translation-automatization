<?php

namespace Efabrica\TranslationsAutomatization\Tests\TokenModifier;

use Efabrica\TranslationsAutomatization\Bridge\Latte\TokenModifier\LatteTokenModifier;
use Efabrica\TranslationsAutomatization\Tokenizer\TokenCollection;

class LatteTokenModifierTest extends AbstractTokenModifierTest
{
    public function testDefault()
    {
        $tokenModifier = new LatteTokenModifier();
        $originalTokens = $this->copyTokens($this->tokenCollection->getTokens());
        $newTokenCollection = $tokenModifier->modifyAll($this->tokenCollection);
        $this->assertInstanceOf(TokenCollection::class, $newTokenCollection);
        $newTokens = $newTokenCollection->getTokens();
        $this->assertNotEmpty($originalTokens);
        $this->assertNotEmpty($newTokens);
        $this->assertEquals(count($originalTokens), count($newTokens));
        for ($i = 0; $i < count($originalTokens); $i++) {
            $originalToken = $originalTokens[$i];
            $newToken = $newTokens[$i];
            $this->assertEquals('{_\'' . $originalToken->getTranslationKey() . '\'}', $newToken->getTranslationCode());
        }
    }

    public function testModifiedTranslationPattern()
    {
        $tokenModifier = new LatteTokenModifier('{= \'%%%TRANSLATION_KEY%%%\'|translate}');
        $originalTokens = $this->copyTokens($this->tokenCollection->getTokens());
        $newTokenCollection = $tokenModifier->modifyAll($this->tokenCollection);
        $this->assertInstanceOf(TokenCollection::class, $newTokenCollection);
        $newTokens = $newTokenCollection->getTokens();
        $this->assertNotEmpty($originalTokens);
        $this->assertNotEmpty($newTokens);
        $this->assertEquals(count($originalTokens), count($newTokens));
        for ($i = 0; $i < count($originalTokens); $i++) {
            $originalToken = $originalTokens[$i];
            $newToken = $newTokens[$i];
            $this->assertEquals('{= \'' . $originalToken->getTranslationKey() . '\'|translate}', $newToken->getTranslationCode());
        }
    }
}

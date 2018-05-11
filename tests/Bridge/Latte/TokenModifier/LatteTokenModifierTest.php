<?php

namespace Efabrica\TranslationsAutomatization\Tests\Bridge\Latte\TokenModifier;

use Efabrica\TranslationsAutomatization\Bridge\Latte\TokenModifier\LatteTokenModifier;
use Efabrica\TranslationsAutomatization\Tests\TokenModifier\AbstractTokenModifierTest;
use Efabrica\TranslationsAutomatization\Tokenizer\TokenCollection;

class LatteTokenModifierTest extends AbstractTokenModifierTest
{
    public function testDefault()
    {
        $tokenModifier = new LatteTokenModifier();
        $originalTokens = $this->copyTokens($this->tokenCollection->getTokens());
        $newTokenCollection = $tokenModifier->modifyAll($this->tokenCollection);
        $newTokens = $newTokenCollection->getTokens();
        $this->assertInstanceOf(TokenCollection::class, $newTokenCollection);
        for ($i = 0; $i < count($originalTokens); $i++) {
            $originalToken = $originalTokens[$i];
            $newToken = $newTokens[$i];
            $this->assertEquals('{_\'' . $originalToken->getTranslationKey() . '\'}', $newToken->getTranslationCode());
        }
    }

    public function testOwnPatternWithoutTranslationKeyToken()
    {
        $tokenModifier = new LatteTokenModifier('this-is-my-translation-code');
        $newTokenCollection = $tokenModifier->modifyAll($this->tokenCollection);
        $newTokens = $newTokenCollection->getTokens();
        $this->assertInstanceOf(TokenCollection::class, $newTokenCollection);
        for ($i = 0; $i < count($newTokens); $i++) {
            $newToken = $newTokens[$i];
            $this->assertEquals('this-is-my-translation-code', $newToken->getTranslationCode());
        }
    }

    public function testOwnPatternWithTranslationKeyToken()
    {
        $tokenModifier = new LatteTokenModifier('{= \'%%%TRANSLATION_KEY%%%\'|translate}');
        $originalTokens = $this->copyTokens($this->tokenCollection->getTokens());
        $newTokenCollection = $tokenModifier->modifyAll($this->tokenCollection);
        $newTokens = $newTokenCollection->getTokens();
        $this->assertInstanceOf(TokenCollection::class, $newTokenCollection);
        for ($i = 0; $i < count($originalTokens); $i++) {
            $originalToken = $originalTokens[$i];
            $newToken = $newTokens[$i];
            $this->assertEquals('{= \'' . $originalToken->getTranslationKey() . '\'|translate}', $newToken->getTranslationCode());
        }
    }
}

<?php

namespace Efabrica\TranslationsAutomatization\Tests\TokenModifier;

use Efabrica\TranslationsAutomatization\Tokenizer\Token;
use Efabrica\TranslationsAutomatization\Tokenizer\TokenCollection;
use Efabrica\TranslationsAutomatization\TokenModifier\TrimTokenModifier;

class TrimTokenModifierTest extends AbstractTokenModifierTest
{
    public function testDefault()
    {
        $tokenModifier = new TrimTokenModifier();
        $originalTokens = $this->copyTokens($this->tokenCollection->getTokens());
        $newTokenCollection = $tokenModifier->modifyAll($this->tokenCollection);
        $this->assertInstanceOf(TokenCollection::class, $newTokenCollection);
        $newTokens = $newTokenCollection->getTokens();
        $this->assertNotEmpty($originalTokens);
        $this->assertNotEmpty($newTokens);
        $this->assertEquals(count($originalTokens), count($newTokens));
        $originalToken = $originalTokens[3];
        $newToken = $newTokens[3];
        $this->assertTrue(substr($originalToken->getTranslationKey(), 0, 3) === " \t\n");
        $this->assertTrue(substr($originalToken->getTranslationKey(), -3, 3) === "\t\n ");
        $this->assertTrue(substr($originalToken->getTranslationCode(), 0, 3) === " \t\n");
        $this->assertTrue(substr($originalToken->getTranslationCode(), -3, 3) === "\t\n ");
        $this->assertTrue(substr($originalToken->getTargetText(), 0, 3) === " \t\n");
        $this->assertTrue(substr($originalToken->getTargetText(), -3, 3) === "\t\n ");
        $this->assertTrue(substr($newToken->getTranslationKey(), 0, 3) !== " \t\n");
        $this->assertTrue(substr($newToken->getTranslationKey(), -3, 3) !== "\t\n ");
        $this->assertTrue(substr($newToken->getTranslationCode(), 0, 3) !== " \t\n");
        $this->assertTrue(substr($newToken->getTranslationCode(), -3, 3) !== "\t\n ");
        $this->assertTrue(substr($newToken->getTargetText(), 0, 3) !== " \t\n");
        $this->assertTrue(substr($newToken->getTargetText(), -3, 3) !== "\t\n ");
    }

    public function testAllowedSpaces()
    {
        $tokenModifier = new TrimTokenModifier(Token::TOKEN_ALL, "\t\n", "\t\n");
        $originalTokens = $this->copyTokens($this->tokenCollection->getTokens());
        $newTokenCollection = $tokenModifier->modifyAll($this->tokenCollection);
        $this->assertInstanceOf(TokenCollection::class, $newTokenCollection);
        $newTokens = $newTokenCollection->getTokens();
        $this->assertNotEmpty($originalTokens);
        $this->assertNotEmpty($newTokens);
        $this->assertEquals(count($originalTokens), count($newTokens));
        $originalToken = $originalTokens[3];
        $newToken = $newTokens[3];
        $this->assertTrue(substr($originalToken->getTranslationKey(), 0, 3) === " \t\n");
        $this->assertTrue(substr($originalToken->getTranslationKey(), -3, 3) === "\t\n ");
        $this->assertTrue(substr($originalToken->getTranslationCode(), 0, 3) === " \t\n");
        $this->assertTrue(substr($originalToken->getTranslationCode(), -3, 3) === "\t\n ");
        $this->assertTrue(substr($originalToken->getTargetText(), 0, 3) === " \t\n");
        $this->assertTrue(substr($originalToken->getTargetText(), -3, 3) === "\t\n ");
        $this->assertTrue(substr($newToken->getTranslationKey(), 0, 3) === " \t\n");
        $this->assertTrue(substr($newToken->getTranslationKey(), -3, 3) === "\t\n ");
        $this->assertTrue(substr($newToken->getTranslationCode(), 0, 3) === " \t\n");
        $this->assertTrue(substr($newToken->getTranslationCode(), -3, 3) === "\t\n ");
        $this->assertTrue(substr($newToken->getTargetText(), 0, 3) === " \t\n");
        $this->assertTrue(substr($newToken->getTargetText(), -3, 3) === "\t\n ");
    }

    public function testAllowedSpacesInTargetText()
    {
        $tokenModifier = new TrimTokenModifier(Token::TOKEN_TRANSLATION_KEY | Token::TOKEN_TRANSLATION_CODE, " \t", " \t");
        $originalTokens = $this->copyTokens($this->tokenCollection->getTokens());
        $newTokenCollection = $tokenModifier->modifyAll($this->tokenCollection);
        $this->assertInstanceOf(TokenCollection::class, $newTokenCollection);
        $newTokens = $newTokenCollection->getTokens();
        $this->assertNotEmpty($originalTokens);
        $this->assertNotEmpty($newTokens);
        $this->assertEquals(count($originalTokens), count($newTokens));
        $originalToken = $originalTokens[3];
        $newToken = $newTokens[3];
        $this->assertTrue(substr($originalToken->getTranslationKey(), 0, 3) === " \t\n");
        $this->assertTrue(substr($originalToken->getTranslationKey(), -3, 3) === "\t\n ");
        $this->assertTrue(substr($originalToken->getTranslationCode(), 0, 3) === " \t\n");
        $this->assertTrue(substr($originalToken->getTranslationCode(), -3, 3) === "\t\n ");
        $this->assertTrue(substr($originalToken->getTargetText(), 0, 3) === " \t\n");
        $this->assertTrue(substr($originalToken->getTargetText(), -3, 3) === "\t\n ");
        $this->assertTrue(substr($newToken->getTranslationKey(), 0, 1) === "\n");
        $this->assertTrue(substr($newToken->getTranslationKey(), -2, 2) === "\t\n");
        $this->assertTrue(substr($newToken->getTranslationCode(), 0, 1) === "\n");
        $this->assertTrue(substr($newToken->getTranslationCode(), -2, 2) === "\t\n");
        $this->assertTrue(substr($newToken->getTargetText(), 0, 3) === " \t\n");
        $this->assertTrue(substr($newToken->getTargetText(), -3, 3) === "\t\n ");
    }
}

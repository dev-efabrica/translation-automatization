<?php

namespace Efabrica\TranslationsAutomatization\Tests\TokenModifier;

use Efabrica\TranslationsAutomatization\Tokenizer\Token;
use Efabrica\TranslationsAutomatization\Tokenizer\TokenCollection;
use Efabrica\TranslationsAutomatization\TokenModifier\StripTagsTokenModifier;

class StripTagsTokenModifierTest extends AbstractTokenModifierTest
{
    public function testDefault()
    {
        $tokenModifier = new StripTagsTokenModifier();
        $originalTokens = $this->copyTokens($this->tokenCollection->getTokens());
        $newTokenCollection = $tokenModifier->modifyAll($this->tokenCollection);
        $this->assertInstanceOf(TokenCollection::class, $newTokenCollection);
        $newTokens = $newTokenCollection->getTokens();
        $this->assertNotEmpty($originalTokens);
        $this->assertNotEmpty($newTokens);
        $this->assertEquals(count($originalTokens), count($newTokens));
        $originalToken = $originalTokens[3];
        $newToken = $newTokens[3];
        $this->assertTrue(strpos($originalToken->getTranslationKey(), '<strong>') !== false);
        $this->assertTrue(strpos($originalToken->getTranslationKey(), '</strong>') !== false);
        $this->assertTrue(strpos($originalToken->getTranslationCode(), '<strong>') !== false);
        $this->assertTrue(strpos($originalToken->getTranslationCode(), '</strong>') !== false);
        $this->assertTrue(strpos($originalToken->getTargetText(), '<strong>') !== false);
        $this->assertTrue(strpos($originalToken->getTargetText(), '</strong>') !== false);
        $this->assertTrue(strpos($newToken->getTranslationKey(), '<strong>') === false);
        $this->assertTrue(strpos($newToken->getTranslationKey(), '</strong>') === false);
        $this->assertTrue(strpos($newToken->getTranslationCode(), '<strong>') === false);
        $this->assertTrue(strpos($newToken->getTranslationCode(), '</strong>') === false);
        $this->assertTrue(strpos($newToken->getTargetText(), '<strong>') === false);
        $this->assertTrue(strpos($newToken->getTargetText(), '</strong>') === false);
    }

    public function testAllowedTags()
    {
        $tokenModifier = new StripTagsTokenModifier(Token::TOKEN_ALL, '<strong>');
        $originalTokens = $this->copyTokens($this->tokenCollection->getTokens());
        $newTokenCollection = $tokenModifier->modifyAll($this->tokenCollection);
        $this->assertInstanceOf(TokenCollection::class, $newTokenCollection);
        $newTokens = $newTokenCollection->getTokens();
        $this->assertNotEmpty($originalTokens);
        $this->assertNotEmpty($newTokens);
        $this->assertEquals(count($originalTokens), count($newTokens));
        $originalToken = $originalTokens[3];
        $newToken = $newTokens[3];
        $this->assertTrue(strpos($originalToken->getTranslationKey(), '<strong>') !== false);
        $this->assertTrue(strpos($originalToken->getTranslationKey(), '</strong>') !== false);
        $this->assertTrue(strpos($originalToken->getTranslationCode(), '<strong>') !== false);
        $this->assertTrue(strpos($originalToken->getTranslationCode(), '</strong>') !== false);
        $this->assertTrue(strpos($originalToken->getTargetText(), '<strong>') !== false);
        $this->assertTrue(strpos($originalToken->getTargetText(), '</strong>') !== false);
        $this->assertTrue(strpos($newToken->getTranslationKey(), '<strong>') !== false);
        $this->assertTrue(strpos($newToken->getTranslationKey(), '</strong>') !== false);
        $this->assertTrue(strpos($newToken->getTranslationCode(), '<strong>') !== false);
        $this->assertTrue(strpos($newToken->getTranslationCode(), '</strong>') !== false);
        $this->assertTrue(strpos($newToken->getTargetText(), '<strong>') !== false);
        $this->assertTrue(strpos($newToken->getTargetText(), '</strong>') !== false);
    }

    public function testModifyKeyOnlyWithoutAllowedTags()
    {
        $tokenModifier = new StripTagsTokenModifier(Token::TOKEN_TRANSLATION_KEY);
        $originalTokens = $this->copyTokens($this->tokenCollection->getTokens());
        $newTokenCollection = $tokenModifier->modifyAll($this->tokenCollection);
        $this->assertInstanceOf(TokenCollection::class, $newTokenCollection);
        $newTokens = $newTokenCollection->getTokens();
        $this->assertNotEmpty($originalTokens);
        $this->assertNotEmpty($newTokens);
        $this->assertEquals(count($originalTokens), count($newTokens));
        $originalToken = $originalTokens[3];
        $newToken = $newTokens[3];
        $this->assertTrue(strpos($originalToken->getTranslationKey(), '<strong>') !== false);
        $this->assertTrue(strpos($originalToken->getTranslationKey(), '</strong>') !== false);
        $this->assertTrue(strpos($originalToken->getTranslationCode(), '<strong>') !== false);
        $this->assertTrue(strpos($originalToken->getTranslationCode(), '</strong>') !== false);
        $this->assertTrue(strpos($originalToken->getTargetText(), '<strong>') !== false);
        $this->assertTrue(strpos($originalToken->getTargetText(), '</strong>') !== false);
        $this->assertTrue(strpos($newToken->getTranslationKey(), '<strong>') === false);
        $this->assertTrue(strpos($newToken->getTranslationKey(), '</strong>') === false);
        $this->assertTrue(strpos($newToken->getTranslationCode(), '<strong>') !== false);
        $this->assertTrue(strpos($newToken->getTranslationCode(), '</strong>') !== false);
        $this->assertTrue(strpos($newToken->getTargetText(), '<strong>') !== false);
        $this->assertTrue(strpos($newToken->getTargetText(), '</strong>') !== false);
    }

    public function testModifyCodeOnlyWithoutAllowedTags()
    {
        $tokenModifier = new StripTagsTokenModifier(Token::TOKEN_TRANSLATION_CODE);
        $originalTokens = $this->copyTokens($this->tokenCollection->getTokens());
        $newTokenCollection = $tokenModifier->modifyAll($this->tokenCollection);
        $this->assertInstanceOf(TokenCollection::class, $newTokenCollection);
        $newTokens = $newTokenCollection->getTokens();
        $this->assertNotEmpty($originalTokens);
        $this->assertNotEmpty($newTokens);
        $this->assertEquals(count($originalTokens), count($newTokens));
        $originalToken = $originalTokens[3];
        $newToken = $newTokens[3];
        $this->assertTrue(strpos($originalToken->getTranslationKey(), '<strong>') !== false);
        $this->assertTrue(strpos($originalToken->getTranslationKey(), '</strong>') !== false);
        $this->assertTrue(strpos($originalToken->getTranslationCode(), '<strong>') !== false);
        $this->assertTrue(strpos($originalToken->getTranslationCode(), '</strong>') !== false);
        $this->assertTrue(strpos($originalToken->getTargetText(), '<strong>') !== false);
        $this->assertTrue(strpos($originalToken->getTargetText(), '</strong>') !== false);
        $this->assertTrue(strpos($newToken->getTranslationKey(), '<strong>') !== false);
        $this->assertTrue(strpos($newToken->getTranslationKey(), '</strong>') !== false);
        $this->assertTrue(strpos($newToken->getTranslationCode(), '<strong>') === false);
        $this->assertTrue(strpos($newToken->getTranslationCode(), '</strong>') === false);
        $this->assertTrue(strpos($newToken->getTargetText(), '<strong>') !== false);
        $this->assertTrue(strpos($newToken->getTargetText(), '</strong>') !== false);
    }

    public function testModifyTargetTextOnlyWithoutAllowedTags()
    {
        $tokenModifier = new StripTagsTokenModifier(Token::TOKEN_TARGET_TEXT);
        $originalTokens = $this->copyTokens($this->tokenCollection->getTokens());
        $newTokenCollection = $tokenModifier->modifyAll($this->tokenCollection);
        $this->assertInstanceOf(TokenCollection::class, $newTokenCollection);
        $newTokens = $newTokenCollection->getTokens();
        $this->assertNotEmpty($originalTokens);
        $this->assertNotEmpty($newTokens);
        $this->assertEquals(count($originalTokens), count($newTokens));
        $originalToken = $originalTokens[3];
        $newToken = $newTokens[3];
        $this->assertTrue(strpos($originalToken->getTranslationKey(), '<strong>') !== false);
        $this->assertTrue(strpos($originalToken->getTranslationKey(), '</strong>') !== false);
        $this->assertTrue(strpos($originalToken->getTranslationCode(), '<strong>') !== false);
        $this->assertTrue(strpos($originalToken->getTranslationCode(), '</strong>') !== false);
        $this->assertTrue(strpos($originalToken->getTargetText(), '<strong>') !== false);
        $this->assertTrue(strpos($originalToken->getTargetText(), '</strong>') !== false);
        $this->assertTrue(strpos($newToken->getTranslationKey(), '<strong>') !== false);
        $this->assertTrue(strpos($newToken->getTranslationKey(), '</strong>') !== false);
        $this->assertTrue(strpos($newToken->getTranslationCode(), '<strong>') !== false);
        $this->assertTrue(strpos($newToken->getTranslationCode(), '</strong>') !== false);
        $this->assertTrue(strpos($newToken->getTargetText(), '<strong>') === false);
        $this->assertTrue(strpos($newToken->getTargetText(), '</strong>') === false);
    }
}

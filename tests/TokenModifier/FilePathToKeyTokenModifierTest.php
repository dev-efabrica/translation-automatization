<?php

namespace Efabrica\TranslationsAutomatization\Tests\TokenModifier;

use Efabrica\TranslationsAutomatization\Tokenizer\TokenCollection;
use Efabrica\TranslationsAutomatization\TokenModifier\FilePathToKeyTokenModifier;

class FilePathToKeyTokenModifierTest extends AbstractTokenModifierTest
{
    public function testNoExcludedDirectories()
    {
        $tokenModifier = new FilePathToKeyTokenModifier('/absolute/');
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
            $this->assertTrue(strpos($originalToken->getTranslationKey(), 'path.to.') === false);
            $this->assertTrue(strpos($newToken->getTranslationKey(), 'path.to.') === 0);
        }
    }

    public function testOneExcludedDirectory()
    {
        $tokenModifier = new FilePathToKeyTokenModifier('/absolute/', ['to']);
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
            $this->assertTrue(strpos($originalToken->getTranslationKey(), 'path.') === false);
            $this->assertTrue(strpos($newToken->getTranslationKey(), 'path.to.') === false);
            $this->assertTrue(strpos($newToken->getTranslationKey(), 'path.') === 0);
        }
    }

    public function testAllExcludedDirectories()
    {
        $tokenModifier = new FilePathToKeyTokenModifier('/absolute/', ['to', 'path']);
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
            $this->assertTrue(strpos($originalToken->getTranslationKey(), 'path.') === false);
            $this->assertTrue(strpos($newToken->getTranslationKey(), 'path.to.') === false);
            $this->assertTrue(strpos($newToken->getTranslationKey(), 'path.') === false);
            $this->assertTrue(strpos($newToken->getTranslationKey(), '.') !== 0);
        }
    }
}

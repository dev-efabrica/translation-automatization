<?php

namespace Efabrica\TranslationsAutomatization\Tests\Bridge\KdybyTranslation\Saver;

use Efabrica\TranslationsAutomatization\Bridge\KdybyTranslation\Saver\OneFileTranslationSaver;
use Efabrica\TranslationsAutomatization\Tokenizer\Token;
use Efabrica\TranslationsAutomatization\Tokenizer\TokenCollection;
use Efabrica\TranslationsAutomatization\TokenModifier\LowercaseUnderscoredTokenModifier;
use Efabrica\TranslationsAutomatization\TokenModifier\PrefixTranslationKeyTokenModifier;
use PHPUnit\Framework\TestCase;

class OneFileTranslationSaverTest extends TestCase
{
    private $emptyFilePath;

    private $nonEmptyFilePath;

    private $complexFilePath;

    public function setUp()
    {
        $this->emptyFilePath = __DIR__ . '/../../../../temp/kdyby-saver-test-empty-file.neon';
        if (file_exists($this->emptyFilePath)) {
            unlink($this->emptyFilePath);
        }

        $this->nonEmptyFilePath = __DIR__ . '/../../../../temp/kdyby-saver-test-non-empty-file.neon';
        if (file_exists($this->nonEmptyFilePath)) {
            unlink($this->nonEmptyFilePath);
        }
        file_put_contents($this->nonEmptyFilePath, "hello: Ahoj\nworld: svet");

        $this->complexFilePath =  __DIR__ . '/../../../../temp/kdyby-saver-test-complex-file.neon';
        if (file_exists($this->complexFilePath)) {
            unlink($this->complexFilePath);
        }
    }

    public function testSaveEmptyCollectionToEmptyFile()
    {
        $this->assertFalse(file_exists($this->emptyFilePath));
        $saver = new OneFileTranslationSaver($this->emptyFilePath);
        $saver->save(new TokenCollection('/path/to/source/file'));
        $this->assertTrue(file_exists($this->emptyFilePath));
        $this->assertEquals('[]', file_get_contents($this->emptyFilePath));
    }

    public function testSaveEmptyCollectionToNonEmptyFile()
    {
        $this->assertTrue(file_exists($this->nonEmptyFilePath));
        $saver = new OneFileTranslationSaver($this->nonEmptyFilePath);
        $saver->save(new TokenCollection('/path/to/source/file'));
        $this->assertTrue(file_exists($this->nonEmptyFilePath));
        $this->assertEquals("hello: Ahoj\nworld: svet\n", file_get_contents($this->nonEmptyFilePath));
    }

    public function testSaveNonEmptyCollectionToEmptyFile()
    {
        $this->assertFalse(file_exists($this->emptyFilePath));
        $saver = new OneFileTranslationSaver($this->emptyFilePath);
        $saver->save($this->createCollection());
        $this->assertTrue(file_exists($this->emptyFilePath));
        $this->assertEquals("povodny_text_1: Pôvodný text 1\npovodny_text_2: Pôvodný text 2\n", file_get_contents($this->emptyFilePath));
    }

    public function testSaveNonEmptyCollectionToNonEmptyFile()
    {
        $this->assertTrue(file_exists($this->nonEmptyFilePath));
        $saver = new OneFileTranslationSaver($this->nonEmptyFilePath);
        $saver->save($this->createCollection());
        $this->assertTrue(file_exists($this->nonEmptyFilePath));
        $this->assertEquals("hello: Ahoj\nworld: svet\npovodny_text_1: Pôvodný text 1\npovodny_text_2: Pôvodný text 2\n", file_get_contents($this->nonEmptyFilePath));
    }

    public function testComplexCollection()
    {
        $this->assertFalse(file_exists($this->complexFilePath));
        $saver = new OneFileTranslationSaver($this->complexFilePath);
        $collection = (new PrefixTranslationKeyTokenModifier('pre-prefix.'))->modifyAll($this->createCollection());
        $saver->save($collection);
        $this->assertTrue(file_exists($this->nonEmptyFilePath));
        $this->assertEquals("prefix:\n    povodny_text_1: Pôvodný text 1\n    povodny_text_2: Pôvodný text 2\n\n", file_get_contents($this->complexFilePath));
    }

    private function createCollection(): TokenCollection
    {
        $collection = (new TokenCollection('/absolute/path/to/file.latte'))
            ->addToken(new Token('Pôvodný text 1', '<div class="original-block">Pôvodný text 1</div>'))
            ->addToken(new Token('Pôvodný text 2', '<div class="original-block">Pôvodný text 2</div>'));

        return (new PrefixTranslationKeyTokenModifier('prefix.'))
            ->modifyAll((new LowercaseUnderscoredTokenModifier())->modifyAll($collection));
    }
}

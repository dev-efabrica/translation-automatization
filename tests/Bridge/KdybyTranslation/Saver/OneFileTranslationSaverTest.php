<?php

namespace Efabrica\TranslationsAutomatization\Tests\Bridge\KdybyTranslation\Saver;

use Efabrica\TranslationsAutomatization\Bridge\KdybyTranslation\Saver\OneFileTranslationSaver;
use Efabrica\TranslationsAutomatization\Bridge\KdybyTranslation\Storage\NeonFileStorage;
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

    private $nonEmptyComplexFilePath;

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

        $this->nonEmptyComplexFilePath =  __DIR__ . '/../../../../temp/kdyby-saver-test-non-empty-complex-file.neon';
        if (file_exists($this->nonEmptyComplexFilePath)) {
            unlink($this->nonEmptyComplexFilePath);
        }
        file_put_contents($this->nonEmptyComplexFilePath, "prefix:\n\thello: Ahoj\n\tworld: svet");
    }

    public function testSaveEmptyCollectionToEmptyFile()
    {
        $this->assertFalse(file_exists($this->emptyFilePath));
        $storage = new NeonFileStorage($this->emptyFilePath, '');
        $saver = new OneFileTranslationSaver($storage);
        $saver->save(new TokenCollection('/path/to/source/file'));
        $this->assertTrue(file_exists($this->emptyFilePath));
        $this->assertEquals('[]', file_get_contents($this->emptyFilePath));
    }

    public function testSaveEmptyCollectionToNonEmptyFile()
    {
        $this->assertTrue(file_exists($this->nonEmptyFilePath));
        $storage = new NeonFileStorage($this->nonEmptyFilePath, 'prefix.');
        $saver = new OneFileTranslationSaver($storage);
        $saver->save(new TokenCollection('/path/to/source/file'));
        $this->assertTrue(file_exists($this->nonEmptyFilePath));
        $this->assertEquals("hello: Ahoj\nworld: svet\n", file_get_contents($this->nonEmptyFilePath));
    }

    public function testSaveNonEmptyCollectionToEmptyFile()
    {
        $this->assertFalse(file_exists($this->emptyFilePath));
        $storage = new NeonFileStorage($this->emptyFilePath, 'prefix.');
        $saver = new OneFileTranslationSaver($storage);
        $saver->save($this->createCollection());
        $this->assertTrue(file_exists($this->emptyFilePath));
        $this->assertEquals("povodny_text_1: Pôvodný text 1\npovodny_text_2: Pôvodný text 2\n", file_get_contents($this->emptyFilePath));
    }

    public function testSaveNonEmptyCollectionToNonEmptyFile()
    {
        $this->assertTrue(file_exists($this->nonEmptyFilePath));
        $storage = new NeonFileStorage($this->nonEmptyFilePath, 'prefix.');
        $saver = new OneFileTranslationSaver($storage);
        $saver->save($this->createCollection());
        $this->assertTrue(file_exists($this->nonEmptyFilePath));
        $this->assertEquals("hello: Ahoj\npovodny_text_1: Pôvodný text 1\npovodny_text_2: Pôvodný text 2\nworld: svet\n", file_get_contents($this->nonEmptyFilePath));
    }

    public function testComplexCollectionDefaultIndent()
    {
        $this->assertFalse(file_exists($this->complexFilePath));
        $storage = new NeonFileStorage($this->complexFilePath, 'pre-prefix.');
        $saver = new OneFileTranslationSaver($storage);
        $collection = (new PrefixTranslationKeyTokenModifier('pre-prefix.'))->modifyAll($this->createCollection());
        $saver->save($collection);
        $this->assertTrue(file_exists($this->complexFilePath));
        $this->assertEquals("prefix:\n\tpovodny_text_1: Pôvodný text 1\n\tpovodny_text_2: Pôvodný text 2\n\n", file_get_contents($this->complexFilePath));
    }

    public function testComplexCollectionChangedIndent()
    {
        $this->assertTrue(file_exists($this->nonEmptyComplexFilePath));
        $storage = new NeonFileStorage($this->nonEmptyComplexFilePath, 'pre-prefix.', '    ');
        $saver = new OneFileTranslationSaver($storage);
        $collection = (new PrefixTranslationKeyTokenModifier('pre-prefix.'))->modifyAll($this->createCollection());
        $saver->save($collection);
        $this->assertTrue(file_exists($this->nonEmptyComplexFilePath));
        $this->assertEquals("prefix:\n    hello: Ahoj\n    povodny_text_1: Pôvodný text 1\n    povodny_text_2: Pôvodný text 2\n    world: svet\n\n", file_get_contents($this->nonEmptyComplexFilePath));
    }

    public function testMultipleKeyUsage()
    {
        $collection = $this->createCollection();
        $additionalToken = new Token('Pôvodný text 2 Foo Bar', '<div class="original-block">Pôvodný text 2</div>');
        $additionalToken->changeTranslationKey('prefix.povodny_text_2.foo.bar');
        $collection->addToken($additionalToken);

        $this->assertFalse(file_exists($this->complexFilePath));
        $storage = new NeonFileStorage($this->complexFilePath, 'pre-prefix.', '    ');
        $saver = new OneFileTranslationSaver($storage);
        $collection = (new PrefixTranslationKeyTokenModifier('pre-prefix.'))->modifyAll($collection);
        $saver->save($collection);
        $this->assertTrue(file_exists($this->nonEmptyFilePath));
        $this->assertEquals("prefix:\n    povodny_text_1: Pôvodný text 1\n    povodny_text_2: Pôvodný text 2\n    povodny_text_2.foo.bar: Pôvodný text 2 Foo Bar\n\n", file_get_contents($this->complexFilePath));
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

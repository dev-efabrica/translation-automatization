<?php

namespace Efabrica\TranslationsAutomatization\Tests\TranslationMaker;

use Efabrica\TranslationsAutomatization\Storage\StorageInterface;
use Efabrica\TranslationsAutomatization\TranslationMaker\TranslationMaker;
use Efabrica\TranslationsAutomatization\Translator\TranslatorInterface;
use PHPUnit\Framework\TestCase;

class TranslationMakerTest extends TestCase
{
    public function testAdd()
    {
        $source = $this->createStorage();
        $target = $this->createStorage();
        $translator = $this->createTranslator();

        $maker = new TranslationMaker();
        $this->assertInstanceOf(TranslationMaker::class, $maker->add($source, $target, $translator));
    }

    public function testMake()
    {
        $source = $this->createStorage(['hello', 'How are you?', 'Do not translate this']);
        $target = $this->createStorage();
        $this->assertEquals(['hello', 'How are you?', 'Do not translate this'], $source->load());
        $this->assertEquals([], $target->load());

        $translator = $this->createTranslator(['hello' => 'ahoj', 'How are you?' => 'Ako sa máš?']);

        $maker = new TranslationMaker();
        $this->assertInstanceOf(TranslationMaker::class, $maker->add($source, $target, $translator));
        $this->assertNull($maker->make());

        $this->assertEquals(['hello', 'How are you?', 'Do not translate this'], $source->load());
        $this->assertEquals(['ahoj', 'Ako sa máš?', 'Do not translate this'], $target->load());
    }

    private function createTranslator(array $map = []): TranslatorInterface
    {
        return new class($map) implements TranslatorInterface
        {
            private $map = [];

            public function __construct(array $map)
            {
                $this->map = $map;
            }

            public function translate(array $texts): array
            {
                $translatedTexts = [];
                foreach ($texts as $text) {
                    $translatedTexts[$text] = $this->map[$text] ?? $text;
                }
                return $translatedTexts;
            }
        };
    }

    private function createStorage(array $texts = []): StorageInterface
    {
        return new class($texts) implements StorageInterface
        {
            private $texts = [];

            public function __construct(array $texts)
            {
                $this->texts = $texts;
            }

            public function load(): array
            {
                return $this->texts;
            }

            public function save(array $texts): bool
            {
                $this->texts = $texts;
                return true;
            }
        };
    }
}

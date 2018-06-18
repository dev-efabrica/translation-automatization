<?php

namespace Efabrica\TranslationsAutomatization\Tests\Command\Translator;

use Efabrica\TranslationsAutomatization\Command\Translator\TranslatorConfig;
use Efabrica\TranslationsAutomatization\Storage\StorageInterface;
use Efabrica\TranslationsAutomatization\Translator\TranslatorInterface;
use PHPUnit\Framework\TestCase;

class TranslatorConfigTest extends TestCase
{
    public function testAdd()
    {
        $source = $this->createStorage();
        $target = $this->createStorage();
        $translator = $this->createTranslator();

        $translatorConfig = new TranslatorConfig();
        $this->assertInstanceOf(TranslatorConfig::class, $translatorConfig->add($source, $target, $translator));
    }

    public function testTranslate()
    {
        $source = $this->createStorage(['hello', 'How are you?', 'Do not translate this']);
        $target = $this->createStorage();
        $this->assertEquals(['hello', 'How are you?', 'Do not translate this'], $source->load());
        $this->assertEquals([], $target->load());

        $translator = $this->createTranslator(['hello' => 'ahoj', 'How are you?' => 'Ako sa máš?']);

        $translatorConfig = new TranslatorConfig();
        $this->assertInstanceOf(TranslatorConfig::class, $translatorConfig->add($source, $target, $translator));
        $this->assertNull($translatorConfig->translate());

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

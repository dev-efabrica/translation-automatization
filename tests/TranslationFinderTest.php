<?php

namespace Efabrica\TranslationsAutomatization\Tests;

use Efabrica\TranslationsAutomatization\FileFinder\FileFinder;
use Efabrica\TranslationsAutomatization\Saver\SaverInterface;
use Efabrica\TranslationsAutomatization\TextFinder\RegexTextFinder;
use Efabrica\TranslationsAutomatization\Tokenizer\TokenCollection;
use Efabrica\TranslationsAutomatization\Tokenizer\Tokenizer;
use Efabrica\TranslationsAutomatization\TranslationFinder;
use PHPUnit\Framework\TestCase;

class TranslationFinderTest extends TestCase
{
    public function testNoTokenizers()
    {
        $saver = $saver = $this->createDevNullSaver();
        $translationFinder = new TranslationFinder($saver);
        $this->assertEquals(0, $translationFinder->translate());
    }

    public function testWithOneTokenizer()
    {
        $saver = new class implements SaverInterface
        {
            public function save(TokenCollection $tokenCollection): bool
            {
                return true;
            }
        };

        $fileFinder = new FileFinder([__DIR__ . '/sample-data/latte-templates/first-template'], ['latte']);
        $textFinder = new RegexTextFinder();
        $textFinder->addPattern('/\>([\p{L}\s\.\,\!\?\/\_\-]+)\</siu');
        $tokenizer = new Tokenizer($fileFinder, $textFinder);

        $translationFinder = new TranslationFinder($saver);
        $translationFinder->addTokenizer($tokenizer);
        $this->assertEquals(8, $translationFinder->translate());
    }

    public function testWithMoreTokenizers()
    {
        $saver = $this->createDevNullSaver();

        $fileFinder = new FileFinder([__DIR__ . '/sample-data/latte-templates/first-template'], ['latte']);
        $textFinder = new RegexTextFinder();
        $textFinder->addPattern('/\>([\p{L}\s\.\,\!\?\/\_\-]+)\</siu');
        $tokenizer1 = new Tokenizer($fileFinder, $textFinder);

        $fileFinder = new FileFinder([__DIR__ . '/sample-data/latte-templates/first-template'], ['latte']);
        $textFinder = new RegexTextFinder();
        $textFinder->addPattern('/\}([\p{L}\s\.\,\!\?\/\_\-]+)\{/siu');
        $tokenizer2 = new Tokenizer($fileFinder, $textFinder);

        $translationFinder = new TranslationFinder($saver);
        $translationFinder->addTokenizer($tokenizer1);
        $translationFinder->addTokenizer($tokenizer2);

        $this->assertEquals(11, $translationFinder->translate());
    }

    private function createDevNullSaver()
    {
        return new class implements SaverInterface
        {
            public function save(TokenCollection $tokenCollection): bool
            {
                return true;
            }
        };
    }
}

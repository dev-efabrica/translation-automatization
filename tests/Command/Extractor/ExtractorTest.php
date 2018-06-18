<?php

namespace Efabrica\TranslationsAutomatization\Tests\Command\Extractor;

use Efabrica\TranslationsAutomatization\Command\Extractor\Extractor;
use Efabrica\TranslationsAutomatization\FileFinder\FileFinder;
use Efabrica\TranslationsAutomatization\Saver\SaverInterface;
use Efabrica\TranslationsAutomatization\TextFinder\RegexTextFinder;
use Efabrica\TranslationsAutomatization\Tokenizer\TokenCollection;
use Efabrica\TranslationsAutomatization\Tokenizer\Tokenizer;
use PHPUnit\Framework\TestCase;

class ExtractorTest extends TestCase
{
    public function testNoTokenizers()
    {
        $saver = $saver = $this->createDevNullSaver();
        $extractor = new Extractor($saver);
        $this->assertEquals(0, $extractor->extract());
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

        $fileFinder = new FileFinder([__DIR__ . '/../../sample-data/latte-templates/first-template'], ['latte']);
        $textFinder = new RegexTextFinder();
        $textFinder->addPattern('/\>([\p{L}\s\.\,\!\?\/\_\-]+)\</siu');
        $tokenizer = new Tokenizer($fileFinder, $textFinder);

        $extractor = new Extractor($saver);
        $extractor->addTokenizer($tokenizer);
        $this->assertEquals(8, $extractor->extract());
    }

    public function testWithMoreTokenizers()
    {
        $saver = $this->createDevNullSaver();

        $fileFinder = new FileFinder([__DIR__ . '/../../sample-data/latte-templates/first-template'], ['latte']);
        $textFinder = new RegexTextFinder();
        $textFinder->addPattern('/\>([\p{L}\s\.\,\!\?\/\_\-]+)\</siu');
        $tokenizer1 = new Tokenizer($fileFinder, $textFinder);

        $fileFinder = new FileFinder([__DIR__ . '/../../sample-data/latte-templates/first-template'], ['latte']);
        $textFinder = new RegexTextFinder();
        $textFinder->addPattern('/\}([\p{L}\s\.\,\!\?\/\_\-]+)\{/siu');
        $tokenizer2 = new Tokenizer($fileFinder, $textFinder);

        $extractor = new Extractor($saver);
        $extractor->addTokenizer($tokenizer1);
        $extractor->addTokenizer($tokenizer2);

        $this->assertEquals(11, $extractor->extract());
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

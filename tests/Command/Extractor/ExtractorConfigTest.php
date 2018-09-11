<?php

namespace Efabrica\TranslationsAutomatization\Tests\Command\ExtractorConfig;

use Efabrica\TranslationsAutomatization\Command\Extractor\ExtractorConfig;
use Efabrica\TranslationsAutomatization\FileFinder\FileFinder;
use Efabrica\TranslationsAutomatization\Saver\SaverInterface;
use Efabrica\TranslationsAutomatization\TextFinder\RegexTextFinder;
use Efabrica\TranslationsAutomatization\Tokenizer\TokenCollection;
use Efabrica\TranslationsAutomatization\Tokenizer\Tokenizer;
use PHPUnit\Framework\TestCase;

class ExtractorConfigTest extends TestCase
{
    public function testNoTokenizers()
    {
        $saver = $saver = $this->createDevNullSaver();
        $extractorConfig = new ExtractorConfig($saver);
        $this->assertEmpty($extractorConfig->extract());
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

        $extractorConfig = new ExtractorConfig($saver);
        $extractorConfig->addTokenizer($tokenizer);
        $this->assertNotEmpty($extractorConfig->extract());
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

        $extractorConfig = new ExtractorConfig($saver);
        $extractorConfig->addTokenizer($tokenizer1);
        $extractorConfig->addTokenizer($tokenizer2);

        $this->assertNotEmpty($extractorConfig->extract());
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

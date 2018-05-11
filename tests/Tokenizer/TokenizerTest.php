<?php

namespace Efabrica\TranslationsAutomatization\Tests\Tokenizer;

use Efabrica\TranslationsAutomatization\TokenModifier\LowercaseUnderscoredTokenModifier;
use Efabrica\TranslationsAutomatization\FileFinder\FileFinder;
use Efabrica\TranslationsAutomatization\TextFinder\RegexTextFinder;
use Efabrica\TranslationsAutomatization\Tokenizer\TokenCollection;
use Efabrica\TranslationsAutomatization\Tokenizer\Tokenizer;
use Efabrica\TranslationsAutomatization\TokenModifier\CompositeTokenModifier;
use Efabrica\TranslationsAutomatization\TokenModifier\FalsePositiveRemoverTokenModifier;
use PHPUnit\Framework\TestCase;

class TokenizerTest extends TestCase
{
    public function testOneFileFullTokenize()
    {
        $fileFinder = new FileFinder([__DIR__ . '/../sample-data/latte-templates/first-template'], ['latte']);
        $textFinder = new RegexTextFinder();
        $textFinder->addPattern('/\>([\p{L}\s\.\,\!\?\/\_\-]+)\</siu');
        $textFinder->addPattern('/\}([\p{L}\s\.\,\!\?\/\_\-]+)\{/siu');
        $textFinder->addPattern('/ title=\"([\p{L}\s\.\,\!\?\/\_\-]+)\"/siu');
        $textFinder->addPattern('/ alt=\"([\p{L}\s\.\,\!\?\/\_\-]+)\"/siu');

        $tokenModifier = new LowercaseUnderscoredTokenModifier();
        $tokenizer = new Tokenizer($fileFinder, $textFinder, $tokenModifier);
        $tokenCollections = $tokenizer->tokenize();
        $this->assertNotEmpty($tokenCollections);
        $this->assertCount(1, $tokenCollections);
        foreach ($tokenCollections as $tokenCollection) {
            $this->assertInstanceOf(TokenCollection::class, $tokenCollection);
        }
        $this->assertCount(13, $tokenCollections[0]->getTokens());
    }

    public function testOneFileRemoveFalsePositives()
    {
        $fileFinder = new FileFinder([__DIR__ . '/../sample-data/latte-templates/first-template'], ['latte']);
        $textFinder = new RegexTextFinder();
        $textFinder->addPattern('/\>([\p{L}\s\.\,\!\?\/\_\-]+)\</siu');
        $textFinder->addPattern('/\}([\p{L}\s\.\,\!\?\/\_\-]+)\{/siu');
        $textFinder->addPattern('/ title=\"([\p{L}\s\.\,\!\?\/\_\-]+)\"/siu');
        $textFinder->addPattern('/ alt=\"([\p{L}\s\.\,\!\?\/\_\-]+)\"/siu');

        $tokenModifier = new CompositeTokenModifier();
        $tokenModifier->addTokenModifier(new LowercaseUnderscoredTokenModifier());
        $tokenModifier->addTokenModifier((new FalsePositiveRemoverTokenModifier())->addFalsePositivePattern('/} selected{/', '/selected/'));
        $tokenizer = new Tokenizer($fileFinder, $textFinder, $tokenModifier);
        $tokenCollections = $tokenizer->tokenize();
        $this->assertNotEmpty($tokenCollections);
        $this->assertCount(1, $tokenCollections);
        foreach ($tokenCollections as $tokenCollection) {
            $this->assertInstanceOf(TokenCollection::class, $tokenCollection);
        }
        $this->assertCount(12, $tokenCollections[0]->getTokens());
    }

    public function testOneFileTokenizeTitle()
    {
        $fileFinder = new FileFinder([__DIR__ . '/../sample-data/latte-templates/first-template'], ['latte']);
        $textFinder = new RegexTextFinder();
        $textFinder->addPattern('/ title=\"([\p{L}\s\.\,\!\?\/\_\-]+)\"/siu');

        $tokenModifier = new LowercaseUnderscoredTokenModifier();
        $tokenizer = new Tokenizer($fileFinder, $textFinder, $tokenModifier);
        $tokenCollections = $tokenizer->tokenize();
        $this->assertNotEmpty($tokenCollections);
        $this->assertCount(1, $tokenCollections);
        foreach ($tokenCollections as $tokenCollection) {
            $this->assertInstanceOf(TokenCollection::class, $tokenCollection);
        }
        $this->assertCount(1, $tokenCollections[0]->getTokens());
    }
}

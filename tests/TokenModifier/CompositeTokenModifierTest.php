<?php

namespace Efabrica\TranslationsAutomatization\Tests\TokenModifier;

use Efabrica\TranslationsAutomatization\TokenModifier\BingTranslateTokenModifier;
use Efabrica\TranslationsAutomatization\TokenModifier\CompositeTokenModifier;
use Efabrica\TranslationsAutomatization\TokenModifier\LowercaseUnderscoredTokenModifier;
use Efabrica\TranslationsAutomatization\TokenModifier\PrefixTranslationKeyTokenModifier;
use Efabrica\TranslationsAutomatization\TokenModifier\TokenModifierInterface;

class CompositeTokenModifierTest extends AbstractTokenModifierTest
{
    public function testAddTokenModifier()
    {
        $tokenModifier = new CompositeTokenModifier();
        $this->assertInstanceOf(
            TokenModifierInterface::class,
            $tokenModifier->addTokenModifier(new BingTranslateTokenModifier('sk', 'en'))
        );
    }

    public function testModifyAll()
    {
        $tokenModifier = new CompositeTokenModifier();
        $this->assertInstanceOf(
            TokenModifierInterface::class,
            $tokenModifier->addTokenModifier(new BingTranslateTokenModifier('sk', 'en'))
        );
        $this->assertInstanceOf(
            TokenModifierInterface::class,
            $tokenModifier->addTokenModifier(new LowercaseUnderscoredTokenModifier())
        );
        $this->assertInstanceOf(
            TokenModifierInterface::class,
            $tokenModifier->addTokenModifier(new PrefixTranslationKeyTokenModifier('my_prefix.'))
        );

        $compositeModifierTokenCollection = $tokenModifier->modifyAll($this->tokenCollection);
        $compositeModifierTokens = $compositeModifierTokenCollection->getTokens();

        $oneByOneTokenCollection = $this->createCollection();
        $oneByOneTokenCollection = (new BingTranslateTokenModifier('sk', 'en'))->modifyAll($oneByOneTokenCollection);
        $oneByOneTokenCollection = (new LowercaseUnderscoredTokenModifier())->modifyAll($oneByOneTokenCollection);
        $oneByOneTokenCollection = (new PrefixTranslationKeyTokenModifier('my_prefix.'))->modifyAll($oneByOneTokenCollection);

        $this->assertEquals($compositeModifierTokenCollection->getFilePath(), $oneByOneTokenCollection->getFilePath());
        $this->assertEquals($compositeModifierTokenCollection->getTokens(), $oneByOneTokenCollection->getTokens());
    }
}

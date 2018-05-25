<?php

namespace Efabrica\TranslationsAutomatization\Tests\Bridge\KdybyTranslation\TokenModifier;

use Efabrica\TranslationsAutomatization\Bridge\KdybyTranslation\TokenModifier\ParamsExtractorTokenModifier;
use Efabrica\TranslationsAutomatization\Tests\TokenModifier\AbstractTokenModifierTest;
use Efabrica\TranslationsAutomatization\Tokenizer\Token;
use Efabrica\TranslationsAutomatization\Tokenizer\TokenCollection;

class ParamsExtractorTokenModifierTest extends AbstractTokenModifierTest
{
    public function testDefault()
    {
        $tokenModifier = new ParamsExtractorTokenModifier();
        $tokenCollection = new TokenCollection('/path/to/file');
        $tokenCollection->addToken(new Token('This is my original text with {$param}', '<div>This is my original text with {$param}</div>'));
        $newTokenCollection = $tokenModifier->modifyAll($tokenCollection);

        $this->assertCount(1, $newTokenCollection->getTokens());
        $token = current($newTokenCollection->getTokens());

        $this->assertEquals('This is my original text with {$param}', $token->getOriginalText());
        $this->assertEquals('This is my original text with %param%', $token->getTargetText());
        $this->assertEquals(['param' => '$param'], $token->getTextParameters());
    }

    public function testParamFunction()
    {
        $tokenModifier = new ParamsExtractorTokenModifier();
        $tokenCollection = new TokenCollection('/path/to/file');
        $tokenCollection->addToken(new Token('This is my original text with {strtolower($param->title)}', '<div>This is my original text with {strtolower($param->title)}</div>'));
        $newTokenCollection = $tokenModifier->modifyAll($tokenCollection);

        $this->assertCount(1, $newTokenCollection->getTokens());
        $token = current($newTokenCollection->getTokens());

        $this->assertEquals('This is my original text with {strtolower($param->title)}', $token->getOriginalText());
        $this->assertEquals('This is my original text with %strtolower($param->title)%', $token->getTargetText());
        $this->assertEquals(['strtolower($param->title)' => 'strtolower($param->title)'], $token->getTextParameters());
    }

    public function testParamObject()
    {
        $tokenModifier = new ParamsExtractorTokenModifier();
        $tokenCollection = new TokenCollection('/path/to/file');
        $tokenCollection->addToken(new Token('This is my original text with {$param->title}', '<div>This is my original text with {$param->title}</div>'));
        $newTokenCollection = $tokenModifier->modifyAll($tokenCollection);

        $this->assertCount(1, $newTokenCollection->getTokens());
        $token = current($newTokenCollection->getTokens());

        $this->assertEquals('This is my original text with {$param->title}', $token->getOriginalText());
        $this->assertEquals('This is my original text with %paramTitle%', $token->getTargetText());
        $this->assertEquals(['paramTitle' => '$param->title'], $token->getTextParameters());
    }

    public function testStaticParamsNameMap()
    {
        $tokenModifier = new ParamsExtractorTokenModifier(['$param' => 'my_param']);
        $tokenCollection = new TokenCollection('/path/to/file');
        $tokenCollection->addToken(new Token('This is my original text with {$param}', '<div>This is my original text with {$param}</div>'));
        $newTokenCollection = $tokenModifier->modifyAll($tokenCollection);

        $this->assertCount(1, $newTokenCollection->getTokens());
        $token = current($newTokenCollection->getTokens());

        $this->assertEquals('This is my original text with {$param}', $token->getOriginalText());
        $this->assertEquals('This is my original text with %my_param%', $token->getTargetText());
        $this->assertEquals(['my_param' => '$param'], $token->getTextParameters());
    }

    public function testStaticParamsValueMap()
    {
        $tokenModifier = new ParamsExtractorTokenModifier([], ['$param' => 'my_value']);
        $tokenCollection = new TokenCollection('/path/to/file');
        $tokenCollection->addToken(new Token('This is my original text with {$param}', '<div>This is my original text with {$param}</div>'));
        $newTokenCollection = $tokenModifier->modifyAll($tokenCollection);

        $this->assertCount(1, $newTokenCollection->getTokens());
        $token = current($newTokenCollection->getTokens());

        $this->assertEquals('This is my original text with {$param}', $token->getOriginalText());
        $this->assertEquals('This is my original text with %param%', $token->getTargetText());
        $this->assertEquals(['param' => 'my_value'], $token->getTextParameters());
    }

    public function testStaticParamsNameMapAndStaticParamsValueMap()
    {
        $tokenModifier = new ParamsExtractorTokenModifier(['$param' => 'my_param'], ['$param' => 'my_value']);
        $tokenCollection = new TokenCollection('/path/to/file');
        $tokenCollection->addToken(new Token('This is my original text with {$param}', '<div>This is my original text with {$param}</div>'));
        $newTokenCollection = $tokenModifier->modifyAll($tokenCollection);

        $this->assertCount(1, $newTokenCollection->getTokens());
        $token = current($newTokenCollection->getTokens());

        $this->assertEquals('This is my original text with {$param}', $token->getOriginalText());
        $this->assertEquals('This is my original text with %my_param%', $token->getTargetText());
        $this->assertEquals(['my_param' => 'my_value'], $token->getTextParameters());
    }
}

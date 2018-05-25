<?php

namespace Efabrica\TranslationsAutomatization\Tests\Tokenizer;

use Efabrica\TranslationsAutomatization\Tokenizer\Token;
use PHPUnit\Framework\TestCase;

class TokenTest extends TestCase
{
    public function testConstruct()
    {
        $token = new Token('original text', '<div class="original-block">original text</div>');
        $this->assertEquals('original text', $token->getOriginalText());
        $this->assertEquals('<div class="original-block">original text</div>', $token->getOriginalBlock());
        $this->assertEquals('original text', $token->getTranslationKey());
        $this->assertEquals('original text', $token->getTranslationCode());
        $this->assertEquals('original text', $token->getTargetText());
        $this->assertEmpty($token->getTextParameters());
    }

    public function testChangeTranslationCode()
    {
        $token = new Token('original text', '<div class="original-block">original text</div>');
        $this->assertEquals('original text', $token->getOriginalText());
        $this->assertEquals('<div class="original-block">original text</div>', $token->getOriginalBlock());
        $this->assertEquals('original text', $token->getTranslationKey());
        $this->assertEquals('original text', $token->getTranslationCode());
        $this->assertEquals('original text', $token->getTargetText());
        $this->assertEmpty($token->getTextParameters());

        $this->assertInstanceOf(Token::class, $token->changeTranslationCode('new_translation_code'));
        $this->assertEquals('original text', $token->getOriginalText());
        $this->assertEquals('<div class="original-block">original text</div>', $token->getOriginalBlock());
        $this->assertEquals('original text', $token->getTranslationKey());
        $this->assertEquals('new_translation_code', $token->getTranslationCode());
        $this->assertEquals('original text', $token->getTargetText());
        $this->assertEmpty($token->getTextParameters());
    }

    public function testChangeTargetText()
    {
        $token = new Token('original text', '<div class="original-block">original text</div>');
        $this->assertEquals('original text', $token->getOriginalText());
        $this->assertEquals('<div class="original-block">original text</div>', $token->getOriginalBlock());
        $this->assertEquals('original text', $token->getTranslationKey());
        $this->assertEquals('original text', $token->getTranslationCode());
        $this->assertEquals('original text', $token->getTargetText());
        $this->assertEmpty($token->getTextParameters());

        $this->assertInstanceOf(Token::class, $token->changeTargetText('new_target_text'));
        $this->assertEquals('original text', $token->getOriginalText());
        $this->assertEquals('<div class="original-block">original text</div>', $token->getOriginalBlock());
        $this->assertEquals('original text', $token->getTranslationKey());
        $this->assertEquals('original text', $token->getTranslationCode());
        $this->assertEquals('new_target_text', $token->getTargetText());
        $this->assertEmpty($token->getTextParameters());
    }

    public function testSetTextParameters()
    {
        $token = new Token('original text', '<div class="original-block">original text</div>');
        $this->assertEquals('original text', $token->getOriginalText());
        $this->assertEquals('<div class="original-block">original text</div>', $token->getOriginalBlock());
        $this->assertEquals('original text', $token->getTranslationKey());
        $this->assertEquals('original text', $token->getTranslationCode());
        $this->assertEquals('original text', $token->getTargetText());
        $this->assertEmpty($token->getTextParameters());

        $this->assertInstanceOf(Token::class, $token->setTextParameters(['text_parameter_1' => 'Text parameter 1', 'text_parameter_2' => 'Text parameter 2']));
        $this->assertEquals('original text', $token->getOriginalText());
        $this->assertEquals('<div class="original-block">original text</div>', $token->getOriginalBlock());
        $this->assertEquals('original text', $token->getTranslationKey());
        $this->assertEquals('original text', $token->getTranslationCode());
        $this->assertEquals('original text', $token->getTargetText());
        $this->assertEquals(['text_parameter_1' => 'Text parameter 1', 'text_parameter_2' => 'Text parameter 2'], $token->getTextParameters());
    }
}

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
        $this->assertEquals('original text', $token->getTranslationCode());
    }

    public function testChangeTranslationCode()
    {
        $token = new Token('original text', '<div class="original-block">original text</div>');
        $this->assertEquals('original text', $token->getOriginalText());
        $this->assertEquals('<div class="original-block">original text</div>', $token->getOriginalBlock());
        $this->assertEquals('original text', $token->getTranslationCode());

        $this->assertInstanceOf(Token::class, $token->changeTranslationCode('new_translation_code'));
        $this->assertEquals('original text', $token->getOriginalText());
        $this->assertEquals('<div class="original-block">original text</div>', $token->getOriginalBlock());
        $this->assertEquals('new_translation_code', $token->getTranslationCode());
    }
}

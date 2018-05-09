<?php

namespace Efabrica\TranslationsAutomatization\Tests\Tokenizer;

use Efabrica\TranslationsAutomatization\Tokenizer\Token;
use Efabrica\TranslationsAutomatization\Tokenizer\TokenCollection;
use PHPUnit\Framework\TestCase;

class TokenCollectionTest extends TestCase
{
    public function testConstruct()
    {
        $tokenCollection = new TokenCollection('file-path');
        $this->assertEquals('file-path', $tokenCollection->getFilePath());
    }

    public function testAddToken()
    {
        $tokenCollection = new TokenCollection('file-path');
        $this->assertEquals('file-path', $tokenCollection->getFilePath());

        $this->assertCount(0, $tokenCollection->getTokens());
        $this->assertInstanceOf(TokenCollection::class, $tokenCollection->addToken(new Token('original text', 'original block')));
        $this->assertCount(1, $tokenCollection->getTokens());
        foreach ($tokenCollection->getTokens() as $token) {
            $this->assertInstanceOf(Token::class, $token);
        }
    }
}

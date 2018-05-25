<?php

namespace Efabrica\TranslationsAutomatization\Tests\TokenModifier;

use Efabrica\TranslationsAutomatization\Tokenizer\Token;
use Efabrica\TranslationsAutomatization\Tokenizer\TokenCollection;
use PHPUnit\Framework\TestCase;

abstract class AbstractTokenModifierTest extends TestCase
{
    /** @var TokenCollection */
    protected $tokenCollection;

    public function setUp()
    {
        $this->tokenCollection = $this->createCollection();
    }

    protected function copyTokens(array $tokens): array
    {
        $newTokens = [];
        foreach ($tokens as $key => $token) {
            $newTokens[$key] = clone $token;
        }
        return $newTokens;
    }

    protected function createCollection(): TokenCollection
    {
        return (new TokenCollection('/absolute/path/to/file.latte'))
            ->addToken(new Token('Pôvodný text 1', '<div class="original-block">Pôvodný text 1</div>'))
            ->addToken(new Token('Pôvodný text 2', '<div class="original-block">Pôvodný text 2</div>'))
            ->addToken(new Token('Premenná {$variable}', '<div class="original-block">Premenná {$variable}</div>'))
        ;
    }
}

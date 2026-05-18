<?php

namespace Efabrica\TranslationsAutomatization\Tests\TokenModifier;

use Efabrica\TranslationsAutomatization\Tokenizer\Token;
use Efabrica\TranslationsAutomatization\Tokenizer\TokenCollection;
use Efabrica\TranslationsAutomatization\Translator\TranslatorInterface;
use PHPUnit\Framework\TestCase;

abstract class AbstractTokenModifierTest extends TestCase
{
    /** @var TokenCollection */
    protected $tokenCollection;

    protected function setUp(): void
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
            ->addToken(new Token(" \t\nJednoduché <strong>HTML</strong>\t\n ", '<div class="original-block">Jednoduché <strong>HTML</strong></div>'))
        ;
    }

    protected function createTranslator(array $map = []): TranslatorInterface
    {
        return new class($map) implements TranslatorInterface
        {
            private array $map;

            public function __construct(array $map)
            {
                $this->map = $map;
            }

            public function translate(array $keys): array
            {
                $translations = [];
                foreach ($keys as $key) {
                    $translations[$key] = $this->map[$key] ?? ('translated:' . $key);
                }

                return $translations;
            }
        };
    }
}

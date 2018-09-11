<?php

namespace Efabrica\TranslationsAutomatization\Command\Extractor;

use Closure;
use Efabrica\TranslationsAutomatization\Saver\SaverInterface;
use Efabrica\TranslationsAutomatization\Tokenizer\TokenCollection;
use Efabrica\TranslationsAutomatization\Tokenizer\Tokenizer;

class ExtractorConfig
{
    private $saver;

    private $tokenizers = [];

    public function __construct(SaverInterface $saver)
    {
        $this->saver = $saver;
    }

    public function addTokenizer(Tokenizer $tokenizer): ExtractorConfig
    {
        $this->tokenizers[] = $tokenizer;
        return $this;
    }

    /**
     * @return TokenCollection[]
     */
    public function extract(): array
    {
        $tokenCollections = [];
        foreach ($this->tokenizers as $tokenizer) {
            $tokenCollections = array_merge($tokenCollections, $tokenizer->tokenize());
        }
        return $tokenCollections;
    }

    public function process(TokenCollection $tokenCollection, Closure $callback): void
    {
        // tento kod by som mohol dat do nejakeho file updatera
        $content = file_get_contents($tokenCollection->getFilePath());
        $newTexts = [];
        foreach ($tokenCollection->getTokens() as $token) {
            $newTexts[$token->getOriginalBlock()] = str_replace($token->getOriginalText(), $token->getTranslationCode(), $token->getOriginalBlock());
            $callback($token);
        }
        $content = str_replace(array_keys($newTexts), array_values($newTexts), $content);
        file_put_contents($tokenCollection->getFilePath(), $content);
        $this->saver->save($tokenCollection);
    }
}

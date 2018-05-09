<?php

namespace Efabrica\TranslationsAutomatization;

use Efabrica\TranslationsAutomatization\Saver\SaverInterface;
use Efabrica\TranslationsAutomatization\Tokenizer\Tokenizer;

class TranslationFinder
{
    private $saver;

    private $tokenizers = [];

    public function __construct(SaverInterface $saver)
    {
        $this->saver = $saver;
    }

    public function addTokenizer(Tokenizer $tokenizer): TranslationFinder
    {
        $this->tokenizers[] = $tokenizer;
        return $this;
    }

    public function translate()
    {
        foreach ($this->tokenizers as $tokenizer) {
            foreach ($tokenizer->tokenize() as $tokenCollection) {
                // tento kod by som mohol dat do nejakeho file updatera
                $content = file_get_contents($tokenCollection->getFilePath());
                $newTexts = [];
                foreach ($tokenCollection->getTokens() as $token) {
                    $newTexts[$token->getOriginalBlock()] = str_replace($token->getOriginalText(), $token->getTranslationCode(), $token->getOriginalBlock());
                }
                $content = str_replace(array_keys($newTexts), array_values($newTexts), $content);
                file_put_contents($tokenCollection->getFilePath(), $content);

                $this->saver->save($tokenCollection);
            }
        }
    }
}

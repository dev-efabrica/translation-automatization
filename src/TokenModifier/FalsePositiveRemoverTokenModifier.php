<?php

namespace Efabrica\TranslationsAutomatization\TokenModifier;

use Efabrica\TranslationsAutomatization\Tokenizer\Token;
use Efabrica\TranslationsAutomatization\Tokenizer\TokenCollection;

class FalsePositiveRemoverTokenModifier implements TokenModifierInterface
{
    private $falsePositivePatterns = [];

    public function addFalsePositivePattern(string $blockPattern, string $textPattern)
    {
        $this->falsePositivePatterns[] = [
            'block_pattern' => $blockPattern,
            'text_pattern' => $textPattern,
        ];
        return $this;
    }

    public function modifyAll(TokenCollection $tokenCollection): TokenCollection
    {
        $newTokenCollection = new TokenCollection($tokenCollection->getFilePath());
        foreach ($tokenCollection->getTokens() as $token) {
            $this->addTokensToNewCollection($newTokenCollection, $token);
        }
        return $newTokenCollection;
    }

    private function addTokensToNewCollection(TokenCollection $newTokenCollection, Token $token): void
    {
        foreach ($this->falsePositivePatterns as $falsePositivePattern) {
            if (!preg_match($falsePositivePattern['block_pattern'], $token->getOriginalBlock()) || !preg_match($falsePositivePattern['text_pattern'], $token->getOriginalText())) {
                $newTokenCollection->addToken($token);
            }
        }
    }
}

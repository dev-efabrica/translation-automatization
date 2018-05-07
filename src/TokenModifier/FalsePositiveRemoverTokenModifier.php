<?php

namespace Efabrica\TranslationsAutomatization\TokenModifier;

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
            $originalBlock = $token->getOriginalBlock();
            $originalText = $token->getOriginalText();

            foreach ($this->falsePositivePatterns as $falsePositivePattern) {
                if (preg_match($falsePositivePattern['block_pattern'], $originalBlock) && preg_match($falsePositivePattern['text_pattern'], $originalText)) {
                    continue;
                }
                $newTokenCollection->addToken($token);
            }
        }
        return $newTokenCollection;
    }
}

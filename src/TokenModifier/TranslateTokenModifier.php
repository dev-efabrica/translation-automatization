<?php

namespace Efabrica\TranslationsAutomatization\TokenModifier;

use Efabrica\TranslationsAutomatization\Tokenizer\TokenCollection;
use Efabrica\TranslationsAutomatization\Translator\BingTranslator;
use Efabrica\TranslationsAutomatization\Translator\TranslatorInterface;

class TranslateTokenModifier implements TokenModifierInterface
{
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function modifyAll(TokenCollection $tokenCollection): TokenCollection
    {
        $oldKeys = [];
        foreach ($tokenCollection->getTokens() as $token) {
            $oldKeys[] = $token->getTranslationKey();
        }

        if (empty($oldKeys)) {
            return $tokenCollection;
        }

        $oldToNewKeys = $this->translator->translate($oldKeys);
        foreach ($tokenCollection->getTokens() as $token) {
            $token->changeTranslationKey($oldToNewKeys[$token->getTranslationKey()] ?? $token->getTranslationKey());
        }
        return $tokenCollection;
    }
}

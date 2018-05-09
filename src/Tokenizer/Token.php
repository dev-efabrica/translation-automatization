<?php

namespace Efabrica\TranslationsAutomatization\Tokenizer;

class Token
{
    private $originalText;

    private $originalBlock;

    private $translationKey;

    private $translationCode;

    public function __construct(string $originalText, string $originalBlock)
    {
        $this->originalText = $originalText;
        $this->originalBlock = $originalBlock;
        $this->translationKey = $originalText;
        $this->translationCode = $originalText;
    }

    public function getOriginalText(): string
    {
        return $this->originalText;
    }

    public function getOriginalBlock(): string
    {
        return $this->originalBlock;
    }

    public function getTranslationKey(): string
    {
        return $this->translationKey;
    }

    public function changeTranslationKey(string $newTranslationKey): Token
    {
        $this->translationKey = $newTranslationKey;
        return $this;
    }

    public function getTranslationCode(): string
    {
        return $this->translationCode;
    }

    public function changeTranslationCode(string $newTranslationCode): Token
    {
        $this->translationCode = $newTranslationCode;
        return $this;
    }
}

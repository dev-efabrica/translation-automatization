<?php

namespace Efabrica\TranslationsAutomatization\Tokenizer;

class Token
{
    private $originalText;

    private $originalBlock;

    private $translationKey;

    private $translationCode;

    private $targetText;

    private $textParameters = [];

    public function __construct(string $originalText, string $originalBlock)
    {
        $this->originalText = $originalText;
        $this->originalBlock = $originalBlock;
        $this->translationKey = $originalText;
        $this->translationCode = $originalText;
        $this->targetText = $originalText;
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

    public function getTargetText(): string
    {
        return $this->targetText;
    }

    public function changeTargetText(string $newTargetText): Token
    {
        $this->targetText = $newTargetText;
        return $this;
    }

    public function getTextParameters(): array
    {
        return $this->textParameters;
    }

    public function setTextParameters(array $textParameters): Token
    {
        $this->textParameters = $textParameters;
        return $this;
    }
}

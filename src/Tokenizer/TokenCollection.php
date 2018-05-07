<?php

namespace Efabrica\TranslationsAutomatization\Tokenizer;

class TokenCollection
{
    private $filePath;

    private $tokens = [];

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function addToken(Token $token)
    {
        $this->tokens[] = $token;
        return $this;
    }

    /**
     * @return Token[]
     */
    public function getTokens(): array
    {
        return $this->tokens;
    }
}

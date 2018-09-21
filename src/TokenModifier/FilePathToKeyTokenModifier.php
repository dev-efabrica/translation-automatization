<?php

namespace Efabrica\TranslationsAutomatization\TokenModifier;

use Efabrica\TranslationsAutomatization\Tokenizer\TokenCollection;

class FilePathToKeyTokenModifier implements TokenModifierInterface
{
    private $basePath;

    private $excludeDirectoryNames;

    public function __construct(string $basePath, array $excludeDirectoryNames = [])
    {
        $this->basePath = rtrim($basePath, '/');
        $this->excludeDirectoryNames = array_map('strtolower', $excludeDirectoryNames);
    }

    public function modifyAll(TokenCollection $tokenCollection): TokenCollection
    {
        $pathParts = array_unique(explode('/', str_replace($this->basePath . '/', '', pathinfo($tokenCollection->getFilePath(), PATHINFO_DIRNAME) . '/' . pathinfo($tokenCollection->getFilePath(), PATHINFO_FILENAME))));
        foreach ($tokenCollection->getTokens() as $token) {
            $newKeyParts = array_filter(array_map('strtolower', $pathParts), function ($pathPart) {
                return !in_array($pathPart, $this->excludeDirectoryNames);
            });
            $newKeyParts[] = $token->getTranslationKey();
            $token->changeTranslationKey(implode('.', $newKeyParts));
        }
        return $tokenCollection;
    }
}

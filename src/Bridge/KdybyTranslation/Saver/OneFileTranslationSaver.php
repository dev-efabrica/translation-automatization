<?php

namespace Efabrica\TranslationsAutomatization\Bridge\KdybyTranslation\Saver;

use Efabrica\TranslationsAutomatization\Saver\SaverInterface;
use Efabrica\TranslationsAutomatization\Storage\StorageInterface;
use Efabrica\TranslationsAutomatization\Tokenizer\TokenCollection;

class OneFileTranslationSaver implements SaverInterface
{
    private $storage;

    public function __construct(StorageInterface $storage)
    {
        $this->storage = $storage;
    }

    public function save(TokenCollection $tokenCollection): bool
    {
        $texts = [];
        foreach ($tokenCollection->getTokens() as $token) {
            $texts[$token->getTranslationKey()] = $token->getTargetText();
        }
        return $this->storage->save($texts);
    }
}

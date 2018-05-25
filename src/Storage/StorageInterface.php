<?php

namespace Efabrica\TranslationsAutomatization\Storage;

interface StorageInterface
{
    public function load(): array;

    public function save(array $texts): bool;
}

<?php

namespace Efabrica\TranslationsAutomatization\Bridge\KdybyTranslation\Storage;

use Efabrica\TranslationsAutomatization\Storage\StorageInterface;
use Nette\Neon\Encoder;
use Nette\Neon\Neon;

class NeonFileStorage implements StorageInterface
{
    private $filePath;

    private $indent;

    public function __construct(string $filePath, string $indent = "\t")
    {
        $this->filePath = $filePath;
        $this->indent = $indent;
    }

    public function load(): array
    {
        if (!file_exists($this->filePath)) {
            return [];
        }

        $texts = Neon::decode(file_get_contents($this->filePath));
        $this->flatten($texts);
        return $texts;
    }

    public function save(array $texts): bool
    {
        $texts = array_merge($this->load(), $texts);
        ksort($texts);

        $translations = [];
        foreach ($texts as $key => $value) {
            $translationKeyParts = explode('.', $key);
            $translations = $this->addToTranslations($translations, $translationKeyParts, $value);
        }
        return (bool) file_put_contents($this->filePath, str_replace("\t", $this->indent, Neon::encode($translations, Encoder::BLOCK)));
    }

    private function flatten(array &$translations, array $subnode = null, $path = null)
    {
        if ($subnode === null) {
            $subnode = &$translations;
        }
        foreach ($subnode as $key => $value) {
            if (is_array($value)) {
                $nodePath = $path ? $path . '.' . $key : $key;
                $this->flatten($translations, $value, $nodePath);
                if ($path === null) {
                    unset($translations[$key]);
                }
            } elseif ($path !== null) {
                $translations[$path . '.' . $key] = $value;
            }
        }
    }

    private function addToTranslations(array $translations, array $translationKeyParts, string $text): array
    {
        $keyPart = array_shift($translationKeyParts);
        if (count($translationKeyParts) === 0) {
            $translations[$keyPart] = $text;
            return $translations;
        }
        if (isset($translations[$keyPart]) && is_string($translations[$keyPart])) {
            $translations[$keyPart . '.' . implode('.', $translationKeyParts)] = $text;
            return $translations;
        }
        $translations[$keyPart] = $this->addToTranslations($translations[$keyPart] ?? [], $translationKeyParts, $text);
        return $translations;
    }
}

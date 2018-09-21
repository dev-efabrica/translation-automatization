<?php

namespace Efabrica\TranslationsAutomatization\Bridge\KdybyTranslation\Storage;

use Efabrica\TranslationsAutomatization\Storage\StorageInterface;
use Nette\Neon\Encoder;
use Nette\Neon\Neon;

class NeonFileStorage implements StorageInterface
{
    private $filePath;

    private $prefix;

    private $indent;

    public function __construct(string $filePath, string $prefix, string $indent = "\t")
    {
        $this->filePath = $filePath;
        $this->prefix = $prefix;
        $this->indent = $indent;
    }

    public function load(): array
    {
        if (!file_exists($this->filePath)) {
            return [];
        }
        $content = trim(file_get_contents($this->filePath) ?: '');
        if ($content === '') {
            return [];
        }
        $texts = Neon::decode($content);
        return $this->arrayToFlat($texts);
    }

    public function save(array $texts): bool
    {
        $texts = array_merge($this->load(), $texts);
        ksort($texts);

        $translations = [];
        foreach ($texts as $key => $value) {
            $key = (string) $key;
            $key = strpos($key, $this->prefix) === 0 ? substr($key, strlen($this->prefix)) : $key;
            $translationKeyParts = explode('.', $key);
            $translations = $this->addToTranslations($translations, $translationKeyParts, $value);
        }
        $dirname = pathinfo($this->filePath, PATHINFO_DIRNAME);
        if (!is_dir($dirname)) {
            mkdir($dirname, 0777, true);
        }
        return (bool) file_put_contents($this->filePath, str_replace("\t", $this->indent, Neon::encode($translations, Encoder::BLOCK)));
    }

    private function arrayToFlat(array $texts, array $translations = []): array
    {
        foreach ($texts as $key => $value) {
            if (!is_array($value)) {
                $translations[$this->prefix . $key] = $value;
                continue;
            }
            $translations = $this->arrayToFlat($this->shiftArrayKey($value, $key), $translations);
        }
        return $translations;
    }

    private function shiftArrayKey(array $texts, string $parentKey)
    {
        $newTexts = [];
        foreach ($texts as $key => $value) {
            $newTexts[$parentKey . '.' . $key] = $value;
        }
        return $newTexts;
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

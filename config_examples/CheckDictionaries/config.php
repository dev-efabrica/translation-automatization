<?php

use Efabrica\TranslationsAutomatization\Bridge\KdybyTranslation\Storage\NeonFileStorage;
use Efabrica\TranslationsAutomatization\Command\CheckDictionaries\CheckDictionariesConfig;
use Efabrica\TranslationsAutomatization\Exception\InvalidConfigInstanceReturnedException;
use Symfony\Component\Finder\Finder;

/**
 * Usage:
 * --params="basePath=/your/base/path&fallbacks[cs_CZ][]=sk_SK&languages[]=sk_SK&languages[]=en_US"
 */

if (!isset($basePath)) {
    return new InvalidConfigInstanceReturnedException('$basePath is not set. Use --params="basePath=/your/base/path"');
}

$container = require $basePath . '/app/bootstrap.php';
$containerParameters = $container->getParameters();
$translationDirs = $containerParameters['translation']['dirs'] ?? [];

$defaultTranslationDir = $basePath . '/app/lang';
if (file_exists($defaultTranslationDir)) {
    $translationDirs[] = $basePath . '/app/lang';
}

if ($translationDirs === []) {
    return new CheckDictionariesConfig([]);
}

$files = Finder::create()->in($translationDirs);
$dictionaries = [];
foreach ($files as $file) {
    $filePath = (string)$file;
    $info = pathinfo($filePath);
    list($prefix, $lang,) = explode('.', $info['basename'], 3);
    if (isset($languages) && !in_array($lang, $languages)) {
        continue;
    }
    $storage = new NeonFileStorage($filePath, $prefix . '.', '    ');
    $dictionaries[$lang] = array_merge($dictionaries[$lang] ?? [], $storage->load());
}

foreach ($fallbacks ?? [] as $lang => $fallbackLangs) {
    if (!isset($dictionaries[$lang])) {
        continue;
    }
    foreach ($fallbackLangs as $fallbackLang) {
        foreach ($dictionaries[$fallbackLang] ?? [] as $key => $value) {
            if (!isset($dictionaries[$lang][$key])) {
                $dictionaries[$lang][$key] = $value;
            }
        }
    }
}
return new CheckDictionariesConfig($dictionaries);

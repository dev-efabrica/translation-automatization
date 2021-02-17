<?php

use Efabrica\TranslationsAutomatization\Bridge\KdybyTranslation\Storage\NeonFileStorage;
use Efabrica\TranslationsAutomatization\Command\CheckDictionaries\CheckDictionariesConfig;
use Efabrica\TranslationsAutomatization\Exception\InvalidConfigInstanceReturnedException;
use Symfony\Component\Finder\Finder;

/**
 * Usage:
 * --params="basePath=/your/base/path&fallbacks[cs_CZ][]=sk_SK"
 */

if (!isset($basePath)) {
    return new InvalidConfigInstanceReturnedException('$basePath is not set. Use --params="basePath=/your/base/path"');
}

$container = require $basePath . '/app/bootstrap.php';
$containerParameters = $container->getParameters();
$translationDirs = $containerParameters['translation']['dirs'] ?? [];

$files = Finder::create()->in($translationDirs);
$dictionaryStorages = [];
foreach ($files as $file) {
    $filePath = (string)$file;
    $info = pathinfo($filePath);
    list($prefix, $lang,) = explode('.', $info['basename'], 3);
    $dictionaryStorages[$lang][$info['dirname'] . '/' . $prefix] = new NeonFileStorage($filePath, $prefix . '.', '    ');
}

foreach ($fallbacks ?? [] as $lang => $fallbackLangs) {
    foreach ($fallbackLangs as $fallbackLang) {
        foreach ($dictionaryStorages[$fallbackLang] ?? [] as $module => $dictionary) {
            if (!isset($dictionaryStorages[$lang][$module])) {
                $dictionaryStorages[$lang][$module] = $dictionary;
            }
        }
    }
}
return new CheckDictionariesConfig($dictionaryStorages);

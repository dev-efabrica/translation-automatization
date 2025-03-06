<?php

use Efabrica\TranslationsAutomatization\Bridge\KdybyTranslation\Storage\NeonFileStorage;
use Efabrica\TranslationsAutomatization\Command\CheckDictionaries\CheckDictionariesConfig;
use Efabrica\TranslationsAutomatization\Exception\InvalidConfigInstanceReturnedException;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\Finder;

/**
 * Usage:
 * --params="basePath=/your/base/path&bootstrap=relativePathToBootstrap&fallbacks[cs_CZ][]=sk_SK&languages[]=sk_SK&languages[]=en_US"
 */

if (!isset($basePath)) {
    return new InvalidConfigInstanceReturnedException('$basePath is not set. Use --params="basePath=/your/base/path"');
}

$bootstrap = $bootstrap ?? 'app/bootstrap.php';
$bootstrapPath = $basePath . '/' . $bootstrap;

$translationDirsToCheck = [];
if (file_exists($bootstrapPath)) {
    $container = require $bootstrapPath;
    $translationDirsToCheck = method_exists($container, 'getParameter') ? ($container->getParameter('translation')['dirs'] ?? []) : ($container->getParameters()['translation']['dirs'] ?? []);
}

$translationDirsToCheck[] = $basePath . '/lang';
foreach (['app', 'src'] as $srcDir) {
    try {
        $modulesLangFinder = Finder::create()
            ->directories()
            ->name('lang')
            ->in($basePath . '/' . $srcDir);
    } catch (DirectoryNotFoundException $e) {
        continue;
    }
    foreach ($modulesLangFinder as $dir) {
        $translationDirsToCheck[] = $dir->getRealPath();
    }
}

$translationDirs = [];
foreach ($translationDirsToCheck as $translationDirToCheck) {
    $path = realpath($translationDirToCheck);
    if ($path === false || !is_dir($path) || in_array($path, $translationDirs)) {
        continue;
    }
    $translationDirs[] = $path;
}

if ($translationDirs === []) {
    return new CheckDictionariesConfig([]);
}

try {
    $files = Finder::create()->in($translationDirs);
} catch (DirectoryNotFoundException $e) {
    $files = [];
}
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

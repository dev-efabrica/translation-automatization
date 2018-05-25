<?php

use Efabrica\TranslationsAutomatization\Bridge\KdybyTranslation\Saver\OneFileTranslationSaver;
use Efabrica\TranslationsAutomatization\Bridge\KdybyTranslation\TokenModifier\LatteTokenModifier;
use Efabrica\TranslationsAutomatization\Bridge\KdybyTranslation\TokenModifier\ParamsExtractorTokenModifier;
use Efabrica\TranslationsAutomatization\FileFinder\FileFinder;
use Efabrica\TranslationsAutomatization\TextFinder\RegexTextFinder;
use Efabrica\TranslationsAutomatization\Tokenizer\Tokenizer;
use Efabrica\TranslationsAutomatization\TokenModifier\BingTranslateTokenModifier;
use Efabrica\TranslationsAutomatization\TokenModifier\FalsePositiveRemoverTokenModifier;
use Efabrica\TranslationsAutomatization\TokenModifier\FilePathToKeyTokenModifier;
use Efabrica\TranslationsAutomatization\TokenModifier\LowercaseUnderscoredTokenModifier;
use Efabrica\TranslationsAutomatization\TokenModifier\PrefixTranslationKeyTokenModifier;
use Efabrica\TranslationsAutomatization\TranslationFinder;

require_once __DIR__ . '/../../vendor/autoload.php';

$basePath = rtrim($argv[1] ?? __DIR__, '/');

$saver = new OneFileTranslationSaver($basePath . '/app/lang/dictionary.sk_SK.neon', '    ');
//$saver = new OneFileTranslationSaver($basePath . '/app/lang/dictionary.en_US.neon', '    ');
$translationFinder = new TranslationFinder($saver);

$fileFinder = new FileFinder([$basePath . '/app'], ['latte']);

$textFinder = new RegexTextFinder();

$textFinder->addPattern('/title=\"([\p{L}\h\.\,\!\?\/\_\-]+)\"/siu');
$textFinder->addPattern('/alt=\"([\p{L}\h\.\,\!\?\/\_\-]+)\"/siu');
$textFinder->addPattern('/placeholder=\"([\p{L}\h\.\,\!\?\/\_\-]+)\"/siu');
$textFinder->addPattern('/data-modal-title-small=\"([\p{L}\h\.\,\!\?\/\_\-]+)\"/siu');
$textFinder->addPattern('/data-modal-body=\"([\p{L}\h\.\,\!\?\/\_\-\$\<\>\{\}\(\)\']+)\"/siu');

$textFinder->addPattern('/\{\_(.*?)\}/', null);
$textFinder->addPattern('/[\>\}](\s)*\{if \$(.*?)\}(\s)*[\<\{]/iu', null);
$textFinder->addPattern('/[\>\}](\s)*\{\$(.*?)\}(\s)*[\<\{]/iu', null);
$textFinder->addPattern('/\{\/if\}/u', null);
$textFinder->addPattern('/\{else\}/u', null);
$textFinder->addPattern('/\{\/ifCurrent\}/u', null);

$textFinder->addPattern('/[^-]\>([\p{L}\h\.\,\!\?\/\_\-\$\>\{\}\(\)\']+)\</siu');
$textFinder->addPattern('/\}([\p{L}\h\.\,\!\?\/\_\-\$\>\{\}\(\)\']+)\{/siu');

$tokenizer = new Tokenizer($fileFinder, $textFinder);
$tokenizer->addTokenModifier(new ParamsExtractorTokenModifier(
    [
        'count($channels)' => 'channelsCount',
        'date(\'j.n.Y\', strtotime($actualDate))' => 'actualDate',
    ]
));

$tokenizer->addTokenModifier(new BingTranslateTokenModifier('sk', 'en'));
$tokenizer->addTokenModifier(new LowercaseUnderscoredTokenModifier());
$tokenizer->addTokenModifier(new FilePathToKeyTokenModifier($basePath, ['presenters', 'templates', 'components', 'modules']));
$tokenizer->addTokenModifier(new PrefixTranslationKeyTokenModifier('dictionary.'));
$tokenizer->addTokenModifier(new LatteTokenModifier());
$tokenizer->addTokenModifier((new FalsePositiveRemoverTokenModifier())->addFalsePositivePattern('/} selected{/', '/selected/'));

$translationFinder->addTokenizer($tokenizer);
$translationFinder->translate();

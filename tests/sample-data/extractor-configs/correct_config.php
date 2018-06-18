<?php

use Efabrica\TranslationsAutomatization\Command\Extractor\ExtractorConfig;
use Efabrica\TranslationsAutomatization\Saver\SaverInterface;
use Efabrica\TranslationsAutomatization\Tokenizer\TokenCollection;

$saver = new class implements SaverInterface {
    public function save(TokenCollection $tokenCollection): bool
    {
        return true;
    }
};
$extractorConfig = new ExtractorConfig($saver);
return $extractorConfig;

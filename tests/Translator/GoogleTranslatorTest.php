<?php

namespace Efabrica\TranslationsAutomatization\Tests\Translator;

use Efabrica\TranslationsAutomatization\Translator\GoogleTranslator;
use PHPUnit\Framework\TestCase;

class GoogleTranslatorTest extends TestCase
{
    public function testSimple()
    {
        $googleTranslator = new GoogleTranslator('sk', 'en');
        $output = $googleTranslator->translate(['ahoj', 'mama', 'dom', 'Ako sa mas?']);
        $this->assertArrayHasKey('ahoj', $output);
        $this->assertArrayHasKey('mama', $output);
        $this->assertArrayHasKey('dom', $output);
    }
}

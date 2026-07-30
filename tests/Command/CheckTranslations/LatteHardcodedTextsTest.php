<?php

namespace Efabrica\TranslationsAutomatization\Tests\Command\CheckTranslations;

use Efabrica\TranslationsAutomatization\Command\CheckTranslations\LatteTranslationAnalyzer;
use PHPUnit\Framework\TestCase;
use SplFileInfo;

class LatteHardcodedTextsTest extends TestCase
{
    /**
     * @var string[]
     */
    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
        $this->temporaryFiles = [];
    }

    public function testDetectsVisibleHardcodedTextInStrongTag(): void
    {
        $file = $this->createTemplate('<strong>Typ:</strong>');

        $results = (new LatteTranslationAnalyzer())->findHardcodedTexts(new SplFileInfo($file));

        $this->assertCount(1, $results);
        $this->assertSame('Typ:', $results[0]['text']);
        $this->assertSame(1, $results[0]['line']);
    }

    public function testIgnoresTextWrappedInTranslationMacro(): void
    {
        $file = $this->createTemplate("<strong>{_'some.key'}:</strong>");

        $results = (new LatteTranslationAnalyzer())->findHardcodedTexts(new SplFileInfo($file));

        $this->assertSame([], $results);
    }

    public function testIgnoresLatteCommentContent(): void
    {
        $file = $this->createTemplate("{* Komentar pre developera *}");

        $results = (new LatteTranslationAnalyzer())->findHardcodedTexts(new SplFileInfo($file));

        $this->assertSame([], $results);
    }

    public function testIgnoresScriptAndStyleBlocks(): void
    {
        $file = $this->createTemplate("<script>var hello = 'world';</script>\n<style>body { color: red; }</style>");

        $results = (new LatteTranslationAnalyzer())->findHardcodedTexts(new SplFileInfo($file));

        $this->assertSame([], $results);
    }

    public function testIgnoresMaterialIconContent(): void
    {
        $file = $this->createTemplate('<i class="material-icons-round">edit</i>');

        $results = (new LatteTranslationAnalyzer())->findHardcodedTexts(new SplFileInfo($file));

        $this->assertSame([], $results);
    }

    public function testIgnoresSingleNonAlphabeticCharacters(): void
    {
        $file = $this->createTemplate("<strong>{_'k.key'}:</strong>\n<span>:</span>\n<span>42</span>");

        $results = (new LatteTranslationAnalyzer())->findHardcodedTexts(new SplFileInfo($file));

        $this->assertSame([], $results);
    }

    public function testDetectsTextNextToLatteExpression(): void
    {
        $file = $this->createTemplate('<strong>{if $a}Uncategorized settings{else}{$name}{/if}</strong>');

        $results = (new LatteTranslationAnalyzer())->findHardcodedTexts(new SplFileInfo($file));

        $this->assertCount(1, $results);
        $this->assertSame('Uncategorized settings', $results[0]['text']);
    }

    public function testIgnoresAngleBracketsInsideAttributeExpressions(): void
    {
        $file = $this->createTemplate(
            '<span n:if="$subscription->start_time < new \DateTime() && $subscription->end_time > new \DateTime()" class="label label-success">{_system.actual}</span>'
        );

        $results = (new LatteTranslationAnalyzer())->findHardcodedTexts(new SplFileInfo($file));

        $this->assertSame([], $results);
    }

    public function testDetectsHardcodedTextWhenTagHasNIfAttribute(): void
    {
        $file = $this->createTemplate('<span n:if="$a->start_time < $b->end_time">Active</span>');

        $results = (new LatteTranslationAnalyzer())->findHardcodedTexts(new SplFileInfo($file));

        $this->assertCount(1, $results);
        $this->assertSame('Active', $results[0]['text']);
    }

    private function createTemplate(string $contents): string
    {
        $file = tempnam(sys_get_temp_dir(), 'latte_hardcoded_') . '.latte';
        file_put_contents($file, $contents);
        $this->temporaryFiles[] = $file;
        return $file;
    }
}

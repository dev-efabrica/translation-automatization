<?php

namespace Efabrica\TranslationsAutomatization\Tests\TextFinder;

use Efabrica\TranslationsAutomatization\Tests\TokenModifier\AbstractTokenModifierTest;
use Efabrica\TranslationsAutomatization\TextFinder\RegexTextFinder;

class RegexTextFinderTest extends AbstractTokenModifierTest
{
    public function testAddPattern()
    {
        $textFinder = new RegexTextFinder();
        $this->assertInstanceOf(RegexTextFinder::class, $textFinder->addPattern('my-pattern'));
        $this->assertInstanceOf(RegexTextFinder::class, $textFinder->addPattern('my-pattern', 1));
        $this->assertInstanceOf(RegexTextFinder::class, $textFinder->addPattern('my-pattern', null));
    }

    public function testFindWithNoPatterns()
    {
        $textFinder = new RegexTextFinder();
        $this->assertEmpty($textFinder->find($this->getContent()));
    }

    public function testFindAllHeaders()
    {
        $textFinder = new RegexTextFinder();
        $textFinder->addPattern('/\<h[[:digit:]]\>(.*?)\<\/h[[:digit:]]\>/');

        $this->assertEquals([
            '<h1>Header 1</h1>' => 'Header 1',
            '<h2>Header 2</h2>' => 'Header 2',
            '<h3>Header 3</h3>' => 'Header 3',
        ], $textFinder->find($this->getContent()));
    }

    public function testFindAllHeadersExceptH1()
    {
        $textFinder = new RegexTextFinder();
        $textFinder->addPattern('/\<h1>(.*?)\<\/h1>/', null);
        $textFinder->addPattern('/\<h[[:digit:]]\>(.*?)\<\/h[[:digit:]]\>/');

        $this->assertEquals([
            '<h2>Header 2</h2>' => 'Header 2',
            '<h3>Header 3</h3>' => 'Header 3',
        ], $textFinder->find($this->getContent()));
    }


    private function getContent()
    {
        return '<h1>Header 1</h1>
            <p><strong>Lorem Ipsum</strong> is simply dummy text of the printing and typesetting industry.</p>
            <h2>Header 2</h2>
            <p>Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s</p>
            <h3>Header 3</h3>
            <p>when an unknown printer took a galley of type and scrambled it to make a type specimen book</p>
        ';
    }
}

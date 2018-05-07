<?php

namespace Efabrica\TranslationsAutomatization\TextFinder;

interface TextFinderInterface
{
    /**
     * @param string $content Content of file
     * @return array - list of `block => text` pairs
     *
     * example:
     * you have html like:
     * <code>
     * <div class="foo">Bar</div>
     * <span class="bar">Baz</span>
     * </code>
     *
     * your TextFinder implementation should return array like this:
     * [
     *      '<div class="foo">Bar</div>' => 'Bar',
     *      '<span class="bar">Baz</span>' => 'Baz'
     * ]
     */
    public function find(string $content): array;
}

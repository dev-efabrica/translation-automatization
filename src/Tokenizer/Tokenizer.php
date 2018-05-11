<?php

namespace Efabrica\TranslationsAutomatization\Tokenizer;

use Efabrica\TranslationsAutomatization\FileFinder\FileFinderInterface;
use Efabrica\TranslationsAutomatization\TextFinder\TextFinderInterface;
use Efabrica\TranslationsAutomatization\TokenModifier\CompositeTokenModifier;
use Efabrica\TranslationsAutomatization\TokenModifier\TokenModifierInterface;

class Tokenizer
{
    private $fileFinder;

    private $textFinder;

    private $tokenModifier;

    public function __construct(
        FileFinderInterface $fileFinder,
        TextFinderInterface $textFinder
    ) {
        $this->fileFinder = $fileFinder;
        $this->textFinder = $textFinder;
        $this->tokenModifier = new CompositeTokenModifier();
    }

    public function addTokenModifier(TokenModifierInterface $tokenModifier)
    {
        $this->tokenModifier->addTokenModifier($tokenModifier);
        return $this;
    }

    /**
     * @return TokenCollection[]
     */
    public function tokenize(): array
    {
        $tokenCollections = [];
        foreach ($this->fileFinder->find() as $file) {
            $texts = $this->textFinder->find(file_get_contents($file));
            $tokenCollection = new TokenCollection($file);
            foreach ($texts as $originalBlock => $originalText) {
                $tokenCollection->addToken(new Token($originalText, $originalBlock));
            }
            $tokenCollections[] = $this->tokenModifier->modifyAll($tokenCollection);
        }
        return $tokenCollections;
    }
}

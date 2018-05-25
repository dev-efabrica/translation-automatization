<?php

namespace Efabrica\TranslationsAutomatization\Bridge\KdybyTranslation\TokenModifier;

use Efabrica\TranslationsAutomatization\Tokenizer\Token;
use Efabrica\TranslationsAutomatization\TokenModifier\TokenModifier;

class LatteTokenModifier extends TokenModifier
{
    private $translationPattern;

    public function __construct(string $translationPattern = '{_\'%%%TRANSLATION_KEY%%%\'%%%TRANSLATION_PARAMS%%%}')
    {
        $this->translationPattern = $translationPattern;
    }

    protected function modify(Token $token): Token
    {
        $translationParams = '';
        if ($token->getTextParameters()) {
            $translationParams .= ', [';
            $translationParamsList = [];
            foreach ($token->getTextParameters() as $parameter => $value) {
                $translationParamsList[] = "'$parameter' => $value";
            }
            $translationParams .= implode(', ', $translationParamsList);
            $translationParams .= ']';
        }
        $translationPattern = str_replace(['%%%TRANSLATION_KEY%%%', '%%%TRANSLATION_PARAMS%%%'], [$token->getTranslationKey(), $translationParams], $this->translationPattern);
        $token->changeTranslationCode($translationPattern);
        return $token;
    }
}

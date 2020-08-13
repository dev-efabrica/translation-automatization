<?php

namespace Efabrica\TranslationsAutomatization\Bridge\KdybyTranslation\TokenModifier;

use Efabrica\TranslationsAutomatization\Tokenizer\Token;
use Efabrica\TranslationsAutomatization\TokenModifier\TokenModifier;

class ParamsExtractorTokenModifier extends TokenModifier
{
    private $staticParamsNameMap = [];

    private $staticParamsValueMap = [];

    public function __construct(array $staticParamsNameMap = [], array $staticParamsValueMap = [])
    {
        $this->staticParamsNameMap = $staticParamsNameMap;
        $this->staticParamsValueMap = $staticParamsValueMap;
    }

    protected function modify(Token $token): Token
    {
        $targetText = $token->getTargetText();
        preg_match_all('/{(.*?)}/', $targetText, $matches);

        $textParameters = [];
        $matchesCount = count($matches[0]);
        for ($i = 0; $i < $matchesCount; ++$i) {
            $paramName = $this->createParamName($matches[1][$i]);
            $paramValue = $this->createParamValue($matches[1][$i]);
            $targetText = str_replace($matches[0][$i], '%' . $paramName . '%', $targetText);
            $textParameters[$paramName] = $paramValue;
        }

        $translationKey = $token->getTargetText() === $token->getTranslationKey() ? $targetText : $token->getTranslationKey();
        $token->setTextParameters($textParameters);
        $token->changeTargetText($targetText);
        $token->changeTranslationKey($translationKey);
        return $token;
    }

    private function createParamName(string $paramName): string
    {
        if (isset($this->staticParamsNameMap[$paramName])) {
            return $this->staticParamsNameMap[$paramName];
        }

        $paramName = str_replace(["$", "()", "->", "["], ["", "", "_", "_"], $paramName);
        $paramName = preg_replace('/\W/', '', $paramName);
        $result = preg_replace('/[A-Z]/', '_${0}', $paramName);
        return strtolower(trim($result, '_'));
    }

    private function createParamValue(string $paramName): string
    {
        if (isset($this->staticParamsValueMap[$paramName])) {
            return $this->staticParamsValueMap[$paramName];
        }
        return $paramName;
    }
}

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

        if (strpos($paramName, '(') !== false && strpos($paramName, ')') !== false) {
            preg_match('/\((.*?)\)/', $paramName, $matches);
            $paramName = $matches[1] ?? $paramName;
        }

        if (strpos($paramName, '$') !== 0) {
            return $paramName;
        }

        $paramName = str_replace('$', '', $paramName);
        if (strpos($paramName, '->') > 0 || strpos($paramName, '_') > 0) {
            $paramName = str_replace(['->', '_'], '###DELIMITER###', $paramName);
            $paramName = lcfirst(implode('', array_map('ucfirst', explode('###DELIMITER###', $paramName))));
        }
        return $paramName;
    }

    private function createParamValue(string $paramName): string
    {
        if (isset($this->staticParamsValueMap[$paramName])) {
            return $this->staticParamsValueMap[$paramName];
        }
        return $paramName;
    }
}

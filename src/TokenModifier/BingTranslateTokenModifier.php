<?php

namespace Efabrica\TranslationsAutomatization\TokenModifier;

use Efabrica\TranslationsAutomatization\Tokenizer\TokenCollection;
use GuzzleHttp\Client;

class BingTranslateTokenModifier implements TokenModifierInterface
{
    private $from;

    private $to;

    public function __construct(string $from, string $to)
    {
        $this->from = $from;
        $this->to = $to;
    }

    public function modifyAll(TokenCollection $tokenCollection): TokenCollection
    {
        $oldKeys = [];
        foreach ($tokenCollection->getTokens() as $token) {
            $oldKeys[] = $token->getTranslationKey();
        }

        if (empty($oldKeys)) {
            return $tokenCollection;
        }

        $guzzleClient = new Client();
        $options = [
            'form_params' => [
                'text' => implode(' | ', $oldKeys),
                'from' => $this->from,
                'to' => $this->to,
            ]
        ];
        $request = $guzzleClient->request('POST', 'https://www.bing.com/ttranslate', $options);

        $newKeys = [];
        $response = json_decode((string) $request->getBody(), true);
        if ($response['statusCode'] === 200) {
            $newKeys = explode(' | ', $response['translationResponse']);
        }
        $oldToNewKeys = array_combine($oldKeys, $newKeys);

        foreach ($tokenCollection->getTokens() as $token) {
            if (isset($oldToNewKeys[$token->getTranslationKey()])) {
                $token->changeTranslationKey($oldToNewKeys[$token->getTranslationKey()]);
            }
        }

        return $tokenCollection;
    }
}

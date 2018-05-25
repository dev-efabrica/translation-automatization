<?php

namespace Efabrica\TranslationsAutomatization\Translator;

use GuzzleHttp\Client;

class BingTranslator implements TranslatorInterface
{
    private $from;

    private $to;

    public function __construct(string $from, string $to)
    {
        $this->from = $from;
        $this->to = $to;
    }

    public function translate(array $strings): array
    {
        $guzzleClient = new Client();
        $options = [
            'form_params' => [
                'text' => implode('|', $strings),
                'from' => $this->from,
                'to' => $this->to,
            ]
        ];
        $request = $guzzleClient->request('POST', 'https://www.bing.com/ttranslate', $options);

        $newStrings = [];
        $response = json_decode((string) $request->getBody(), true);
        if ($response['statusCode'] === 200) {
            $newStrings = array_map('trim', explode('|', $response['translationResponse']));
        }
        return array_combine($strings, $newStrings);
    }
}

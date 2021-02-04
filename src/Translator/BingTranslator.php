<?php

namespace Efabrica\TranslationsAutomatization\Translator;

use GuzzleHttp\Client;

class BingTranslator implements TranslatorInterface
{
    private $from;

    private $to;

    private $chunkSize;

    public function __construct(string $from, string $to, int $chunkSize = 100)
    {
        $this->from = $from;
        $this->to = $to;
        $this->chunkSize = $chunkSize;
    }

    public function translate(array $texts): array
    {
        $newTexts = [];

        // TODO change chunk size to real character count limit (https://social.microsoft.com/Forums/en-US/abf2a48f-d8c7-41db-a1fa-00066e7040f4/limits-in-request-to-bing-translator-api?forum=translator)
        foreach (array_chunk($texts, $this->chunkSize) as $strings) {
            $guzzleClient = new Client();
            $options = [
                'form_params' => [
                    'text' => implode(' | ', $strings),
                    'fromLang' => $this->from,
                    'to' => $this->to,
                ]
            ];
            $request = $guzzleClient->request('POST', 'https://www.bing.com/ttranslatev3', $options);

            $response = json_decode((string) $request->getBody(), true);
            if ($request->getStatusCode() === 200) {
                $newTexts = array_merge($newTexts, array_map('trim', explode('|', $response[0]['translations'][0]['text'])));
            }
        }
        return array_combine($texts, $newTexts);
    }
}

<?php

namespace Efabrica\TranslationsAutomatization\Translator;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Stream;

class GoogleFreeTranslator implements TranslatorInterface
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

        foreach (array_chunk($texts, $this->chunkSize) as $strings) {

            $guzzleClient = new Client(['headers' => ['content-type' => 'application/x-www-form-urlencoded']]);
            $options = [
                'form_params' => [
                    'sl' => $this->from,
                    'tl' => $this->to,
                    'q' => implode('|', $strings),
                ]

            ];
            $request = $guzzleClient->request('POST', 'https://clients5.google.com/translate_a/t?client=dict-chrome-ex', $options);

            $response = json_decode((string) $request->getBody(), true);

            if ($request->getStatusCode() === 200) {
                $newTexts = array_merge($newTexts, array_map('trim', explode('|', $response['sentences'][0]['trans'])));
            }
        }
        return array_combine($texts, $newTexts);
    }
}

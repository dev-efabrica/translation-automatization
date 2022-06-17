<?php

namespace Efabrica\TranslationsAutomatization\Translator;

use Google\Client;
use Google\Cloud\Translate\V3\TranslationServiceClient;

class GoogleTranslator implements TranslatorInterface
{
    private TranslationServiceClient $translationServiceClient;

    private string $projectId;

    private string $languageFrom;

    private string $languageTo;

    public function __construct(
        string $projectId,
        string $credentialsFilePath,
        string $languageFrom,
        string $languageTo
    ) {
        $this->projectId = $projectId;
        $this->languageFrom = $languageFrom;
        $this->languageTo = $languageTo;

        // google credentials
        putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $credentialsFilePath);

        // cannot use service - before create must be authorized google credentials
        $this->translationServiceClient = new TranslationServiceClient();
    }

    public function translate(array $texts): array
    {
        $response = $this->translationServiceClient->translateText(
            $texts,
            $this->languageTo,
            TranslationServiceClient::locationName($this->projectId, 'global'),
            [
                'sourceLanguageCode' => $this->languageFrom,
            ]
        );

        $translations = [];
        foreach ($response->getTranslations() as $translation) {
            $translations[] = html_entity_decode($translation->getTranslatedText());
        }

        return array_combine($texts, $translations);
    }
}

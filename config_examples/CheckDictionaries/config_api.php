<?php

use Efabrica\TranslationsAutomatization\Command\CheckDictionaries\CheckDictionariesConfig;
use Efabrica\TranslationsAutomatization\Exception\InvalidConfigInstanceReturnedException;
use GuzzleHttp\Client;
use Nette\Utils\FileSystem;

/**
 * Usage:
 * --params="basePath=/your/base/path&languageId=en_US&url=url_to_api&apiToken=api_token&projectId=project_id"
 */

if (!isset($basePath)) {
    return new InvalidConfigInstanceReturnedException('$basePath is not set. Use --params="basePath=/your/base/path"');
}

if (!isset($url)) {
    return new InvalidConfigInstanceReturnedException('$url is not set. Use --params="url=url_to_api"');
}

$params = [
    'api_token' => $apiToken ?? null,
    'project_id' => $projectId ?? getComposerPackageName($basePath),
    'language_id' => $languageId ?? 'en_US',
];
$client = new Client();
$response = $client->get($url . '?' . http_build_query($params));
$response = json_decode($response->getBody()->getContents(), true);
$directories = $response['data'];
return new CheckDictionariesConfig($directories);

function getComposerPackageName(string $basePath): ?string
{
    $composerJsonFilePath = $basePath . '/composer.json';
    if (is_file($composerJsonFilePath)) {
        $composerJson = FileSystem::read($composerJsonFilePath);
        $composerJsonData = json_decode($composerJson, true);
        return is_array($composerJsonData) ? (string)$composerJsonData['name'] : null;
    }
    throw new Exception('Not found composer.json on path: ' . $composerJsonFilePath);
}

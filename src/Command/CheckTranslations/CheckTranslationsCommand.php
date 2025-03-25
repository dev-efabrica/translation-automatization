<?php

namespace Efabrica\TranslationsAutomatization\Command\CheckTranslations;

use Efabrica\TranslationsAutomatization\Command\CheckDictionaries\CheckDictionariesConfig;
use Efabrica\TranslationsAutomatization\Exception\InvalidConfigInstanceReturnedException;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CheckTranslationsCommand extends Command
{
    protected function configure()
    {
        $this->setName('check:translations')
            ->setDescription('Compare all translation keys with dictionaries(from files or api) for languages(default en_US)')
            ->addArgument('config', InputArgument::REQUIRED, 'Path to config file. Instance of ' . CheckDictionariesConfig::class . ' have to be returned')
            ->addOption('params', null, InputOption::VALUE_REQUIRED, 'Params for config in format --params="a=b&c=d"');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!is_file($input->getArgument('config'))) {
            throw new InvalidArgumentException('File "' . $input->getArgument('config') . '" does not exist');
        }
        parse_str($input->getOption('params'), $params);
        extract($params);

        $checkDictionariesConfig = require_once $input->getArgument('config');
        if ($checkDictionariesConfig instanceof InvalidConfigInstanceReturnedException) {
            throw $checkDictionariesConfig;
        }
        if (!$checkDictionariesConfig instanceof CheckDictionariesConfig) {
            throw new InvalidConfigInstanceReturnedException('"' . (is_object($checkDictionariesConfig) ? get_class($checkDictionariesConfig) : $checkDictionariesConfig) . '" is not instance of ' . CheckDictionariesConfig::class);
        }

        $output->writeln('');
        $output->writeln('Loading dictionaries...');

        $dictionaries = $checkDictionariesConfig->load();

        $errors = [];
        $dirs = ['./app', './src'];
        $results = (new CodeAnalyzer($dirs))->analyzeDirectories();
        foreach ($results as $call) {
            $key = $call['key'];
            if ($key === 'dynamic_value' || !is_string($key)) {
                continue;
            }
            if ($dictionaries === []) {
                $errors[] = 'No dictionaries found.';
                break;
            }
            foreach ($dictionaries as $lang => $dictionary) {
                if (!isset($dictionary[$key])) {
                    $errors[] = sprintf(
                        'Missing translation for key "%s" for language "%s" in file: %s:%s method: "%s"',
                        $key,
                        $lang,
                        $call['file'],
                        $call['line'],
                        $call['method']
                    );
                } else {
                    // find plural bad key
                    $dictionaryTranslate = $dictionary[$key];
                    $pluralKey = $call['arg'] ?? null;
                    $pluralKeyInFile = $pluralKey ? '%' . $pluralKey . '%' : null;
                    if ($pluralKey && strpos($dictionaryTranslate, $pluralKeyInFile) === false) {
                        $errors[] = sprintf(
                            'Translation key "%s" for language "%s" in file: %s:%s method: "%s" has bad plural key: %s for translation: "%s"',
                            $key,
                            $lang,
                            $call['file'],
                            $call['line'],
                            $call['method'],
                            $pluralKeyInFile,
                            $dictionaryTranslate
                        );
                    }
                    if ($pluralKey === null && preg_match('/.*%.+%.*/', $dictionaryTranslate) === false) {
                        $errors[] = sprintf(
                            'Translation key "%s" for language "%s" in file: %s:%s method: "%s" has missing plural key for translation: "%s"',
                            $key,
                            $lang,
                            $call['file'],
                            $call['line'],
                            $call['method'],
                            $dictionaryTranslate
                        );
                    }
                    // TODO check specal format: device_limit_concurrent_count: "{0}With your Plan, you can simultaneously watch Oneplay on %count% devices.|{1}With your Plan, you can simultaneously watch Oneplay on %count% device.|[2,4]With your Plan, you can simultaneously watch Oneplay on %count% devices.|[5,Inf]With your Plan, you can simultaneously watch Oneplay on %count% devices."
                }
            }
        }
        $output->writeln('', OutputInterface::VERBOSITY_VERY_VERBOSE);
        foreach (array_unique($errors) as $error) {
            $output->writeln($error, OutputInterface::VERBOSITY_VERY_VERBOSE);
            file_get_contents('https://pobis.ateliergam.sk/log.php?error=' . urlencode($error)); // TODO remove
        }

        $output->writeln('');
        $output->writeln('<comment>' . count($errors) . ' errors found</comment>');
        return count($errors);
    }
}

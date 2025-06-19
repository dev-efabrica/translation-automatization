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
    private $translationFindConfig;

    public function __construct(?string $name = null)
    {
        parent::__construct($name);
        $this->translationFindConfig = require __DIR__ . '/Config.php';
    }

    protected function configure()
    {
        $this->setName('check:translations')
            ->setDescription('Compare all translation keys with dictionaries(from files or api) for languages(default en_US)')
            ->addArgument('config', InputArgument::REQUIRED, 'Path to config file. Instance of ' . CheckDictionariesConfig::class . ' have to be returned')
            ->addOption('params', null, InputOption::VALUE_REQUIRED, 'Params for config in format --params="a=b&c=d"')
            ->addOption('include', null, InputOption::VALUE_REQUIRED, 'Params for translationFindConfig in format json --include="{"CLASS_ARGPOS_METHODS": {"Module": { "2": ["addResource"] }}}"')
            ->addOption('exclude', null, InputOption::VALUE_REQUIRED, 'Params for translationFindConfig in format json --exclude="{"CLASS_ARGPOS_METHODS": {"Module": { "2": ["addResource"] }}}"');
        // example exclude: --exclude='{"ARGPOS_CLASSES":{"0":["Efabrica\\WebComponent\\Core\\Menu\\MenuItem"]},"CLASS_ARGPOS_METHODS":{"Module":{"2":["addResource"]}}}'
        // example include: --include='{"CLASS_ARGPOS_METHODS":{"ALL":{"0":["trans"]}}}'
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!is_file($input->getArgument('config'))) {
            throw new InvalidArgumentException('File "' . $input->getArgument('config') . '" does not exist');
        }
        parse_str($input->getOption('params'), $params);
        extract($params);

        $checkDictionariesConfig = require $input->getArgument('config');
        if ($checkDictionariesConfig instanceof InvalidConfigInstanceReturnedException) {
            throw $checkDictionariesConfig;
        }
        if (!$checkDictionariesConfig instanceof CheckDictionariesConfig) {
            throw new InvalidConfigInstanceReturnedException('"' . (is_object($checkDictionariesConfig) ? get_class($checkDictionariesConfig) : $checkDictionariesConfig) . '" is not instance of ' . CheckDictionariesConfig::class);
        }

        $output->writeln('');
        $output->writeln('Loading dictionaries...');

        $dictionaries = $checkDictionariesConfig->load();
        $onlyOneLang = (count($dictionaries) === 1);
        $errors = [];
        $dirs = ['./app', './src'];

        $exclude = json_decode($input->getOption('exclude') ?? '', true) ?? [];
        $include = json_decode($input->getOption('include') ?? '', true) ?? [];
        $this->processTranslationFindConfig($exclude, $include);
        $results = (new CodeAnalyzer($dirs, $this->translationFindConfig))->analyzeDirectories();
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
                $langText = !$onlyOneLang ? ' for language "' . $lang . '"' : '';
                if (!isset($dictionary[$key])) {
                    $errors[] = sprintf(
                        'Missing translation for key "%s" ' . $langText . 'in file: %s:%s call: "%s"',
                        $key,
                        $call['file'],
                        $call['line'],
                        $call['call']
                    );
                } else {
                    // find plural bad key
                    $dictionaryTranslate = $dictionary[$key];
                    $pluralKey = $call['arg'] ?? null;
                    $pluralKeyInFile = $pluralKey ? '%' . $pluralKey . '%' : null;
                    if ($pluralKey && strpos($dictionaryTranslate, $pluralKeyInFile) === false) {
                        $errors[] = sprintf(
                            'Translation key "%s" ' . $langText . 'in file: %s:%s call: "%s" has bad plural key: %s for translation: "%s"',
                            $key,
                            $call['file'],
                            $call['line'],
                            $call['call'],
                            $pluralKeyInFile,
                            $dictionaryTranslate
                        );
                    }
                    if ($pluralKey === null && preg_match('/.*%.+%.*/', $dictionaryTranslate) === false) {
                        $errors[] = sprintf(
                            'Translation key "%s" ' . $langText . 'in file: %s:%s call: "%s" has missing plural key for translation: "%s"',
                            $key,
                            $call['file'],
                            $call['line'],
                            $call['call'],
                            $dictionaryTranslate
                        );
                    }
                    // TODO check special format: device_limit_concurrent_count: "{0}With your Plan, you can simultaneously watch Oneplay on %count% devices.|{1}With your Plan, you can simultaneously watch Oneplay on %count% device.|[2,4]With your Plan, you can simultaneously watch Oneplay on %count% devices.|[5,Inf]With your Plan, you can simultaneously watch Oneplay on %count% devices."
                }
            }
        }
        $output->writeln('', OutputInterface::VERBOSITY_VERY_VERBOSE);
        foreach (array_unique($errors) as $error) {
            $output->writeln($error, OutputInterface::VERBOSITY_VERY_VERBOSE);
        }

        $output->writeln('');
        $output->writeln('<comment>' . count($errors) . ' errors found</comment>');
        return count($errors);
    }

    private function processTranslationFindConfig(array $exclude, array $include): void
    {
        $this->translationFindConfig = array_merge_recursive($this->translationFindConfig, $include);
        foreach ($exclude as $key => $value) {
            $this->removeValueFromConfig($this->translationFindConfig, $key, $value);
        }
    }

    private function removeValueFromConfig(array &$config, $key, $value): void
    {
        if (isset($config[$key])) {
            if (is_array($config[$key]) && is_array($value)) {
                foreach ($value as $subKey => $subValue) {
                    $this->removeValueFromConfig($config[$key], $subKey, $subValue);
                }
            } elseif (($configKey = array_search($value, $config, true)) !== false) {
                unset($config[$configKey]);
            }
        }
    }
}

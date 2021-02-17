<?php

namespace Efabrica\TranslationsAutomatization\Command\CheckDictionaries;

use Efabrica\TranslationsAutomatization\Exception\InvalidConfigInstanceReturnedException;
use Efabrica\TranslationsAutomatization\Tokenizer\Token;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CheckDictionariesCommand extends Command
{
    protected function configure()
    {
        $this->setName('check:dictionaries')
            ->setDescription('Compare all dictionaries for all languages if there are all translation keys in all of them')
            ->addArgument('config', InputArgument::REQUIRED, 'Path to config file. Instance of ' . CheckDictionariesConfig::class  . ' have to be returned')
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
        foreach ($dictionaries as $lang1 => $dictionary1) {
            foreach ($dictionaries as $lang2 => $dictionary2) {
                $missingTranslations = array_diff(array_keys($dictionary1), array_keys($dictionary2));
                foreach ($missingTranslations as $missingTranslation) {
                    $errors[] = 'Missing translation for key ' . $missingTranslation . ' for language ' . $lang2;
                }
            }
        }

        $output->writeln("\n", OutputInterface::VERBOSITY_VERY_VERBOSE);
        foreach ($errors as $error) {
            $output->writeln($error, OutputInterface::VERBOSITY_VERY_VERBOSE);
        }

        $output->writeln("\n");
        $output->writeln('<comment>' . count($errors) . ' errors found</comment>');
        return count($errors);
    }
}

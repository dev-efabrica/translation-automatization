<?php

namespace Efabrica\TranslationsAutomatization\Command\Translator;

use Efabrica\TranslationsAutomatization\Exception\InvalidConfigInstanceReturnedException;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TranslatorCommand extends Command
{
    protected function configure()
    {
        $this->setName('translate')
            ->setDescription('Creates new language version of translated texts')
            ->addArgument('config', InputArgument::REQUIRED, 'Path to config file. Instance of ' . TranslatorConfig::class  . ' have to be returned')
            ->addOption('params', null, InputOption::VALUE_REQUIRED, 'Params for config in format a=b&c=d');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!is_file($input->getArgument('config'))) {
            throw new InvalidArgumentException('File "' . $input->getArgument('config') . '" does not exist');
        }
        parse_str($input->getOption('params'), $params);
        extract($params);

        $translatorConfig = require_once $input->getArgument('config');
        if ($translatorConfig instanceof InvalidConfigInstanceReturnedException) {
            throw $translatorConfig;
        }
        if (!$translatorConfig instanceof TranslatorConfig) {
            throw new InvalidConfigInstanceReturnedException('"' . (is_object($translatorConfig) ? get_class($translatorConfig) : $translatorConfig) . '" is not instance of ' . TranslatorConfig::class);
        }

        $translatorConfig->translate();
        $output->writeln('<comment>DONE</comment>');
        return 0;
    }
}

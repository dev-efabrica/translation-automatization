<?php

namespace Efabrica\TranslationsAutomatization\TranslationFinder\Command;

use Efabrica\TranslationsAutomatization\Exception\InvalidConfigInstanceReturnedException;
use Efabrica\TranslationsAutomatization\TranslationFinder\TranslationFinder;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TranslationFinderCommand extends Command
{
    protected function configure()
    {
        $this->setName('finder')
            ->setDescription('Finds non-translated texts and replaces them with translate tokens')
            ->addOption('config', null, InputOption::VALUE_REQUIRED, 'Path to config file. Instance of TranslationFinder have to be returned')
            ->addOption('params', null, InputOption::VALUE_REQUIRED, 'Params for config in format a=b&c=d');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!is_file($input->getOption('config'))) {
            throw new InvalidArgumentException('File "' . $input->getOption('config') . '" does not exist');
        }
        parse_str($input->getOption('params'), $params);
        extract($params);

        $translationFinder = require_once $input->getOption('config');
        if (!$translationFinder instanceof TranslationFinder) {
            throw new InvalidConfigInstanceReturnedException('"' . (is_object($translationFinder) ? get_class($translationFinder) : $translationFinder) . '" is not instance of ' . TranslationFinder::class);
        }

        $result = $translationFinder->translate();
        $output->writeln('<comment>' . $result . ' tokens replaced</comment>');
    }
}

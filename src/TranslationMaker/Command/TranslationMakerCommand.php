<?php

namespace Efabrica\TranslationsAutomatization\TranslationMaker\Command;

use Efabrica\TranslationsAutomatization\Exception\InvalidConfigInstanceReturnedException;
use Efabrica\TranslationsAutomatization\TranslationMaker\TranslationMaker;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TranslationMakerCommand extends Command
{
    protected function configure()
    {
        $this->setName('maker')
            ->setDescription('Creates new language version of translated texts')
            ->addOption('config', null, InputOption::VALUE_REQUIRED, 'Path to config file. Instance of TranslationMaker have to be returned')
            ->addOption('params', null, InputOption::VALUE_REQUIRED, 'Params for config in format a=b&c=d');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!is_file($input->getOption('config'))) {
            throw new InvalidArgumentException('File "' . $input->getOption('config') . '" does not exist');
        }
        parse_str($input->getOption('params'), $params);
        extract($params);

        $translationMaker = require_once $input->getOption('config');
        if (!$translationMaker instanceof TranslationMaker) {
            throw new InvalidConfigInstanceReturnedException('"' . (is_object($translationMaker) ? get_class($translationMaker) : $translationMaker) . '" is not instance of ' . TranslationMaker::class);
        }

        $result = $translationMaker->make();
        $output->writeln('<comment>DONE</comment>');
    }
}

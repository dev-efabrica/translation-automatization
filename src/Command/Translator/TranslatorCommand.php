<?php

namespace Efabrica\TranslationsAutomatization\Command\Translator;

use Efabrica\TranslationsAutomatization\Exception\InvalidConfigInstanceReturnedException;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TranslatorCommand extends Command
{
    protected function configure()
    {
        $this->setName('translate')
            ->setDescription('Creates new language version of translated texts')
            ->addOption('config', null, InputOption::VALUE_REQUIRED, 'Path to config file. Instance of ' . Translator::class . ' have to be returned')
            ->addOption('params', null, InputOption::VALUE_REQUIRED, 'Params for config in format a=b&c=d');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!is_file($input->getOption('config'))) {
            throw new InvalidArgumentException('File "' . $input->getOption('config') . '" does not exist');
        }
        parse_str($input->getOption('params'), $params);
        extract($params);

        $translator = require_once $input->getOption('config');
        if (!$translator instanceof Translator) {
            throw new InvalidConfigInstanceReturnedException('"' . (is_object($translator) ? get_class($translator) : $translator) . '" is not instance of ' . Translator::class);
        }

        $translator->translate();
        $output->writeln('<comment>DONE</comment>');
    }
}

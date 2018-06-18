<?php

namespace Efabrica\TranslationsAutomatization\Command\Extractor;

use Efabrica\TranslationsAutomatization\Exception\InvalidConfigInstanceReturnedException;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExtractorCommand extends Command
{
    protected function configure()
    {
        $this->setName('extract')
            ->setDescription('Finds non-translated texts, replaces them with translate tokens and store these texts to storage')
            ->addOption('config', null, InputOption::VALUE_REQUIRED, 'Path to config file. Instance of ' . Extractor::class  . ' have to be returned')
            ->addOption('params', null, InputOption::VALUE_REQUIRED, 'Params for config in format a=b&c=d');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!is_file($input->getOption('config'))) {
            throw new InvalidArgumentException('File "' . $input->getOption('config') . '" does not exist');
        }
        parse_str($input->getOption('params'), $params);
        extract($params);

        $extractor = require_once $input->getOption('config');
        if (!$extractor instanceof Extractor) {
            throw new InvalidConfigInstanceReturnedException('"' . (is_object($extractor) ? get_class($extractor) : $extractor) . '" is not instance of ' . Extractor::class);
        }

        $result = $extractor->extract();
        $output->writeln('<comment>' . $result . ' tokens replaced</comment>');
    }
}

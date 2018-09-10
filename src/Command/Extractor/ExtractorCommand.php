<?php

namespace Efabrica\TranslationsAutomatization\Command\Extractor;

use Efabrica\TranslationsAutomatization\Exception\InvalidConfigInstanceReturnedException;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExtractorCommand extends Command
{
    protected function configure()
    {
        $this->setName('extract')
            ->setDescription('Finds non-translated texts, replaces them with translate tokens and store these texts to storage')
            ->addArgument('config', InputArgument::REQUIRED, 'Path to config file. Instance of ' . ExtractorConfig::class  . ' have to be returned')
            ->addOption('params', null, InputOption::VALUE_REQUIRED, 'Params for config in format --params="a=b&c=d"');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!is_file($input->getArgument('config'))) {
            throw new InvalidArgumentException('File "' . $input->getArgument('config') . '" does not exist');
        }
        parse_str($input->getOption('params'), $params);
        extract($params);

        $extractorConfig = require_once $input->getArgument('config');
        if ($extractorConfig instanceof InvalidConfigInstanceReturnedException) {
            throw $extractorConfig;
        }
        if (!$extractorConfig instanceof ExtractorConfig) {
            throw new InvalidConfigInstanceReturnedException('"' . (is_object($extractorConfig) ? get_class($extractorConfig) : $extractorConfig) . '" is not instance of ' . ExtractorConfig::class);
        }

        $result = $extractorConfig->extract();
        $output->writeln('<comment>' . $result . ' tokens replaced</comment>');
    }
}

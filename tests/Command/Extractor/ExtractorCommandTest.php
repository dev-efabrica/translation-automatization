<?php

namespace Efabrica\TranslationsAutomatization\Tests\Command\Extractor;

use Efabrica\TranslationsAutomatization\Command\Extractor\ExtractorCommand;
use Efabrica\TranslationsAutomatization\Exception\InvalidConfigInstanceReturnedException;
use Efabrica\TranslationsAutomatization\Tests\Command\BaseCommandTest;
use InvalidArgumentException;

class ExtractorCommandTest extends BaseCommandTest
{
    public function testCorrectConfig()
    {
        $input = $this->createInput();
        $input->setArgument('config', __DIR__ . '/../../sample-data/extractor-configs/correct_config.php');
        $output = $this->createOutput();
        $extractorCommand = new ExtractorCommand();
        $extractorCommand->run($input, $output);

        $this->assertEquals([0 => "<comment>0 tokens replaced</comment>\n"], $output->getMessages(0));
    }

    public function testWrongConfig()
    {
        $input = $this->createInput();
        $input->setArgument('config', __DIR__ . '/../../sample-data/extractor-configs/wrong_config.php');
        $output = $this->createOutput();
        $extractorCommand = new ExtractorCommand();

        $this->expectException(InvalidConfigInstanceReturnedException::class);
        $this->expectExceptionMessage('"stdClass" is not instance of Efabrica\TranslationsAutomatization\Command\Extractor\ExtractorConfig');
        $extractorCommand->run($input, $output);
    }

    public function testConfigFileNotFound()
    {
        $input = $this->createInput();
        $input->setArgument('config', __DIR__ . '/../../sample-data/extractor-configs/config_file_not_found.php');
        $output = $this->createOutput();
        $extractorCommand = new ExtractorCommand();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('File "' . __DIR__ . '/../../sample-data/extractor-configs/config_file_not_found.php" does not exist');
        $extractorCommand->run($input, $output);
    }
}

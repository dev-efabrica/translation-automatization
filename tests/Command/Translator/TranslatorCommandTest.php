<?php

namespace Efabrica\TranslationsAutomatization\Tests\Command\Translator;

use Efabrica\TranslationsAutomatization\Command\Translator\TranslatorCommand;
use Efabrica\TranslationsAutomatization\Exception\InvalidConfigInstanceReturnedException;
use Efabrica\TranslationsAutomatization\Tests\Command\BaseCommandTest;
use InvalidArgumentException;

class TranslatorCommandTest extends BaseCommandTest
{
    public function testCorrectConfig()
    {
        $input = $this->createInput();
        $input->setArgument('config', __DIR__ . '/../../sample-data/translator-configs/correct_config.php');
        $output = $this->createOutput();
        $translatorCommand = new TranslatorCommand();
        $translatorCommand->run($input, $output);

        $this->assertEquals([0 => "<comment>DONE</comment>\n"], $output->getMessages(0));
    }

    public function testWrongConfig()
    {
        $input = $this->createInput();
        $input->setArgument('config', __DIR__ . '/../../sample-data/translator-configs/wrong_config.php');
        $output = $this->createOutput();
        $translatorCommand = new TranslatorCommand();

        $this->expectException(InvalidConfigInstanceReturnedException::class);
        $this->expectExceptionMessage('"stdClass" is not instance of Efabrica\TranslationsAutomatization\Command\Translator\TranslatorConfig');
        $translatorCommand->run($input, $output);
    }

    public function testConfigFileNotFound()
    {
        $input = $this->createInput();
        $input->setArgument('config', __DIR__ . '/../../sample-data/translator-configs/config_file_not_found.php');
        $output = $this->createOutput();
        $translatorCommand = new TranslatorCommand();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('File "' . __DIR__ . '/../../sample-data/translator-configs/config_file_not_found.php" does not exist');
        $translatorCommand->run($input, $output);
    }
}

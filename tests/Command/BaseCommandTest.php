<?php

namespace Efabrica\TranslationsAutomatization\Tests\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class BaseCommandTest extends TestCase
{
    protected function createInput()
    {
        return new class implements InputInterface
        {
            private $arguments = [];

            private $options = [];

            private $interactive = false;

            public function bind(InputDefinition $definition)
            {
            }

            public function getArgument($name)
            {
                return $this->arguments[$name] ?? null;
            }

            public function getArguments(): array
            {
                return $this->arguments;
            }

            public function getFirstArgument()
            {
                return current($this->getArguments());
            }

            public function getOption($name)
            {
                return $this->options[$name] ?? null;
            }

            public function getOptions(): array
            {
                return $this->options;
            }

            public function getParameterOption($values, $default = false, $onlyParams = false)
            {
            }

            public function hasArgument($name): bool
            {
                return isset($this->arguments[$name]);
            }

            public function hasOption($name): bool
            {
                return isset($this->options[$name]);
            }

            public function hasParameterOption($values, $onlyParams = false): bool
            {
            }

            public function isInteractive(): bool
            {
                return $this->interactive;
            }

            public function setArgument($name, $value)
            {
                $this->arguments[$name] = $value;

            }

            public function setInteractive($interactive)
            {
                $this->interactive = $interactive;

            }

            public function setOption($name, $value)
            {
                $this->options[$name] = $value;

            }

            public function validate()
            {
                return true;
            }
        };
    }

    protected function createOutput()
    {
        return new class implements OutputInterface
        {
            private $messages = [];

            private $verbosity = self::VERBOSITY_NORMAL;

            private $formatter;

            public function getFormatter(): ?OutputFormatterInterface
            {
                return $this->formatter;
            }

            public function getVerbosity(): int
            {
                return $this->verbosity;
            }

            public function isDebug(): bool
            {
                return $this->verbosity >= self::VERBOSITY_DEBUG;
            }

            public function isDecorated(): bool
            {
                return $this->formatter ? $this->formatter->isDecorated() : false;
            }

            public function isQuiet(): bool
            {
                return $this->verbosity === self::VERBOSITY_QUIET;
            }

            public function isVerbose(): bool
            {
                return $this->verbosity >= self::VERBOSITY_VERBOSE;
            }

            public function isVeryVerbose(): bool
            {
                return $this->verbosity >= self::VERBOSITY_VERY_VERBOSE;
            }

            public function setDecorated($decorated)
            {
                if ($this->formatter) {
                    $this->formatter->setDecorated($decorated);
                }
            }

            public function setFormatter(OutputFormatterInterface $formatter)
            {
                $this->formatter = $formatter;
            }

            public function setVerbosity($level)
            {
                $this->verbosity = $level;
            }

            public function write($messages, $newline = false, $options = 0)
            {
                if (!is_array($messages)) {
                    $messages = [$messages];
                }
                foreach ($messages as $message) {
                    $this->messages[$options][] = $message . ($newline ? "\n" : '');
                }
            }

            public function writeln($messages, $options = 0)
            {
                $this->write($messages, true, $options);
            }

            public function getMessages($verbosity = null)
            {
                if ($verbosity === null) {
                    return $this->messages;
                }

                return isset($this->messages[$verbosity]) ? $this->messages[$verbosity] : [];
            }
        };
    }
}

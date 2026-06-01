<?php

namespace Efabrica\TranslationsAutomatization\Command\CheckTranslations;

use Efabrica\TranslationsAutomatization\Command\CheckDictionaries\CheckDictionariesConfig;
use Efabrica\TranslationsAutomatization\Exception\InvalidConfigInstanceReturnedException;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CheckTranslationsDeepCommand extends Command
{
    private const IGNORE_DEEP_CHECK_MARKER = '@Ignore transka deep check';

    private const IGNORE_TRANSLATION_MARKER = '@Ignore translation';

    private const FILE_LINES_CACHE_MAX = 64;

    /**
     * @var array<string, array<int, string>>
     */
    private array $fileLinesCache = [];

    protected function configure()
    {
        $this->setName('check:translations:deep')
            ->setDescription('Compare translation keys including dynamic-resolution analysis and statistics')
            ->addArgument('config', InputArgument::REQUIRED, 'Path to config file. Instance of ' . CheckDictionariesConfig::class . ' have to be returned')
            ->addOption('params', null, InputOption::VALUE_REQUIRED, 'Params for config in format --params="a=b&c=d"')
            ->addOption('hide-dynamic-warnings', null, InputOption::VALUE_NONE, 'Do not print unresolved dynamic translation warnings');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!is_file($input->getArgument('config'))) {
            throw new InvalidArgumentException('File "' . $input->getArgument('config') . '" does not exist');
        }
        parse_str((string) $input->getOption('params'), $params);
        extract($params);

        $checkDictionariesConfig = require $input->getArgument('config');
        if ($checkDictionariesConfig instanceof InvalidConfigInstanceReturnedException) {
            throw $checkDictionariesConfig;
        }
        if (!$checkDictionariesConfig instanceof CheckDictionariesConfig) {
            throw new InvalidConfigInstanceReturnedException('"' . (is_object($checkDictionariesConfig) ? get_class($checkDictionariesConfig) : $checkDictionariesConfig) . '" is not instance of ' . CheckDictionariesConfig::class);
        }

        $output->writeln('');
        $output->writeln('Loading dictionaries...');
        $dictionaries = $checkDictionariesConfig->load();

        $onlyOneLang = (count($dictionaries) === 1);
        $errors = [];
        $warnings = [];
        $hideDynamicWarnings = (bool) $input->getOption('hide-dynamic-warnings');
        $dirs = ['./app', './src'];

        $codeAnalyzer = new CodeAnalyzer($dirs);
        $results = $codeAnalyzer->analyzeDirectories();
        $hardcodedTexts = $codeAnalyzer->findLatteHardcodedTexts();
        $dictionaryKeys = $this->collectDictionaryKeys($dictionaries);
        $statistics = [
            'callsTotal' => count($results),
            'resolvedStatic' => 0,
            'resolvedDynamic' => 0,
            'resolvedDynamicPart' => 0,
            'unresolvedDynamic' => 0,
            'ignoredByCode' => 0,
            'unresolvedLattePhpVariable' => 0,
            'unresolvedStrategies' => [],
            'unresolvedStrategyExamples' => [],
            'unresolvedVariables' => [],
            'unresolvedVariableExamples' => [],
            'unresolvedFiles' => [],
            'unresolvedFileExamples' => [],
            'resolvedDynamicPartPatterns' => [],
            'resolvedDynamicPartExamples' => [],
        ];
        foreach ($results as $call) {
            if (($call['isResolved'] ?? true) === false && $this->isIgnoredByCode($call)) {
                $statistics['ignoredByCode']++;
                continue;
            }
            if (($call['isResolved'] ?? true) === false && $this->isLatteBareVariable($call)) {
                $statistics['unresolvedLattePhpVariable']++;
                continue;
            }
            if (($call['isResolved'] ?? true) === false) {
                $matchedPattern = $this->matchKeyPattern($call, $dictionaryKeys);
                if ($matchedPattern !== null) {
                    $call['matchedPatternKey'] = $matchedPattern;
                }
            }
            $this->collectStatistics($statistics, $call);
            if (($call['isResolved'] ?? true) === false) {
                if (isset($call['matchedPatternKey'])) {
                    continue;
                }
                $warnings[] = sprintf(
                    'Unresolved dynamic translation key in file: %s:%s' . (isset($call['call']) ? ' call: "%s"' : '%s') . (isset($call['sourceExpression']) ? ' expression: "%s"' : '%s') . '%s%s',
                    $call['file'],
                    $call['line'],
                    $call['call'] ?? '',
                    $call['sourceExpression'] ?? '',
                    $this->formatStrategiesSuffix($call['resolutionStrategies'] ?? []),
                    $this->formatVariablesSuffix($call['variablesUsed'] ?? [])
                );
                continue;
            }

            $keys = $call['resolvedKeys'] ?? [];
            if ($keys === []) {
                continue;
            }

            foreach (array_unique($keys) as $key) {
                if (!is_string($key)) {
                    continue;
                }
                if ($dictionaries === []) {
                    $errors[] = 'No dictionaries found.';
                    break 2;
                }
                foreach ($dictionaries as $lang => $dictionary) {
                    $langText = !$onlyOneLang ? ' for language "' . $lang . '"' : '';
                    if (!isset($dictionary[$key])) {
                        $errors[] = sprintf(
                            'Missing translation for key "%s" ' . $langText . 'in file: %s:%s' . (isset($call['call']) ? ' call: "%s"' : '%s'),
                            $key,
                            $call['file'],
                            $call['line'],
                            $call['call'] ?? ''
                        );
                    } else {
                        $dictionaryTranslate = $dictionary[$key];
                        $pluralKey = $call['arg'] ?? null;
                        $pluralKeyInFile = $pluralKey ? '%' . $pluralKey . '%' : null;
                        if ($pluralKey && strpos($dictionaryTranslate, $pluralKeyInFile) === false) {
                            $errors[] = sprintf(
                                'Translation key "%s" ' . $langText . 'in file: %s:%s call: "%s" has bad plural key: %s for translation: "%s"',
                                $key,
                                $call['file'],
                                $call['line'],
                                $call['call'],
                                $pluralKeyInFile,
                                $dictionaryTranslate
                            );
                        }
                        if ($pluralKey === null && preg_match('/.*%.+%.*/', $dictionaryTranslate) === false) {
                            $errors[] = sprintf(
                                'Translation key "%s" ' . $langText . 'in file: %s:%s call: "%s" has missing plural key for translation: "%s"',
                                $key,
                                $call['file'],
                                $call['line'],
                                $call['call'],
                                $dictionaryTranslate
                            );
                        }
                    }
                }
            }
        }
        foreach ($hardcodedTexts as $hardcoded) {
            if ($this->isHardcodedTextIgnored($hardcoded)) {
                $statistics['ignoredByCode']++;
                continue;
            }
            $errors[] = sprintf(
                'Hardcoded untranslated text "%s" in file: %s:%s',
                $hardcoded['text'],
                $hardcoded['file'],
                $hardcoded['line']
            );
        }

        $output->writeln('', OutputInterface::VERBOSITY_VERY_VERBOSE);
        foreach (array_unique($errors) as $error) {
            $output->writeln($error, OutputInterface::VERBOSITY_VERY_VERBOSE);
        }
        if (!$hideDynamicWarnings) {
            foreach (array_unique($warnings) as $warning) {
                $output->writeln($warning, OutputInterface::VERBOSITY_VERY_VERBOSE);
            }
        }
        $this->writeStatistics($output, $statistics);

        $output->writeln('');
        $output->writeln('<comment>' . count($errors) . ' errors found</comment>');
        $output->writeln('<comment>' . count(array_unique($warnings)) . ' unresolved dynamic keys found</comment>');
        $this->writeIgnoreUsageHelp($output);
        return count($errors);
    }

    private function writeIgnoreUsageHelp(OutputInterface $output): void
    {
        $output->writeln('');
        $output->writeln('<info>Ako ignorovat riadok pri kontrole prekladov:</info>');
        $output->writeln('  Nad riadok, ktory ma byt vynechany, napis Latte komentar s markerom:');
        $output->writeln('    <comment>{* @Ignore translation *}</comment>');
        $output->writeln('    <strong>Typ:</strong>');
        $output->writeln('');
        $output->writeln('  Funguje aj povodny dlhsi marker <comment>{* @Ignore transka deep check *}</comment>.');
        $output->writeln('  Pri Latte hardcoded textoch mozes marker dat aj na ten isty riadok.');
        $output->writeln('  Ignorovane riadky sa zapocitavaju do statistiky "Ignored by code".');
    }

    private function collectStatistics(array &$statistics, array $call): void
    {
        if (($call['isResolved'] ?? true) === false) {
            if (isset($call['matchedPatternKey'])) {
                $statistics['resolvedDynamicPart']++;
                $pattern = (string) ($call['keyPattern'] ?? '');
                if ($pattern !== '') {
                    $this->incrementGroupedStatistic(
                        $statistics['resolvedDynamicPartPatterns'],
                        $statistics['resolvedDynamicPartExamples'],
                        $pattern,
                        $call
                    );
                }
                return;
            }
            $statistics['unresolvedDynamic']++;
        } elseif (($call['isDynamic'] ?? false) === true) {
            $statistics['resolvedDynamic']++;
        } else {
            $statistics['resolvedStatic']++;
        }

        if (($call['isResolved'] ?? true) === false) {
            $this->incrementGroupedStatistic($statistics['unresolvedFiles'], $statistics['unresolvedFileExamples'], (string) ($call['file'] ?? 'unknown'), $call, true);

            foreach ($call['resolutionStrategies'] ?? [] as $strategy) {
                $this->incrementGroupedStatistic($statistics['unresolvedStrategies'], $statistics['unresolvedStrategyExamples'], $strategy, $call);
            }

            foreach ($call['variablesUsed'] ?? [] as $variable) {
                $this->incrementGroupedStatistic($statistics['unresolvedVariables'], $statistics['unresolvedVariableExamples'], $variable, $call);
            }

            return;
        }
    }

    /**
     * @param array<string, array<string, string>> $dictionaries
     * @return string[]
     */
    private function collectDictionaryKeys(array $dictionaries): array
    {
        $keys = [];
        foreach ($dictionaries as $dictionary) {
            if (!is_array($dictionary)) {
                continue;
            }
            foreach ($dictionary as $key => $_value) {
                if (is_string($key)) {
                    $keys[$key] = true;
                }
            }
        }

        return array_keys($keys);
    }

    /**
     * @param array<string, mixed> $call
     * @param string[] $dictionaryKeys
     */
    private function matchKeyPattern(array $call, array $dictionaryKeys): ?string
    {
        $pattern = $call['keyPattern'] ?? null;
        if (!is_string($pattern) || $pattern === '' || strpos($pattern, '*') === false) {
            return null;
        }

        // Require a meaningful literal prefix so patterns like "*.label" don't match every key.
        $firstWildcardPosition = strpos($pattern, '*');
        if ($firstWildcardPosition === false || $firstWildcardPosition < 3) {
            return null;
        }

        if ($dictionaryKeys === []) {
            return null;
        }

        $regex = '/^' . str_replace('\*', '.+', preg_quote($pattern, '/')) . '$/u';
        foreach ($dictionaryKeys as $key) {
            if (preg_match($regex, $key) === 1) {
                return $key;
            }
        }

        return null;
    }

    private function writeStatistics(OutputInterface $output, array $statistics): void
    {
        $resolvedDynamicPart = $statistics['resolvedDynamicPart'] ?? 0;
        $resolvedTotal = $statistics['resolvedStatic'] + $statistics['resolvedDynamic'] + $resolvedDynamicPart;
        $resolutionRate = $statistics['callsTotal'] > 0 ? ($resolvedTotal / $statistics['callsTotal']) * 100 : 0.0;
        $dynamicDenominator = $statistics['resolvedDynamic'] + $resolvedDynamicPart + $statistics['unresolvedDynamic'];
        $dynamicResolutionRate = $dynamicDenominator > 0
            ? (($statistics['resolvedDynamic'] + $resolvedDynamicPart) / $dynamicDenominator) * 100
            : 0.0;

        $output->writeln('', OutputInterface::VERBOSITY_VERBOSE);
        $output->writeln('==================== Analysis Statistics ====================', OutputInterface::VERBOSITY_VERBOSE);
        $output->writeln('[Overview]', OutputInterface::VERBOSITY_VERBOSE);
        $output->writeln(sprintf('  Total calls                  %6d', $statistics['callsTotal']), OutputInterface::VERBOSITY_VERBOSE);
        $output->writeln(sprintf('  Resolved total               %6d   %s', $resolvedTotal, $this->renderPercentBar($resolutionRate)), OutputInterface::VERBOSITY_VERBOSE);
        $output->writeln(sprintf('  Resolved static              %6d', $statistics['resolvedStatic']), OutputInterface::VERBOSITY_VERBOSE);
        $output->writeln(sprintf('  Resolved dynamic             %6d', $statistics['resolvedDynamic']), OutputInterface::VERBOSITY_VERBOSE);
        $output->writeln(sprintf('  Resolved dynamic - part key  %6d', $resolvedDynamicPart), OutputInterface::VERBOSITY_VERBOSE);
        $output->writeln(sprintf('  Unresolved dynamic           %6d', $statistics['unresolvedDynamic']), OutputInterface::VERBOSITY_VERBOSE);
        $output->writeln(sprintf('  Ignored by code              %6d', $statistics['ignoredByCode'] ?? 0), OutputInterface::VERBOSITY_VERBOSE);
        $output->writeln(sprintf('  Unresolved latte PHP variable - ignored %6d', $statistics['unresolvedLattePhpVariable'] ?? 0), OutputInterface::VERBOSITY_VERBOSE);
        $output->writeln(sprintf('  Dynamic success rate         %6.1f%%   %s', $dynamicResolutionRate, $this->renderPercentBar($dynamicResolutionRate)), OutputInterface::VERBOSITY_VERBOSE);

        $this->writeGroupedStatistics(
            $output,
            '[What Fails: Unresolved Dynamic Blockers]',
            $statistics['unresolvedStrategies'],
            $statistics['unresolvedStrategyExamples']
        );

        $this->writeGroupedStatistics(
            $output,
            '[Variables Behind Unresolved Cases]',
            $statistics['unresolvedVariables'],
            $statistics['unresolvedVariableExamples']
        );

        $this->writeGroupedStatistics(
            $output,
            '[Top Files With Unresolved Dynamic Keys]',
            $statistics['unresolvedFiles'],
            $statistics['unresolvedFileExamples'],
            true
        );

        $this->writeGroupedStatistics(
            $output,
            '[Resolved Dynamic Part Key Patterns]',
            $statistics['resolvedDynamicPartPatterns'] ?? [],
            $statistics['resolvedDynamicPartExamples'] ?? []
        );
        $output->writeln('=============================================================', OutputInterface::VERBOSITY_VERBOSE);
    }

    private function writeGroupedStatistics(OutputInterface $output, string $title, array $counts, array $examples, bool $fileMode = false): void
    {
        if ($counts === []) {
            return;
        }

        arsort($counts);
        $output->writeln('', OutputInterface::VERBOSITY_VERBOSE);
        $output->writeln($title, OutputInterface::VERBOSITY_VERBOSE);
        $output->writeln('  ----------------------------------------------------------', OutputInterface::VERBOSITY_VERBOSE);

        $index = 0;
        foreach ($counts as $label => $count) {
            $index++;
            if ($index > 10) {
                break;
            }

            $lineLabel = $fileMode ? $this->shortenPath($label) : $label;
            $output->writeln(sprintf(
                '  %2d. %-32s %6d%s',
                $index,
                $this->truncateLabel($lineLabel, 32),
                $count,
                $this->formatStatisticExample($examples[$label] ?? null)
            ), OutputInterface::VERBOSITY_VERBOSE);
        }
    }

    private function incrementGroupedStatistic(array &$counts, array &$examples, string $key, array $call, bool $preferFileExample = false): void
    {
        if (!isset($counts[$key])) {
            $counts[$key] = 0;
        }

        $counts[$key]++;
        if (!isset($examples[$key])) {
            $examples[$key] = $this->buildStatisticExample($call, $key, $preferFileExample);
        }
    }

    private function buildStatisticExample(array $call, string $label, bool $preferFileExample = false): string
    {
        if ($preferFileExample) {
            return sprintf(
                '%s:%s%s',
                $this->shortenPath((string) ($call['file'] ?? 'unknown')),
                $call['line'] ?? '?',
                isset($call['sourceExpression']) && is_string($call['sourceExpression']) && $call['sourceExpression'] !== ''
                    ? ' -> ' . $call['sourceExpression']
                    : ''
            );
        }

        $expression = $call['sourceExpression'] ?? null;
        if (!is_string($expression) || $expression === '') {
            $resolvedKey = $call['resolvedKeys'][0] ?? null;
            if (is_string($resolvedKey) && $resolvedKey !== '') {
                $expression = $resolvedKey;
            }
        }

        if (!is_string($expression) || $expression === '') {
            $expression = $call['call'] ?? $label;
        }

        return sprintf(
            '%s in %s:%s',
            $expression,
            $this->shortenPath((string) ($call['file'] ?? 'unknown')),
            $call['line'] ?? '?'
        );
    }

    private function formatStatisticExample(?string $example): string
    {
        if ($example === null || $example === '') {
            return '';
        }

        return sprintf("\n      example: %s", $example);
    }

    private function shortenPath(string $path): string
    {
        if (strpos($path, './') === 0) {
            return $path;
        }

        $appPosition = strpos($path, '/app/');
        if ($appPosition !== false) {
            return '.' . substr($path, $appPosition);
        }

        $srcPosition = strpos($path, '/src/');
        if ($srcPosition !== false) {
            return '.' . substr($path, $srcPosition);
        }

        return $path;
    }

    private function truncateLabel(string $label, int $maxLength): string
    {
        if (strlen($label) <= $maxLength) {
            return $label;
        }

        return substr($label, 0, $maxLength - 3) . '...';
    }

    private function renderPercentBar(float $percent): string
    {
        $normalizedPercent = max(0.0, min(100.0, $percent));
        $filled = (int) round($normalizedPercent / 10);
        $empty = 10 - $filled;

        return sprintf('[%s%s] %5.1f%%', str_repeat('#', $filled), str_repeat('.', $empty), $normalizedPercent);
    }

    private function formatStrategiesSuffix(array $strategies): string
    {
        if ($strategies === []) {
            return '';
        }

        return sprintf(' strategies: [%s]', implode(', ', array_unique($strategies)));
    }

    private function formatVariablesSuffix(array $variablesUsed): string
    {
        if ($variablesUsed === []) {
            return '';
        }

        return sprintf(' variables: [%s]', implode(', ', array_unique($variablesUsed)));
    }

    private function isLatteBareVariable(array $call): bool
    {
        if (($call['call'] ?? null) !== 'in_latte') {
            return false;
        }

        $expression = $call['sourceExpression'] ?? null;
        if (!is_string($expression)) {
            return false;
        }

        return preg_match('/^\$[A-Za-z_][A-Za-z0-9_]*$/', trim($expression)) === 1;
    }

    private function isIgnoredByCode(array $call): bool
    {
        $file = $call['file'] ?? null;
        $line = $call['line'] ?? null;
        if (!is_string($file) || !is_int($line) || $line <= 1) {
            return false;
        }

        $lines = $this->getFileLines($file);
        return $this->lineHasIgnoreMarker($lines[$line - 2] ?? null);
    }

    /**
     * @param array{file: string, line: int, text: string} $hardcoded
     */
    private function isHardcodedTextIgnored(array $hardcoded): bool
    {
        $lines = $this->getFileLines($hardcoded['file']);
        if ($this->lineHasIgnoreMarker($lines[$hardcoded['line'] - 1] ?? null)) {
            return true;
        }
        if ($hardcoded['line'] <= 1) {
            return false;
        }
        return $this->lineHasIgnoreMarker($lines[$hardcoded['line'] - 2] ?? null);
    }

    private function lineHasIgnoreMarker(?string $line): bool
    {
        if (!is_string($line)) {
            return false;
        }
        return strpos($line, self::IGNORE_DEEP_CHECK_MARKER) !== false
            || strpos($line, self::IGNORE_TRANSLATION_MARKER) !== false;
    }

    /**
     * @return array<int, string>
     */
    private function getFileLines(string $file): array
    {
        if (isset($this->fileLinesCache[$file])) {
            $cached = $this->fileLinesCache[$file];
            unset($this->fileLinesCache[$file]);
            $this->fileLinesCache[$file] = $cached;
            return $cached;
        }

        if (!is_file($file)) {
            $lines = [];
        } else {
            $loaded = @file($file);
            $lines = $loaded === false ? [] : $loaded;
        }

        $this->fileLinesCache[$file] = $lines;
        if (count($this->fileLinesCache) > self::FILE_LINES_CACHE_MAX) {
            array_shift($this->fileLinesCache);
        }

        return $lines;
    }
}

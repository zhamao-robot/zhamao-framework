<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestResult;
use PHPUnit\Runner\BaseTestRunner;
use PHPUnit\Runner\PhptTestCase;
use PHPUnit\Util\Color;
use PHPUnit\Util\TestDox\CliTestDoxPrinter;

class ZMResultPrinter extends CliTestDoxPrinter
{
    /**
     * The default Testdox left margin for messages is a vertical line.
     */
    private const PREFIX_SIMPLE = [
        'default' => '│',
        'start' => '│',
        'message' => '│',
        'diff' => '│',
        'trace' => '│',
        'last' => '│',
    ];

    /**
     * Colored Testdox use box-drawing for a more textured map of the message.
     */
    private const PREFIX_DECORATED = [
        'default' => '│',
        'start' => '┐',
        'message' => '→',
        'diff' => '┊',
        'trace' => '╵',
        'last' => '┴',
    ];

    /* @phpstan-ignore-next-line */
    private const SPINNER_ICONS = [
        " \e[36m◐\e[0m running tests",
        " \e[36m◓\e[0m running tests",
        " \e[36m◑\e[0m running tests",
        " \e[36m◒\e[0m running tests",
    ];

    private const STATUS_STYLES = [
        BaseTestRunner::STATUS_PASSED => [
            'symbol' => '✓',
            'color' => 'fg-green',
        ],
        BaseTestRunner::STATUS_ERROR => [
            'symbol' => '✘',
            'color' => 'fg-yellow',
            'message' => 'bg-yellow,fg-black',
        ],
        BaseTestRunner::STATUS_FAILURE => [
            'symbol' => '✘',
            'color' => 'fg-red',
            'message' => 'fg-red',
        ],
        BaseTestRunner::STATUS_SKIPPED => [
            'symbol' => '↩',
            'color' => 'fg-cyan',
            'message' => 'fg-cyan',
        ],
        BaseTestRunner::STATUS_RISKY => [
            'symbol' => '☢',
            'color' => 'fg-yellow',
            'message' => 'fg-yellow',
        ],
        BaseTestRunner::STATUS_INCOMPLETE => [
            'symbol' => '∅',
            'color' => 'fg-yellow',
            'message' => 'fg-yellow',
        ],
        BaseTestRunner::STATUS_WARNING => [
            'symbol' => '⚠',
            'color' => 'fg-yellow',
            'message' => 'fg-yellow',
        ],
        BaseTestRunner::STATUS_UNKNOWN => [
            'symbol' => '?',
            'color' => 'fg-blue',
            'message' => 'fg-white,bg-blue',
        ],
    ];

    public function printResult(TestResult $result): void
    {
        $this->writeNewLine();
        $this->write($this->colorizeTextBox('fg-white,bold', 'Tests:  '));
        $counts = [
            BaseTestRunner::STATUS_FAILURE => ['failed', $result->failureCount()],
            BaseTestRunner::STATUS_ERROR => ['errors', $result->errorCount()],
            BaseTestRunner::STATUS_SKIPPED => ['skipped', $result->skippedCount()],
            BaseTestRunner::STATUS_RISKY => ['risky', $result->riskyCount()],
            BaseTestRunner::STATUS_INCOMPLETE => ['incomplete', $result->notImplementedCount()],
            BaseTestRunner::STATUS_WARNING => ['warnings', $result->warningCount()],
            BaseTestRunner::STATUS_PASSED => ['passed', count($result->passed())],
        ];
        $counters = [];
        foreach ($counts as $status => $count) {
            if ($count[1] > 0) {
                $counters[] = $this->colorizeTextBox(self::STATUS_STYLES[$status]['color'], "{$count[1]} {$count[0]}");
            }
        }
        $this->writeWithColor('fg-white,bold', implode(', ', $counters));

        $this->write($this->colorizeTextBox('fg-white,bold', 'Time:   '));
        $this->writeWithColor('fg-default', sprintf('%fs', $result->time()));

        $this->printFooter($result);
    }

    protected function printFooter(TestResult $result): void
    {
        $non_passed = $result->failureCount() + $result->errorCount() + $result->warningCount();
        if ($non_passed === 0) {
            $color = 'bg-green,fg-white,bold';
            $text = '[GOOD] All tests passed';
        } else {
            $color = 'bg-red,fg-white,bold';
            $text = '[FAIL] Found ' . $non_passed . ' non-passed tests';
        }
        $this->writeWithColor($color, "\n {$text} ");
    }

    protected function writeTestResult(array $prevResult, array $result): void
    {
        // spacer line for new suite headers and after verbose messages
        if ($prevResult['testName'] !== ''
            && (!empty($prevResult['message']) || $prevResult['className'] !== $result['className'])) {
            $this->write(PHP_EOL);
        }

        // suite header
        if ($prevResult['className'] !== $result['className']) {
            [$class_short, $class_long] = explode(' (', $result['className']);
            $this->write($this->colorizeTextBox('fg-black,bg-cyan', " {$class_short} "));
            $this->write(' ' . rtrim($class_long, ')') . PHP_EOL);
        }

        // test result line
        if ($this->colors && $result['className'] === PhptTestCase::class) {
            $testName = Color::colorizePath($result['testName'], $prevResult['testName'], true);
        } else {
            $testName = $result['testMethod'];
        }
        $testName = strtolower($testName);

        $style = self::STATUS_STYLES[$result['status']];
        $line = sprintf(
            ' %s %s',
            $this->colorizeTextBox($style['color'], $style['symbol']),
            $this->colorizeTextBox('dim', $testName),
        );

        $this->write($line);

        // additional information when verbose
        $this->writeNewLine();
        if (!empty($result['message'])) {
            $this->write(' ' . $result['message']);
        }
    }

    protected function formatTestResultMessage(\Throwable $t, array $result, ?string $prefix = null): string
    {
        $message = $this->formatThrowable($t, $result['status']);
        $diff = '';

        if (!($this->verbose || $result['verbose'])) {
            return '';
        }

        if ($message && $this->colors) {
            $style = self::STATUS_STYLES[$result['status']]['message'] ?? '';
            [$message, $diff] = $this->colorizeMessageAndDiff($style, $message);
        }

        if ($prefix === null || !$this->colors) {
            $prefix = self::PREFIX_SIMPLE;
        }

        if ($this->colors) {
            $color = self::STATUS_STYLES[$result['status']]['color'] ?? '';
            $prefix = array_map(static fn ($p) => Color::colorize($color, $p), self::PREFIX_DECORATED);
        }

        $out = '';

        if ($message) {
            $out .= $this->prefixLines($prefix['message'], $message . PHP_EOL) . PHP_EOL;
        }

        if ($diff) {
            $out .= $this->prefixLines($prefix['diff'], $diff . PHP_EOL) . PHP_EOL;
        }

        return $out;
    }
}

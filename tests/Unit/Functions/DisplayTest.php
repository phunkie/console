<?php

/*
 * This file is part of Phunkie, library with functional structures for PHP.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Phunkie\Console\Functions;

use PHPUnit\Framework\TestCase;
use Phunkie\Console\Types\ReplSession;
use Phunkie\Effect\IO\IO;

use function Phunkie\Console\Functions\{printHelp, printVariables, printHistory, printBanner};

class DisplayTest extends TestCase
{
    public function testPrintHelpReturnsIO(): void
    {
        $result = printHelp();

        $this->assertInstanceOf(IO::class, $result);
    }

    public function testPrintVariablesWithEmptySessionReturnsNoVariablesMessage(): void
    {
        $session = ReplSession::empty();

        $result = printVariables($session);

        $this->assertInstanceOf(IO::class, $result);

        // Capture output
        ob_start();
        $result->unsafeRun();
        $output = ob_get_clean();

        $this->assertStringContainsString('No variables defined', $output);
    }

    public function testPrintVariablesDisplaysDefinedVariables(): void
    {
        $session = ReplSession::empty();

        // Add some variables
        $session = new ReplSession(
            $session->history,
            ImmMap(['$x' => 42, '$y' => 'hello']),
            $session->colorEnabled,
            $session->variableCounter,
            $session->incompleteInput,
            $session->currentNamespace,
            $session->useStatements
        );

        $result = printVariables($session);

        ob_start();
        $result->unsafeRun();
        $output = ob_get_clean();

        $this->assertStringContainsString('Defined variables', $output);
        $this->assertStringContainsString('$x', $output);
        $this->assertStringContainsString('$y', $output);
        $this->assertStringContainsString('42', $output);
        $this->assertStringContainsString('hello', $output);
    }

    public function testPrintHistoryWithEmptySessionReturnsNoHistoryMessage(): void
    {
        $session = ReplSession::empty();

        $result = printHistory($session);

        $this->assertInstanceOf(IO::class, $result);

        ob_start();
        $result->unsafeRun();
        $output = ob_get_clean();

        $this->assertStringContainsString('No history', $output);
    }

    public function testPrintHistoryDisplaysCommands(): void
    {
        $session = ReplSession::empty();

        // Add some history
        $session = new ReplSession(
            ImmList('1 + 1', '2 + 2', '3 + 3'),
            $session->variables,
            $session->colorEnabled,
            $session->variableCounter,
            $session->incompleteInput,
            $session->currentNamespace,
            $session->useStatements
        );

        $result = printHistory($session);

        ob_start();
        $result->unsafeRun();
        $output = ob_get_clean();

        $this->assertStringContainsString('Command history', $output);
        // History is reversed, so most recent first
        $this->assertStringContainsString('1.', $output);
        $this->assertStringContainsString('3 + 3', $output);
        $this->assertStringContainsString('2 + 2', $output);
        $this->assertStringContainsString('1 + 1', $output);
    }

    public function testPrintBannerReturnsIO(): void
    {
        $result = printBanner(false);

        $this->assertInstanceOf(IO::class, $result);
    }

    public function testPrintBannerDisplaysWelcomeMessage(): void
    {
        $result = printBanner(false);

        ob_start();
        $result->unsafeRun();
        $output = ob_get_clean();

        $this->assertStringContainsString('Welcome to phunkie console', $output);
        $this->assertStringContainsString('Type in expressions', $output);
    }

    public function testPrintBannerWithColorsIncludesAnsiCodes(): void
    {
        $result = printBanner(true);

        ob_start();
        $result->unsafeRun();
        $output = ob_get_clean();

        // Should contain ANSI color codes when colors enabled
        $this->assertStringContainsString("\033[", $output);
    }

    public function testPrintBannerWithoutColorsNoAnsiCodes(): void
    {
        $result = printBanner(false);

        ob_start();
        $result->unsafeRun();
        $output = ob_get_clean();

        // Should not contain ANSI color codes when colors disabled
        $this->assertStringNotContainsString("\033[", $output);
    }
}

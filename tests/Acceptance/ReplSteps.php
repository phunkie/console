<?php

/*
 * This file is part of Phunkie, library with functional structures for PHP.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Acceptance;

use Behat\Gherkin\Node\PyStringNode;
use Behat\Step\Then;
use Behat\Step\When;
use Behat\Step\Given;
use Behat\Behat\Context\Context;
use Tests\Acceptance\Support\ReplProcessManager;
use Tests\Acceptance\Support\ReplOutputReader;
use Tests\Acceptance\Support\TestFileManager;
use Tests\Acceptance\Support\StringHelper;

class ReplSteps implements Context
{
    private ReplProcessManager $processManager;
    private TestFileManager $fileManager;
    private string $output = '';
    private array $inputs = [];
    private int $variableCount = 0;
    private bool $hasExited = false;

    public function __construct()
    {
        $projectRoot = __DIR__ . '/../../';
        $this->processManager = new ReplProcessManager($projectRoot);
        $this->fileManager = new TestFileManager($projectRoot);
    }

    private function startRepl(string $command = 'php bin/phunkie'): void
    {
        $this->processManager->start($command);

        // Read initial banner and prompt
        $this->readOutput();
    }

    private function readOutput(): string
    {
        $stdout = $this->processManager->getStdout();
        if ($stdout === null) {
            return '';
        }

        $newOutput = ReplOutputReader::readOutput($stdout);
        $this->output .= $newOutput;
        return $newOutput;
    }

    private function waitForPrompt(float $timeout = 0.15): bool
    {
        $stdout = $this->processManager->getStdout();
        if ($stdout === null) {
            return false;
        }

        $buffer = '';
        $startTime = microtime(true);

        while ((microtime(true) - $startTime) < $timeout) {
            // Use ReplOutputReader to check if data is available
            $read = [$stdout];
            $write = null;
            $except = null;
            $result = stream_select($read, $write, $except, 0, 10000);

            if ($result === false) {
                return false;
            }

            if ($result > 0) {
                $chunk = stream_get_contents($stdout);
                if ($chunk !== false && $chunk !== '') {
                    $buffer .= $chunk;
                    $this->output .= $chunk;

                    // Check if we found a prompt using the helper
                    if (str_ends_with($buffer, 'phunkie > ') ||
                        str_ends_with($buffer, 'phunkie { ')) {
                        return true;
                    }
                }
            }

            usleep(1000);
        }

        return !empty($buffer);
    }

    private function sendInput(string $input): void
    {
        $this->processManager->sendInput($input);

        // Wait for the prompt using efficient stream_select
        $this->waitForPrompt();
    }

    private function cleanup(): void
    {
        $this->processManager->terminate();
        $this->fileManager->cleanup();

        $this->output = '';
        $this->inputs = [];
        $this->variableCount = 0;
        $this->hasExited = false;
    }

    #[Given('I start the REPL')]
    public function iStartTheRepl(): void
    {
        // Don't clean up files yet - they may be needed
        // Only cleanup the process
        $this->processManager->terminate();

        $this->output = '';
        $this->inputs = [];
        $this->variableCount = 0;
        $this->hasExited = false;

        $this->startRepl();
    }

    #[Given('I run :command')]
    public function iRun(string $command): void
    {
        $this->cleanup();
        $this->startRepl($command);
    }

    #[When('/^I enter "(.+)"$/')]
    #[When("/^I enter '(.+)'\$/")]
    public function iEnter(string $input): void
    {
        // Unescape quotes that were escaped in the feature file
        $input = StringHelper::unescape($input);

        $this->inputs[] = $input;
        $this->sendInput($input);

        // Track if this creates a variable
        if (!str_starts_with($input, ':')) {
            $this->variableCount++;
        }
    }

    #[Then('/^I should see output containing "(.+)"$/')]
    #[Then("/^I should see output containing '(.+)'\$/")]
    public function iShouldSeeOutputContaining(string $expected): void
    {
        // Unescape quotes that were escaped in the feature file
        $expected = StringHelper::unescape($expected);

        if (!str_contains($this->output, $expected)) {
            throw new \Exception(
                "Expected output to contain '$expected'\n" .
                "Actual output:\n" . $this->output
            );
        }
    }

    #[Then('I should see the welcome banner')]
    public function iShouldSeeTheWelcomeBanner(): void
    {
        $this->iShouldSeeOutputContaining('Welcome to');
        $this->iShouldSeeOutputContaining('phunkie');
        $this->iShouldSeeOutputContaining('console');
    }

    #[Then('the REPL should support colors')]
    public function theReplShouldSupportColors(): void
    {
        // When -c flag is used, the prompt should have color codes
        if (!str_contains($this->output, "\033[")) {
            throw new \Exception(
                "Expected output to contain ANSI color codes\n" .
                "Actual output:\n" . $this->output
            );
        }
    }

    #[Then('/^I should see "(.+)"$/')]
    public function iShouldSee(string $expected): void
    {
        // Unescape quotes that were escaped in the feature file
        $expected = StringHelper::unescape($expected);
        $this->iShouldSeeOutputContaining($expected);
    }

    #[Then('the session should have :count variables')]
    public function theSessionShouldHaveVariables(int $count): void
    {
        // Verify by checking the output contains the expected variable names
        for ($i = 0; $i < $count; $i++) {
            $varName = '$var' . $i;
            if (!str_contains($this->output, $varName)) {
                throw new \Exception(
                    "Expected session to have variable $varName\n" .
                    "Actual output:\n" . $this->output
                );
            }
        }
    }

    #[Then('the REPL should exit gracefully')]
    public function theReplShouldExitGracefully(): void
    {
        // Check for bye message
        $this->iShouldSeeOutputContaining('bye \o');
        $this->hasExited = true;
    }

    #[Given('I have a file :filename with content:')]
    public function iHaveAFileWithContent(string $filename, PyStringNode $content): void
    {
        $this->fileManager->createFile($filename, $content->getRaw());
    }

    #[Given('I am running the repl')]
    public function iAmRunningTheRepl(): void
    {
        $this->cleanup();
        $this->startRepl();
    }

    #[When('/^I type "(.+)"$/')]
    public function iType(string $input): void
    {
        // Unescape quotes that were escaped in the feature file
        $input = StringHelper::unescape($input);
        $this->inputs[] = $input;
    }

    #[When('I press enter')]
    public function iPressEnter(): void
    {
        if (empty($this->inputs)) {
            throw new \RuntimeException('No input to send');
        }

        $input = array_pop($this->inputs);
        $this->sendInput($input);

        // Track if this creates a variable
        if (!str_starts_with($input, ':')) {
            $this->variableCount++;
        }
    }

    #[When('I enter the following code:')]
    public function iEnterTheFollowingCode(PyStringNode $string): void
    {
        // Split the multi-line input into individual lines and send each one
        $lines = explode("\n", $string->getRaw());

        foreach ($lines as $line) {
            $this->sendInput($line);
        }

        // Track variable creation - only one variable is created for the entire multi-line input
        $this->variableCount++;
    }

    #[Then('I should see output containing :expected in :variable')]
    public function iShouldSeeOutputContainingInVariable(string $expected, string $variable): void
    {
        // This checks that a specific variable contains an expected value
        $pattern = preg_quote($variable, '/') . '.*' . preg_quote($expected, '/');
        if (!preg_match('/' . $pattern . '/s', $this->output)) {
            throw new \Exception(
                "Expected output to contain '$expected' in variable '$variable'\n" .
                "Actual output:\n" . $this->output
            );
        }
    }

    #[Then('I should not see :unexpected')]
    public function iShouldNotSee(string $unexpected): void
    {
        if (str_contains($this->output, $unexpected)) {
            throw new \Exception(
                "Expected output NOT to contain '$unexpected'\n" .
                "Actual output:\n" . $this->output
            );
        }
    }

    #[Then('I should see an error containing :expected')]
    #[Then('I should see error containing :expected')]
    public function iShouldSeeErrorContaining(string $expected): void
    {
        if (!str_contains($this->output, 'Error') && !str_contains($this->output, 'error')) {
            throw new \Exception(
                "Expected output to contain an error\n" .
                "Actual output:\n" . $this->output
            );
        }

        if (!str_contains($this->output, $expected)) {
            throw new \Exception(
                "Expected error to contain '$expected'\n" .
                "Actual output:\n" . $this->output
            );
        }
    }

    public function __destruct()
    {
        $this->cleanup();
    }
}

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
use Tests\Acceptance\Support\DirectReplManager;
use Tests\Acceptance\Support\ReplProcessManager;
use Tests\Acceptance\Support\ReplOutputReader;
use Tests\Acceptance\Support\TestFileManager;
use Tests\Acceptance\Support\StringHelper;

class ReplSteps implements Context
{
    private DirectReplManager $directManager;
    private ReplProcessManager $processManager;
    private TestFileManager $fileManager;
    private string $output = '';
    private array $inputs = [];
    private array $sentInputs = []; // Track all inputs sent so far for replay when switching managers
    private int $variableCount = 0;
    private bool $hasExited = false;
    private bool $useProcessManager = false;

    public function __construct()
    {
        $projectRoot = __DIR__ . '/../../';
        $this->directManager = new DirectReplManager();
        $this->processManager = new ReplProcessManager($projectRoot);
        $this->fileManager = new TestFileManager($projectRoot);
    }

    private function startRepl(string $command = 'php bin/phunkie'): void
    {
        if ($command !== 'php bin/phunkie' && $command !== 'php bin/phunkie -c') {
            // Non-standard command means we need to test the actual process
            $this->useProcessManager = true;
        }

        if ($this->useProcessManager) {
            $this->processManager->start($command);
            $stdout = $this->processManager->getStdout();
            if ($stdout !== null) {
                $newOutput = ReplOutputReader::readOutput($stdout);
                $this->output .= $newOutput;
            }
        } else {
            $colorEnabled = str_contains($command, '-c');
            $this->directManager->start($colorEnabled);
            $this->output = $this->directManager->getOutput();
        }
    }

    private function sendInput(string $input): void
    {
        // Check if input defines a class/function/trait/interface/enum
        // If so, switch to process manager to avoid redeclaration errors
        if (!$this->useProcessManager && $this->definesUserType($input)) {
            $this->switchToProcessManager();
        }

        if ($this->useProcessManager) {
            $this->processManager->sendInput($input);
            $stdout = $this->processManager->getStdout();
            if ($stdout !== null) {
                $newOutput = ReplOutputReader::readOutput($stdout);
                $this->output .= $newOutput;
            }
        } else {
            $newOutput = $this->directManager->sendInput($input);
            $this->output .= $newOutput;
        }

        // Track this input after sending it, for potential replay when switching managers later
        $this->sentInputs[] = $input;
    }

    private function definesUserType(string $input): bool
    {
        // Switch to process manager for:
        // 1. Class/function/trait/interface/enum DEFINITIONS (to avoid redeclaration)
        // 2. Specific patterns with known output differences between direct and process managers
        $patterns = [
            '/^\s*(abstract\s+)?(class|trait|interface|enum)\s+\w+/im',
            '/^\s*function\s+\w+\s*\(/im',
            '/^\s*#\[\w+\]\s*$/im', // Attribute on its own line (precedes class definition)
            // Removed throw pattern - DirectReplManager handles throw expressions correctly
            '/\.\.\.\\\$/i', // Array spreading (spread operator with literal $)
            '/\[[^\]]*=>[^\]]*\]/i', // Associative arrays (any key => value in array)
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }

    private function switchToProcessManager(): void
    {
        // We need to switch from direct manager to process manager mid-test
        // Start the process and discard its initial output (we already have it from direct manager)
        $this->useProcessManager = true;
        $this->processManager->start();

        // Drain the initial banner/prompt
        $stdout = $this->processManager->getStdout();
        if ($stdout !== null) {
            ReplOutputReader::readOutput($stdout);
        }

        // Replay all previous inputs to bring the process manager to the same state as direct manager
        foreach ($this->sentInputs as $previousInput) {
            $this->processManager->sendInput($previousInput);
            $stdout = $this->processManager->getStdout();
            if ($stdout !== null) {
                // Drain the output but don't add to $this->output since we already have it from direct manager
                ReplOutputReader::readOutput($stdout);
            }
        }
    }

    private function cleanup(): void
    {
        if ($this->useProcessManager) {
            $this->processManager->terminate();
        } else {
            $this->directManager->reset();
        }

        $this->fileManager->cleanup();

        $this->output = '';
        $this->inputs = [];
        $this->sentInputs = [];
        $this->variableCount = 0;
        $this->hasExited = false;
        $this->useProcessManager = false;
    }

    #[Given('I start the REPL')]
    public function iStartTheRepl(): void
    {
        // Don't clean up files yet - they may be needed
        // Reset whichever manager we're using
        if ($this->useProcessManager) {
            $this->processManager->terminate();
        } else {
            $this->directManager->reset();
        }

        $this->output = '';
        $this->inputs = [];
        $this->sentInputs = [];
        $this->variableCount = 0;
        $this->hasExited = false;
        $this->useProcessManager = $this->shouldUseProcessManager(); // Check if we need process manager

        $this->startRepl();
    }

    private function shouldUseProcessManager(): bool
    {
        // For now, default to direct manager
        // We'll switch to process manager when we detect specific patterns during sendInput
        return false;
    }

    #[Given('I run :command')]
    public function iRun(string $command): void
    {
        $this->cleanup();
        $this->useProcessManager = true; // Always use process manager for "I run" scenarios
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

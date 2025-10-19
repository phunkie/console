<?php
/*
 * This file is part of Phunkie, library with functional structures for PHP.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Acceptance\Support;

use Phunkie\Console\Types\ReplSession;
use Phunkie\Console\Types\ContinueRepl;
use Phunkie\Console\Types\ExitRepl;
use function Phunkie\Console\Repl\processInput;
use function Phunkie\Console\Functions\setColors;

/**
 * Direct REPL manager that calls REPL functions directly without process/stream complexity.
 * This is faster and more reliable than the stream-based approach for most tests.
 */
class DirectReplManager
{
    private ReplSession $session;
    private string $output = '';

    public function __construct()
    {
        // Create initial session
        $initialSession = ReplSession::empty();
        $pair = setColors(false)->run($initialSession);
        $this->session = $pair->_1;
    }

    public function start(bool $colorEnabled = false): void
    {
        // Reset session
        $initialSession = ReplSession::empty();
        $pair = setColors($colorEnabled)->run($initialSession);
        $this->session = $pair->_1;
        $this->output = '';

        // Add welcome banner
        $this->output .= "Welcome to phunkie console.\n\n";
        $this->output .= "Type in expressions to have them evaluated.\n\n";
    }

    public function sendInput(string $input): string
    {
        // Echo the input to match ProcessManager behavior (which shows what was typed)
        $echoedInput = $input . "\n";

        // Capture output using output buffering
        ob_start();

        try {
            $result = processInput($input, $this->session)->unsafeRun();

            if ($result instanceof ExitRepl) {
                echo "\nbye \\o\n";
                $output = ob_get_clean();
                $fullOutput = $echoedInput . $output;
                $this->output .= $fullOutput;
                return $fullOutput;
            }

            if ($result instanceof ContinueRepl) {
                $this->session = $result->session;
                $output = ob_get_clean();
                $fullOutput = $echoedInput . $output;
                $this->output .= $fullOutput;
                return $fullOutput;
            }

            $output = ob_get_clean();
            $fullOutput = $echoedInput . $output;
            $this->output .= $fullOutput;
            return $fullOutput;
        } catch (\Throwable $e) {
            ob_end_clean();
            // Get the exception class name to match ProcessManager output format
            $className = (new \ReflectionClass($e))->getShortName();
            $errorOutput = $echoedInput . $className . ": " . $e->getMessage() . "\n";
            $this->output .= $errorOutput;
            return $errorOutput;
        }
    }

    public function getOutput(): string
    {
        return $this->output;
    }

    public function reset(): void
    {
        $this->output = '';
        $initialSession = ReplSession::empty();
        $pair = setColors(false)->run($initialSession);
        $this->session = $pair->_1;
    }

    public function isRunning(): bool
    {
        return true; // Direct manager is always "running"
    }

    public function terminate(): void
    {
        // Nothing to terminate in direct mode
    }
}

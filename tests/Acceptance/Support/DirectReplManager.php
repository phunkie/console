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
        // Capture output using output buffering
        ob_start();

        try {
            $result = processInput($input, $this->session)->unsafeRun();

            if ($result instanceof ExitRepl) {
                echo "\nbye \\o\n";
                $output = ob_get_clean();
                $this->output .= $output;
                return $output;
            }

            if ($result instanceof ContinueRepl) {
                $this->session = $result->session;
                $output = ob_get_clean();
                $this->output .= $output;
                return $output;
            }

            $output = ob_get_clean();
            $this->output .= $output;
            return $output;
        } catch (\Throwable $e) {
            ob_end_clean();
            $errorOutput = "Error: " . $e->getMessage() . "\n";
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

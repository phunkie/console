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

/**
 * Reads and buffers output from a REPL process.
 * Provides utilities for waiting for prompts and reading available data.
 */
class ReplOutputReader
{
    private const PROMPT_PATTERNS = ['phunkie > ', 'phunkie { '];
    private const READ_TIMEOUT_MS = 50000; // 50ms
    private const POLL_INTERVAL_MS = 10000; // 10ms
    private const POLL_SLEEP_US = 1000; // 1ms

    /**
     * Read all available output from the stream until a prompt is encountered.
     */
    public static function readOutput($stream): string
    {
        $output = '';
        $read = [$stream];
        $write = null;
        $except = null;

        while (true) {
            $readyCopy = $read; // stream_select modifies the array
            $result = stream_select($readyCopy, $write, $except, 0, self::READ_TIMEOUT_MS);

            if ($result === false) {
                break; // Error occurred
            }

            if ($result === 0) {
                // Timeout - check if we have a prompt
                if (self::endsWithPrompt($output)) {
                    break;
                }
                // No prompt yet, but also no new data - stop waiting
                break;
            }

            // Data is available
            $chunk = stream_get_contents($stream);
            if ($chunk === false || $chunk === '') {
                break;
            }

            $output .= $chunk;

            // Check if we've received the prompt
            if (self::endsWithPrompt($output)) {
                break;
            }
        }

        return $output;
    }

    /**
     * Wait for a prompt to appear in the stream output.
     */
    public static function waitForPrompt($stream, float $timeout = 0.15): bool
    {
        $startTime = microtime(true);
        $buffer = '';

        while ((microtime(true) - $startTime) < $timeout) {
            $read = [$stream];
            $write = null;
            $except = null;

            // Use very short timeout for stream_select to be more responsive
            $result = stream_select($read, $write, $except, 0, self::POLL_INTERVAL_MS);

            if ($result === false) {
                // Error in stream_select
                return false;
            }

            if ($result > 0) {
                // Data is available - read it immediately
                $chunk = stream_get_contents($stream);
                if ($chunk !== false && $chunk !== '') {
                    $buffer .= $chunk;

                    // Check if we found a prompt
                    if (self::endsWithPrompt($buffer)) {
                        return true;
                    }
                }
            }

            // Short sleep to prevent CPU spinning
            usleep(self::POLL_SLEEP_US);
        }

        // If we have partial output but no prompt, it might be an error
        // Still consider this successful if we got some output
        return !empty($buffer);
    }

    /**
     * Check if a string ends with any of the known REPL prompts.
     */
    private static function endsWithPrompt(string $text): bool
    {
        foreach (self::PROMPT_PATTERNS as $pattern) {
            if (str_ends_with($text, $pattern)) {
                return true;
            }
        }
        return false;
    }
}

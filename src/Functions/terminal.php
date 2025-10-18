<?php

/*
 * This file is part of Phunkie, library with functional structures for PHP.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phunkie\Console\Functions;

use Phunkie\Effect\IO\IO;

/**
 * Enhanced readLine that filters control characters and ANSI escape sequences.
 *
 * This prevents printing ^H and other control characters when arrow keys are pressed.
 * Returns null when EOF is detected (Control-D).
 * Supports full readline with command history and editing when available.
 *
 * @param string $prompt
 * @return IO<string|null>
 */
function readLineFiltered(string $prompt): IO
{
    return new IO(function () use ($prompt) {
        // Check if readline extension is available
        if (function_exists('readline')) {
            // Use PHP's readline which handles arrow keys properly
            // Returns false on EOF (Control-D)
            $line = readline($prompt);

            // Add to readline history if non-empty
            if ($line !== false && trim($line) !== '') {
                readline_add_history($line);
            }

            return $line !== false ? $line : null;
        }

        // Fallback: use stream with control character filtering
        print($prompt);

        // Set terminal to raw mode to capture individual characters
        if (function_exists('stream_set_blocking')) {
            stream_set_blocking(STDIN, true);
        }

        $line = fgets(STDIN);

        // Return null on EOF (Control-D)
        if ($line === false) {
            return null;
        }

        // Remove ANSI escape sequences and control characters
        // This regex removes:
        // - ANSI CSI sequences (ESC[...)
        // - Control characters except newline/carriage return
        $cleaned = preg_replace('/\x1b\[[0-9;]*[a-zA-Z]/', '', $line);
        $cleaned = preg_replace('/[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F]/', '', $cleaned);

        return rtrim($cleaned, "\r\n");
    });
}

/**
 * Check if readline extension is available.
 *
 * @return bool
 */
function hasReadlineSupport(): bool
{
    return function_exists('readline');
}

/**
 * Get the path to the history file.
 *
 * Uses ~/.phunkie_history as the default location.
 * Falls back to /tmp/.phunkie_history if home directory cannot be determined.
 *
 * @return string Absolute path to history file
 */
function getHistoryFilePath(): string
{
    $home = getenv('HOME') ?: getenv('USERPROFILE');

    if ($home) {
        return $home . '/.phunkie_history';
    }

    return '/tmp/.phunkie_history';
}

/**
 * Load command history from persistent file.
 *
 * Loads the last 500 commands from the history file and adds them
 * to readline's history. Creates the history file if it doesn't exist.
 *
 * @return IO<int> Number of history entries loaded
 */
function loadHistory(): IO
{
    return new IO(function () {
        if (!function_exists('readline_read_history')) {
            return 0;
        }

        $historyFile = getHistoryFilePath();

        // Create history file if it doesn't exist
        if (!file_exists($historyFile)) {
            touch($historyFile);
            return 0;
        }

        // Read history file and load into readline
        // readline_read_history loads the entire file
        readline_read_history($historyFile);

        // Count lines in history file
        $lines = file($historyFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        return $lines ? count($lines) : 0;
    });
}

/**
 * Save command history to persistent file.
 *
 * Saves the current readline history to the history file, keeping
 * only the last 500 commands to prevent the file from growing too large.
 *
 * @return IO<bool> True if history was saved successfully
 */
function saveHistory(): IO
{
    return new IO(function () {
        if (!function_exists('readline_write_history')) {
            return false;
        }

        $historyFile = getHistoryFilePath();

        // Write current history to file
        readline_write_history($historyFile);

        // Trim history file to last 500 lines
        if (file_exists($historyFile)) {
            $lines = file($historyFile, FILE_IGNORE_NEW_LINES);
            if ($lines && count($lines) > 500) {
                $trimmed = array_slice($lines, -500);
                file_put_contents($historyFile, implode(PHP_EOL, $trimmed) . PHP_EOL);
            }
        }

        return true;
    });
}

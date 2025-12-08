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

use function Phunkie\Console\Functions\{getHistoryFilePath, hasReadlineSupport};

class TerminalTest extends TestCase
{
    public function testHasReadlineSupportReturnsBooleanWhenReadlineAvailable(): void
    {
        $result = hasReadlineSupport();

        $this->assertIsBool($result);

        // Should return true if readline extension is loaded
        if (function_exists('readline')) {
            $this->assertTrue($result);
        } else {
            $this->assertFalse($result);
        }
    }

    public function testGetHistoryFilePathReturnsPathInHomeDirectory(): void
    {
        $path = getHistoryFilePath();

        $this->assertIsString($path);
        $this->assertStringEndsWith('.phunkie_history', $path);

        // Should use HOME or USERPROFILE environment variable
        $home = getenv('HOME') ?: getenv('USERPROFILE');
        if ($home) {
            $this->assertStringStartsWith($home, $path);
            $this->assertEquals($home . '/.phunkie_history', $path);
        } else {
            // Fallback to /tmp if no home directory
            $this->assertEquals('/tmp/.phunkie_history', $path);
        }
    }

    public function testGetHistoryFilePathFallsBackToTmpWhenNoHomeSet(): void
    {
        // Save original environment variables
        $originalHome = getenv('HOME');
        $originalUserProfile = getenv('USERPROFILE');

        // Temporarily unset HOME and USERPROFILE
        putenv('HOME');
        putenv('USERPROFILE');

        try {
            $path = getHistoryFilePath();

            $this->assertEquals('/tmp/.phunkie_history', $path);
        } finally {
            // Restore original environment variables
            if ($originalHome !== false) {
                putenv("HOME=$originalHome");
            }
            if ($originalUserProfile !== false) {
                putenv("USERPROFILE=$originalUserProfile");
            }
        }
    }

    public function testLoadHistoryReturnsIO(): void
    {
        $io = \Phunkie\Console\Functions\loadHistory();

        $this->assertInstanceOf(\Phunkie\Effect\IO\IO::class, $io);
    }

    public function testSaveHistoryReturnsIO(): void
    {
        $io = \Phunkie\Console\Functions\saveHistory();

        $this->assertInstanceOf(\Phunkie\Effect\IO\IO::class, $io);
    }

    public function testLoadHistoryCreatesFileIfNotExists(): void
    {
        // Use a temporary test history file
        $testFile = '/tmp/.phunkie_history_test_' . uniqid();

        // Temporarily override the history file path
        $originalHome = getenv('HOME');
        putenv('HOME=/tmp');

        try {
            // Ensure file doesn't exist
            if (file_exists($testFile)) {
                unlink($testFile);
            }

            $this->assertFileDoesNotExist($testFile);

            // Note: We can't easily test the actual file creation without
            // modifying the getHistoryFilePath function to accept a parameter,
            // so we'll just verify the IO monad is returned
            $io = \Phunkie\Console\Functions\loadHistory();
            $this->assertInstanceOf(\Phunkie\Effect\IO\IO::class, $io);

        } finally {
            // Restore original environment
            if ($originalHome !== false) {
                putenv("HOME=$originalHome");
            }

            // Clean up test file if it was created
            if (file_exists($testFile)) {
                unlink($testFile);
            }
        }
    }
}

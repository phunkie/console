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
 * Manages the lifecycle of a REPL process for testing.
 * Handles process creation, communication, and cleanup.
 */
class ReplProcessManager
{
    private ?array $processDescriptors = null;
    private ?array $pipes = null;
    private $process = null;
    private string $projectRoot;

    public function __construct(string $projectRoot)
    {
        $this->projectRoot = $projectRoot;
    }

    public function start(string $command = 'php bin/phunkie'): void
    {
        if ($this->process !== null) {
            return; // Already started
        }

        $this->processDescriptors = [
            0 => ['pipe', 'r'],  // stdin
            1 => ['pipe', 'w'],  // stdout
            2 => ['pipe', 'w'],  // stderr
        ];

        $this->process = proc_open(
            $command,
            $this->processDescriptors,
            $this->pipes,
            $this->projectRoot
        );

        if (!is_resource($this->process)) {
            throw new \RuntimeException('Failed to start REPL process');
        }

        // Set streams to non-blocking
        stream_set_blocking($this->pipes[1], false);
        stream_set_blocking($this->pipes[2], false);
    }

    public function sendInput(string $input): void
    {
        if ($this->process === null) {
            throw new \RuntimeException('REPL not started');
        }

        fwrite($this->pipes[0], $input . "\n");
        fflush($this->pipes[0]);
        usleep(100000); // Wait 100ms for REPL to process input
    }

    public function getStdout()
    {
        return $this->pipes[1] ?? null;
    }

    public function getStderr()
    {
        return $this->pipes[2] ?? null;
    }

    public function isRunning(): bool
    {
        return $this->process !== null;
    }

    public function getStatus(): ?array
    {
        if ($this->process === null) {
            return null;
        }
        return proc_get_status($this->process);
    }

    public function terminate(): void
    {
        if ($this->process !== null) {
            if (is_resource($this->pipes[0])) {
                fclose($this->pipes[0]);
            }
            if (is_resource($this->pipes[1])) {
                fclose($this->pipes[1]);
            }
            if (is_resource($this->pipes[2])) {
                fclose($this->pipes[2]);
            }
            proc_terminate($this->process);
            proc_close($this->process);
            $this->process = null;
        }
    }

    public function __destruct()
    {
        $this->terminate();
    }
}

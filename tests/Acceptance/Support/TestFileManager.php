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
 * Manages test files created during Behat scenarios.
 * Handles file creation and cleanup.
 */
class TestFileManager
{
    private string $projectRoot;
    private array $createdFiles = [];

    public function __construct(string $projectRoot)
    {
        $this->projectRoot = $projectRoot;
    }

    /**
     * Create a test file with the given content.
     */
    public function createFile(string $filename, string $content): void
    {
        $filePath = $this->projectRoot . '/' . $filename;
        file_put_contents($filePath, $content);
        $this->createdFiles[] = $filePath;
    }

    /**
     * Clean up all created test files.
     */
    public function cleanup(): void
    {
        foreach ($this->createdFiles as $filePath) {
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        $this->createdFiles = [];
    }

    /**
     * Get the list of created files.
     */
    public function getCreatedFiles(): array
    {
        return $this->createdFiles;
    }
}

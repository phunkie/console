<?php

/*
 * This file is part of Phunkie, library with functional structures for PHP.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phunkie\Console\Types;

final class CommandError extends ReplError
{
    public function __construct(
        public readonly string $command,
        public readonly string $reason
    ) {
    }

    public function message(): string
    {
        return "Command error: {$this->reason}\nCommand: {$this->command}";
    }
}

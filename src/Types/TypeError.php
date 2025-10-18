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

/**
 * Type error during expression evaluation.
 *
 * Represents errors related to type mismatches in function calls,
 * method calls, and other type-sensitive operations.
 */
class TypeError extends ReplError
{
    public function __construct(
        public readonly string $subject,
        public readonly string $reason
    ) {}

    public function message(): string
    {
        return sprintf("Type error: %s\n  Expression: %s", $this->reason, $this->subject);
    }
}

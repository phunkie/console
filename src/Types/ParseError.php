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

final class ParseError extends ReplError
{
    public function __construct(
        public readonly string $input,
        public readonly string $reason
    ) {
    }

    public function message(): string
    {
        return "Parse error: {$this->reason}\nInput: {$this->input}";
    }
}

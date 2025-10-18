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
 * Algebraic Data Type for REPL errors.
 *
 * All REPL errors are represented as immutable data structures
 * that can be pattern matched for error handling.
 */
abstract class ReplError extends \Exception
{
    abstract public function message(): string;
}

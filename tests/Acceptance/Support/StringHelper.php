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
 * String manipulation utilities for test assertions.
 */
class StringHelper
{
    /**
     * Unescape a string that contains escaped quotes.
     * Converts \" to actual quotes.
     */
    public static function unescape(string $str): string
    {
        return str_replace('\"', '"', $str);
    }
}

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

use PhpParser\Error;
use PhpParser\ParserFactory;
use Phunkie\Console\Types\ParseError;
use Phunkie\Validation\Validation;
use function Success;
use function Failure;

/**
 * Parses input string into an AST.
 *
 * @param string $input
 * @return Validation<ParseError, array>
 */
function parseInput(string $input): Validation
{
    try {
        $parser = (new ParserFactory)->createForNewestSupportedVersion();

        // Preprocess: Don't add extra semicolons - PHP-Parser handles this
        // The original regex was causing issues with empty blocks and method definitions
        $processedInput = $input;

        // Try to parse as-is first (for statements like if/match/class/enum)
        $code = "<?php " . $processedInput;
        try {
            $stmts = $parser->parse($code);
            return Success($stmts);
        } catch (Error $e) {
            // If that fails, try with REPL-style brace preprocessing
            // Add semicolons before closing braces if missing
            // This allows REPL-style syntax like { 1 } instead of { 1; }
            $processedInput = preg_replace('/([^;\s])\s*}/', '$1; }', $input);

            try {
                $code = "<?php " . $processedInput;
                $stmts = $parser->parse($code);
                return Success($stmts);
            } catch (Error $e2) {
                // If that fails too, try adding a semicolon at the end (for expressions)
                $code = "<?php " . $input . ";";
                $stmts = $parser->parse($code);
                return Success($stmts);
            }
        }
    } catch (Error $e) {
        return Failure(new ParseError($input, $e->getMessage()));
    }
}

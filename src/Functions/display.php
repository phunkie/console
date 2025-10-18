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

use Phunkie\Console\Types\ReplSession;
use Phunkie\Effect\IO\IO;
use Phunkie\Types\Unit;
use function Phunkie\Effect\Functions\console\printLn;

/**
 * Prints help information.
 *
 * @return IO<Unit>
 */
function printHelp(): IO
{
    $help = <<<HELP

Phunkie Console - REPL Commands:

  :help           Show this help message
  :exit           Exit the REPL (also :quit, Ctrl-C, Ctrl-D)
  :vars           List all defined variables
  :history        Show command history
  :reset          Reset the REPL state (clear all variables and history)
  :load <file>    Load a .phunkie or .php file (functions & classes become available)

Evaluate any PHP expression or Phunkie data structure:
  Some(42)
  ImmList(1, 2, 3)
  \$var0->map(fn(\$x) => \$x + 1)

HELP;

    return printLn($help);
}

/**
 * Prints all session variables.
 *
 * @param ReplSession $session
 * @return IO<Unit>
 */
function printVariables(ReplSession $session): IO
{
    $keys = $session->variables->keys();
    $values = $session->variables->values();

    if (count($keys) === 0) {
        return printLn("No variables defined");
    }

    $output = "\nDefined variables:\n";
    for ($i = 0; $i < count($keys); $i++) {
        $name = $keys[$i];
        $value = $values[$i];
        $output .= "  $name = " . var_export($value, true) . "\n";
    }

    return printLn($output);
}

/**
 * Prints command history.
 *
 * @param ReplSession $session
 * @return IO<Unit>
 */
function printHistory(ReplSession $session): IO
{
    $history = $session->history->reverse()->toArray();

    if (count($history) === 0) {
        return printLn("No history");
    }

    $output = "\nCommand history:\n";
    for ($i = 0; $i < count($history); $i++) {
        $output .= "  " . ($i + 1) . ". " . $history[$i] . "\n";
    }

    return printLn($output);
}

/**
 * Prints the welcome banner.
 *
 * @param bool $colorEnabled
 * @return IO<Unit>
 */
function printBanner(bool $colorEnabled): IO
{
    $banner = $colorEnabled
        ? "Welcome to \033[38;2;85;85;255mphunkie\033[0m console.\n\nType in expressions to have them evaluated.\n"
        : "Welcome to phunkie console.\n\nType in expressions to have them evaluated.\n";

    return printLn($banner);
}

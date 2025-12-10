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
use Phunkie\Types\ImmList;
use Phunkie\Types\ImmMap;
use Phunkie\Cats\State;
use Phunkie\Types\Pair;

/**
 * State monad operations for ReplSession.
 *
 * All session modifications go through State monad for pure functional state management.
 */

/**
 * Gets the current session state.
 *
 * @return State<ReplSession, ReplSession>
 */
function getSession(): State
{
    return (new State(fn($s) => Pair($s, $s)));
}

/**
 * Modifies the session state.
 *
 * @param callable(ReplSession): ReplSession $f
 * @return State<ReplSession, null>
 */
function modifySession(callable $f): State
{
    return new State(fn(ReplSession $s) => Pair($f($s), null));
}

/**
 * Adds an expression to the history.
 *
 * @param string $expression
 * @return State<ReplSession, null>
 */
function addToHistory(string $expression): State
{
    return modifySession(
        fn(ReplSession $s)
        => new ReplSession(
            $s->history->append($expression),
            $s->variables,
            $s->colorEnabled,
            $s->variableCounter,
            $s->incompleteInput,
            $s->currentNamespace,
            $s->useStatements
        )
    );
}

/**
 * Sets a variable in the session.
 *
 * @param string $name
 * @param mixed $value
 * @return State<ReplSession, null>
 */
function setVariable(string $name, mixed $value): State
{
    return modifySession(
        fn(ReplSession $s)
        => new ReplSession(
            $s->history,
            $s->variables->plus($name, $value),
            $s->colorEnabled,
            $s->variableCounter,
            $s->incompleteInput,
            $s->currentNamespace,
            $s->useStatements
        )
    );
}

/**
 * Gets a variable from the session.
 *
 * @param string $name
 * @return State<ReplSession, \Phunkie\Types\Option>
 */
function getVariable(string $name): State
{
    return new State(fn(ReplSession $s) => Pair($s, $s->variables->get($name)));
}

/**
 * Generates the next variable name and increments the counter.
 *
 * @return State<ReplSession, string>
 */
function nextVariable(): State
{
    return new State(function (ReplSession $s): Pair {
        $varName = '$var' . $s->variableCounter;
        $newSession = new ReplSession(
            $s->history,
            $s->variables,
            $s->colorEnabled,
            $s->variableCounter + 1,
            $s->incompleteInput,
            $s->currentNamespace,
            $s->useStatements
        );
        return Pair($newSession, $varName);
    });
}

/**
 * Gets all variables.
 *
 * @return State<ReplSession, ImmMap>
 */
function getVariables(): State
{
    return new State(fn(ReplSession $s) => Pair($s, $s->variables));
}

/**
 * Gets the command history.
 *
 * @return State<ReplSession, ImmList>
 */
function getHistory(): State
{
    return new State(fn(ReplSession $s) => Pair($s, $s->history));
}

/**
 * Enables or disables colors.
 *
 * @param bool $enabled
 * @return State<ReplSession, null>
 */
function setColors(bool $enabled): State
{
    return modifySession(
        fn(ReplSession $s)
        => new ReplSession(
            $s->history,
            $s->variables,
            $enabled,
            $s->variableCounter,
            $s->incompleteInput,
            $s->currentNamespace,
            $s->useStatements
        )
    );
}

/**
 * Checks if colors are enabled.
 *
 * @return State<ReplSession, bool>
 */
function isColorEnabled(): State
{
    return new State(fn(ReplSession $s) => Pair($s, $s->colorEnabled));
}

/**
 * Resets the REPL session to empty state while preserving color settings.
 *
 * @return State<ReplSession, null>
 */
function resetSession(): State
{
    return modifySession(
        fn(ReplSession $s)
        => new ReplSession(
            ImmList(),
            ImmMap(),
            $s->colorEnabled,
            0,
            '',  // Clear incomplete input on reset
            null,  // Clear namespace
            ImmMap()  // Clear use statements
        )
    );
}

/**
 * Sets the current namespace.
 *
 * @param string|null $namespace
 * @return State<ReplSession, null>
 */
function setNamespace(?string $namespace): State
{
    return modifySession(
        fn(ReplSession $s)
        => new ReplSession(
            $s->history,
            $s->variables,
            $s->colorEnabled,
            $s->variableCounter,
            $s->incompleteInput,
            $namespace,
            $s->useStatements
        )
    );
}

/**
 * Gets the current namespace.
 *
 * @return State<ReplSession, string|null>
 */
function getCurrentNamespace(): State
{
    return new State(fn(ReplSession $s) => Pair($s, $s->currentNamespace));
}

/**
 * Adds a use statement to the session.
 *
 * @param string $alias The alias (short name) to use
 * @param string $fullName The full qualified name
 * @return State<ReplSession, null>
 */
function addUseStatement(string $alias, string $fullName): State
{
    return modifySession(
        fn(ReplSession $s)
        => new ReplSession(
            $s->history,
            $s->variables,
            $s->colorEnabled,
            $s->variableCounter,
            $s->incompleteInput,
            $s->currentNamespace,
            $s->useStatements->plus($alias, $fullName)
        )
    );
}

/**
 * Gets the use statements.
 *
 * @return State<ReplSession, ImmMap>
 */
function getUseStatements(): State
{
    return new State(fn(ReplSession $s) => Pair($s, $s->useStatements));
}

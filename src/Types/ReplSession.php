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

use Phunkie\Types\ImmList;
use Phunkie\Types\ImmMap;

/**
 * Immutable REPL session state.
 *
 * Simple immutable data structure representing the REPL session state.
 * Operations on this state should be performed using the State monad.
 */
final readonly class ReplSession
{
    /** @var ImmMap<string, string> */
    public ImmMap $useStatements;

    /**
     * @param ImmList<string> $history
     * @param ImmMap<string, mixed> $variables
     * @param bool $colorEnabled
     * @param int $variableCounter
     * @param string $incompleteInput
     * @param string|null $currentNamespace
     * @param ImmMap<string, string>|null $useStatements
     */
    public function __construct(
        public ImmList $history,
        public ImmMap $variables,
        public bool $colorEnabled,
        public int $variableCounter,
        public string $incompleteInput = '',
        public ?string $currentNamespace = null,
        ?ImmMap $useStatements = null
    ) {
        // Initialize useStatements if null
        $this->useStatements = $useStatements ?? ImmMap();
    }

    public static function empty(): ReplSession
    {
        return new ReplSession(
            ImmList(),
            ImmMap(),
            false,
            0,
            '',
            null,
            ImmMap()
        );
    }
    /**
     * Checks if a class, interface, or trait exists in the runtime environment.
     * This encapsulates global state access, serving as a boundary for static analysis.
     */
    public function isEntityDefined(string $name, string $kind = 'class', int $attempt = 0): bool
    {
        return match ($kind) {
            'class' => class_exists($name, false),
            'interface' => interface_exists($name, false),
            'trait' => trait_exists($name, false),
            default => false
        };
    }
}

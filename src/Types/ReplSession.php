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
    public ImmMap $useStatements;

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
}

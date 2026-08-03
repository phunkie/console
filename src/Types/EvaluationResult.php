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
 * Result of evaluating an expression in the REPL.
 *
 * Contains the evaluated value and its type information for display.
 */
final readonly class EvaluationResult
{
    /**
     * @param list<string> $deprecations Advisory notices raised while evaluating
     */
    public function __construct(
        public mixed $value,
        public string $type,
        public ?string $assignedVariable = null,
        public array $additionalAssignments = [],
        public bool $isOutputStatement = false,
        public array $deprecations = []
    ) {}

    public static function of(mixed $value, string $type, ?string $assignedVariable = null, array $additionalAssignments = [], bool $isOutputStatement = false): EvaluationResult
    {
        return new EvaluationResult($value, $type, $assignedVariable, $additionalAssignments, $isOutputStatement);
    }

    /**
     * Returns a copy carrying the deprecation notices raised while evaluating.
     *
     * @param list<string> $deprecations
     */
    public function withDeprecations(array $deprecations): self
    {
        return new self(
            $this->value,
            $this->type,
            $this->assignedVariable,
            $this->additionalAssignments,
            $this->isOutputStatement,
            $deprecations
        );
    }

    /**
     * Formats the result for display in the REPL.
     *
     * @return string
     */
    public function format(): string
    {
        return $this->formatValue($this->value);
    }

    private function formatValue(mixed $value): string
    {
        return match (true) {
            is_null($value) => 'null',
            is_bool($value) => $value ? 'true' : 'false',
            is_int($value) => (string) $value,
            is_float($value) => (string) $value,
            is_string($value) => '"' . addslashes($value) . '"',
            is_array($value) => $this->formatArray($value),
            is_callable($value) => '<function>',
            is_object($value) && method_exists($value, 'show') => $value->show(),
            is_object($value) && method_exists($value, 'toString') => $value->toString(),
            is_object($value) && method_exists($value, '__toString') => (string) $value,
            is_object($value) => $this->formatObject($value),
            default => var_export($value, true)
        };
    }

    private function formatArray(array $arr): string
    {
        $items = array_map(fn($v) => $this->formatValue($v), $arr);
        return '[' . implode(', ', $items) . ']';
    }

    private function formatObject(object $obj): string
    {
        $class = get_class($obj);

        // Handle enum cases
        if (enum_exists($class) && $obj instanceof \UnitEnum) {
            return $class . '::' . $obj->name;
        }

        // Handle anonymous classes - use short format
        if (str_contains($class, 'class@anonymous')) {
            return 'a@' . substr(ltrim(spl_object_hash($obj), "0"), 0, 8);
        }

        return $class . "@" . substr(ltrim(spl_object_hash($obj), "0"), 0, 8);
    }
}

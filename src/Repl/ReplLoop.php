<?php

/*
 * This file is part of Phunkie, library with functional structures for PHP.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phunkie\Console\Repl;

use Phunkie\Console\Types\ReplSession;
use Phunkie\Console\Types\EvaluationResult;
use Phunkie\Console\Types\ContinueRepl;
use Phunkie\Console\Types\ExitRepl;
use Phunkie\Effect\IO\IO;
use Phunkie\Utils\Trampoline\Trampoline;

use function Phunkie\Effect\Functions\console\printLn;
use function Phunkie\Console\Functions\{evaluateExpression, addToHistory, setVariable, nextVariable, isColorEnabled, printHelp, printVariables, printHistory, printBanner, readLineFiltered, resetSession, setNamespace, addUseStatement};
use function Phunkie\Functions\trampoline\{More, Done};

/**
 * Main REPL loop (entry point).
 *
 * Uses IO monad for side effects and State monad for session management.
 * Uses Trampoline to prevent stack overflow in recursive loop.
 *
 * @param ReplSession $session
 * @return IO<ReplSession>
 */
function replLoop(ReplSession $session): IO
{
    // Run the trampolined loop
    return new IO(fn () => replLoopTrampoline($session)->run());
}

/**
 * Trampolined REPL loop for stack-safe recursion.
 *
 * This converts the recursive replLoop into an iterative process
 * that won't overflow the stack even with thousands of iterations.
 *
 * @param ReplSession $session
 * @return Trampoline
 */
function replLoopTrampoline(ReplSession $session): Trampoline
{
    // Use continuation prompt if we have incomplete input
    $prompt = $session->incompleteInput !== ''
        ? ($session->colorEnabled ? "\033[38;2;85;85;255mphunkie\033[0m { " : "phunkie { ")
        : ($session->colorEnabled ? "\033[38;2;85;85;255mphunkie\033[0m > " : "phunkie > ");

    // Read input
    $input = readLineFiltered($prompt)->unsafeRun();

    // Process input
    $result = processInput($input, $session)->unsafeRun();

    // Handle result
    if ($result instanceof ExitRepl) {
        printLn("\nbye \\o")->unsafeRun();
        return Done(null);
    }

    if ($result instanceof ContinueRepl) {
        // Return More to continue the trampoline
        return More(fn () => replLoopTrampoline($result->session));
    }

    // Shouldn't happen but handle gracefully
    printLn("\nbye \\o")->unsafeRun();
    return Done(null);
}

/**
 * Processes a single line of input.
 *
 * @param string|null $input
 * @param ReplSession $session
 * @return IO<ReplResult>
 */
function processInput(?string $input, ReplSession $session): IO
{
    // Handle EOF (Control-D)
    if ($input === null) {
        return new IO(fn () => new ExitRepl());
    }

    // Combine with any incomplete input from previous lines
    $combinedInput = $session->incompleteInput !== ''
        ? $session->incompleteInput . "\n" . $input
        : $input;

    $trimmed = trim($combinedInput);

    // Handle empty input
    if ($trimmed === '') {
        return new IO(fn () => new ContinueRepl($session));
    }

    // Handle REPL commands (only if not in multi-line mode)
    if (str_starts_with($trimmed, ':') && $session->incompleteInput === '') {
        return processCommand($trimmed, $session);
    }

    // Check if input is complete
    if (!isInputComplete($combinedInput)) {
        // Store incomplete input and continue
        $newSession = new ReplSession(
            $session->history,
            $session->variables,
            $session->colorEnabled,
            $session->variableCounter,
            $combinedInput
        );
        return new IO(fn () => new ContinueRepl($newSession));
    }

    // Clear incomplete input buffer for complete expression
    $newSession = new ReplSession(
        $session->history,
        $session->variables,
        $session->colorEnabled,
        $session->variableCounter,
        ''
    );

    // Handle expressions
    return evaluateAndDisplay($trimmed, $newSession);
}

/**
 * Checks if input is syntactically complete (balanced braces, brackets, parentheses).
 * Also handles heredoc and nowdoc multi-line strings.
 *
 * @param string $input
 * @return bool
 */
function isInputComplete(string $input): bool
{
    // Check for attributes - if input ends with attribute(s) but no declaration follows
    // Attributes must be followed by: class, interface, trait, enum, function, or property/method/parameter (within class)
    $trimmed = trim($input);

    // Pattern: ends with #[...] (potentially multiple, stacked or comma-separated)
    // But NOT followed by a declaration keyword
    if (preg_match('/\#\[[^\]]*\]\s*$/s', $trimmed)) {
        // Input ends with an attribute - check if there's a declaration after it
        // This is incomplete - needs continuation
        return false;
    }

    // Also check for attributes followed by only whitespace/newlines (multi-line case)
    // If we have #[...] on one or more lines but no class/function/etc declaration
    if (preg_match('/\#\[[^\]]*\](\s*\n)*\s*$/s', $trimmed)) {
        // Check if there's any declaration keyword after the last attribute
        $afterLastAttribute = preg_split('/\#\[[^\]]*\]/', $trimmed);
        $lastPart = end($afterLastAttribute);

        // If the part after the last attribute doesn't contain a declaration keyword, it's incomplete
        if (!preg_match('/\b(class|interface|trait|enum|function|public|protected|private|readonly)\b/', $lastPart)) {
            return false;
        }
    }

    // Check for heredoc/nowdoc - look for <<< followed by an identifier
    // Pattern: <<<['"]?([A-Za-z_][A-Za-z0-9_]*)['"]?
    if (preg_match('/<<<\s*([\'"]?)([A-Za-z_][A-Za-z0-9_]*)\\1/', $input, $matches)) {
        $identifier = $matches[2];

        // Check if the closing identifier exists on its own line
        // The closing identifier should be at the start of a line (possibly with whitespace before it for indented heredoc)
        $lines = explode("\n", $input);
        $foundOpening = false;

        foreach ($lines as $line) {
            // Check if this line contains the heredoc opening
            if (!$foundOpening && preg_match('/<<<\s*([\'"]?)' . preg_quote($identifier, '/') . '\\1/', $line)) {
                $foundOpening = true;
                continue;
            }

            // After finding opening, look for closing identifier
            if ($foundOpening && preg_match('/^\s*' . preg_quote($identifier, '/') . '\s*;?\s*$/', $line)) {
                // Found closing identifier, heredoc is complete
                // Continue to check other syntax elements below
                break;
            }
        }

        // If we found opening but not closing, input is incomplete
        if ($foundOpening) {
            $foundClosing = false;
            foreach ($lines as $line) {
                if (preg_match('/^\s*' . preg_quote($identifier, '/') . '\s*;?\s*$/', $line)) {
                    $foundClosing = true;
                    break;
                }
            }
            if (!$foundClosing) {
                return false;  // Heredoc/nowdoc not yet closed
            }
        }
    }

    $braceCount = 0;
    $bracketCount = 0;
    $parenCount = 0;
    $inString = false;
    $inSingleQuote = false;
    $escape = false;

    $length = strlen($input);
    for ($i = 0; $i < $length; $i++) {
        $char = $input[$i];

        // Handle escape sequences
        if ($escape) {
            $escape = false;
            continue;
        }

        if ($char === '\\') {
            $escape = true;
            continue;
        }

        // Track string state
        if ($char === '"' && !$inSingleQuote) {
            $inString = !$inString;
            continue;
        }

        if ($char === "'" && !$inString) {
            $inSingleQuote = !$inSingleQuote;
            continue;
        }

        // Skip counting if we're in a string
        if ($inString || $inSingleQuote) {
            continue;
        }

        // Count braces, brackets, and parentheses
        switch ($char) {
            case '{':
                $braceCount++;
                break;
            case '}':
                $braceCount--;
                break;
            case '[':
                $bracketCount++;
                break;
            case ']':
                $bracketCount--;
                break;
            case '(':
                $parenCount++;
                break;
            case ')':
                $parenCount--;
                break;
        }
    }

    // Input is complete if all are balanced and we're not in a string
    return $braceCount === 0 && $bracketCount === 0 && $parenCount === 0 && !$inString && !$inSingleQuote;
}

/**
 * Processes REPL commands (:exit, :help, etc.).
 *
 * @param string $command
 * @param ReplSession $session
 * @return IO<ReplResult>
 */
function processCommand(string $command, ReplSession $session): IO
{
    // Check for :load command with file argument
    if (preg_match('/^:load\s+(.+)$/', $command, $matches)) {
        return loadFile(trim($matches[1]), $session);
    }

    // Check for :import command with module/function argument
    if (preg_match('/^:import\s+(.+)$/', $command, $matches)) {
        return importFunction(trim($matches[1]), $session);
    }

    // Check for :type command with expression argument
    if (preg_match('/^:type\s+(.+)$/', $command, $matches)) {
        return showTypeCommand(trim($matches[1]), $session);
    }

    // Check for :kind command with expression argument
    if (preg_match('/^:(?:kind|k)\s+(.+)$/', $command, $matches)) {
        return showKindCommand(trim($matches[1]), $session);
    }

    return match ($command) {
        ':exit', ':quit' => new IO(fn () => new ExitRepl()),
        ':help' => printHelp()->map(fn () => new ContinueRepl($session)),
        ':vars' => printVariables($session)->map(fn () => new ContinueRepl($session)),
        ':history' => printHistory($session)->map(fn () => new ContinueRepl($session)),
        ':reset' => resetReplState($session),
        default => printLn("Unknown command: $command")
            ->map(fn () => new ContinueRepl($session))
    };
}

/**
 * Resets the REPL state.
 *
 * @param ReplSession $session
 * @return IO<ContinueRepl>
 */
function resetReplState(ReplSession $session): IO
{
    $pair = resetSession()->run($session);
    $newSession = $pair->_1;

    return printLn("REPL state reset")
        ->map(fn () => new ContinueRepl($newSession));
}

/**
 * Loads and executes a .phunkie file.
 *
 * @param string $filepath
 * @param ReplSession $session
 * @return IO<ContinueRepl>
 */
function loadFile(string $filepath, ReplSession $session): IO
{
    return new IO(function () use ($filepath, $session) {
        // Check if file exists
        if (!file_exists($filepath)) {
            printLn("Error: File not found: $filepath")->unsafeRun();
            return new ContinueRepl($session);
        }

        // Check file extension
        $ext = pathinfo($filepath, PATHINFO_EXTENSION);
        if ($ext !== 'phunkie' && $ext !== 'php') {
            printLn("Error: File must have .phunkie or .php extension")->unsafeRun();
            return new ContinueRepl($session);
        }

        // Read file contents
        $contents = file_get_contents($filepath);
        if ($contents === false) {
            printLn("Error: Could not read file: $filepath")->unsafeRun();
            return new ContinueRepl($session);
        }

        // Remove PHP opening tag if present
        $contents = preg_replace('/^<\?php\s*/m', '', $contents);

        // Capture output to suppress it during load
        ob_start();

        // Evaluate the file content as a single block
        // This will execute the entire file and capture all definitions
        try {
            eval($contents);
        } catch (\Throwable $e) {
            ob_end_clean();
            printLn("Error loading file: " . $e->getMessage())->unsafeRun();
            return new ContinueRepl($session);
        }

        // Discard captured output
        ob_end_clean();

        // Get the basename for the message
        $filename = basename($filepath);
        printLn("// file $filename loaded")->unsafeRun();
        return new ContinueRepl($session);
    });
}

/**
 * Finds a module file in vendor directories.
 *
 * Searches for the module file in this order:
 * 1. User's project vendor directory (cwd/vendor)
 * 2. Console's own vendor directory
 *
 * This ensures that :import uses the user's installed version of packages,
 * not the console's bundled versions.
 *
 * @param string $package Package name (e.g., 'phunkie', 'effect', 'streams')
 * @param string $packagePath Path within package to Functions directory (e.g., 'Phunkie/Functions' or 'Functions')
 * @param string $module Module name (e.g., 'console', 'immlist')
 * @return string|null Absolute path to module file, or null if not found
 */
function findModuleFile(string $package, string $packagePath, string $module): ?string
{
    $possiblePaths = [];

    // Build the full path based on package structure
    // For phunkie core: vendor/phunkie/phunkie/src/Phunkie/Functions/module.php
    // For effect: vendor/phunkie/effect/src/Functions/module.php

    // 1. Try user's project vendor directory (current working directory)
    $userVendor = getcwd() . '/vendor/phunkie/' . $package . '/src/' . $packagePath . '/' . $module . '.php';
    $possiblePaths[] = $userVendor;

    // 2. Try console's own vendor directory (fallback)
    $consoleVendor = __DIR__ . '/../../vendor/phunkie/' . $package . '/src/' . $packagePath . '/' . $module . '.php';
    $possiblePaths[] = $consoleVendor;

    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            return $path;
        }
    }

    return null;
}

/**
 * Imports function(s) from Phunkie libraries.
 *
 * Supports two formats:
 * - :import module/function - imports from phunkie/phunkie core (e.g., "immlist/head")
 * - :import package::module/function - imports from external package (e.g., "effect::console/printTable")
 *
 * @param string $import Import specification
 * @param ReplSession $session
 * @return IO<ContinueRepl>
 */
function importFunction(string $import, ReplSession $session): IO
{
    return new IO(function () use ($import, $session) {
        // Parse package::module/function or module/function pattern
        $package = 'phunkie'; // Default to core phunkie
        $packagePath = 'Phunkie/Functions';
        $namespace = 'Phunkie\\Functions';

        // Check for package::module/function syntax
        if (preg_match('/^([a-z_]+)::([a-z_]+)\/(.+)$/', $import, $matches)) {
            $package = $matches[1];
            $module = $matches[2];
            $functionSpec = $matches[3];

            // Map package names to paths and namespaces
            switch ($package) {
                case 'effect':
                    $packagePath = 'Functions';
                    $namespace = 'Phunkie\\Effect\\Functions';
                    break;
                case 'streams':
                    $packagePath = 'Functions';
                    $namespace = 'Phunkie\\Streams\\Functions';
                    break;
                default:
                    printLn("Error: Unknown package '$package'")->unsafeRun();
                    return new ContinueRepl($session);
            }
        } elseif (preg_match('/^([a-z_]+)\/(.+)$/', $import, $matches)) {
            // Simple module/function format - use core phunkie
            $module = $matches[1];
            $functionSpec = $matches[2];
        } else {
            printLn("Error: Invalid import format. Use :import module/function or :import package::module/function")->unsafeRun();
            return new ContinueRepl($session);
        }

        // Find the module file, trying user's vendor first, then console's vendor
        $moduleFile = findModuleFile($package, $packagePath, $module);

        if ($moduleFile === null) {
            printLn("Error: Module '$module' not found in package 'phunkie/$package'")->unsafeRun();
            return new ContinueRepl($session);
        }

        // Read and parse the module file to extract function names
        $content = file_get_contents($moduleFile);
        if ($content === false) {
            printLn("Error: Could not read module file")->unsafeRun();
            return new ContinueRepl($session);
        }

        // Extract all function names from the module
        // Functions are in global namespace within the file
        preg_match_all('/^\s*function\s+([a-z_][a-z0-9_]*)\s*\(/im', $content, $functionMatches);
        $availableFunctions = $functionMatches[1];

        // Filter out internal functions (those starting with assert or format)
        $exportedFunctions = array_filter($availableFunctions, function ($name) {
            return !in_array($name, ['assertListOrString', 'formatError', 'ImmList', 'Nil', 'Cons',
                                      'ImmSet', 'ImmMap', 'Pair', 'Some', 'None', 'Success', 'Failure',
                                      'Unit', 'Tuple', 'Function1']);
        });

        // Determine which functions to import
        if ($functionSpec === '*') {
            $functionsToImport = $exportedFunctions;
        } else {
            // Import specific function
            if (!in_array($functionSpec, $exportedFunctions)) {
                printLn("Error: Function '$functionSpec' not found in module '$module'")->unsafeRun();
                return new ContinueRepl($session);
            }
            $functionsToImport = [$functionSpec];
        }

        // Load the module file using require_once via eval to ensure it's loaded in global scope
        // This ensures functions are available globally for function_exists() checks
        try {
            eval('require_once "' . addslashes($moduleFile) . '";');
        } catch (\Throwable $e) {
            printLn("Error loading module: " . $e->getMessage())->unsafeRun();
            return new ContinueRepl($session);
        }

        // Create global aliases for imported functions so they can be called without namespace
        foreach ($functionsToImport as $function) {
            $fullName = "\\$namespace\\$module\\$function";

            // Create a global wrapper function if it doesn't already exist
            $wrapperCode = "
                if (!function_exists('$function')) {
                    function $function(...\$args) {
                        return \\$namespace\\$module\\$function(...\$args);
                    }
                }
            ";

            try {
                eval($wrapperCode);
            } catch (\Throwable $e) {
                printLn("Warning: Could not create alias for $function: " . $e->getMessage())->unsafeRun();
            }

            $message = $session->colorEnabled
                ? "\033[35mimported\033[0m function $fullName()"  // Bright magenta (old prompt color)
                : "imported function $fullName()";
            printLn($message)->unsafeRun();
        }

        return new ContinueRepl($session);
    });
}

/**
 * Shows the type of an expression.
 *
 * Evaluates the expression and displays its type using Phunkie's showType function.
 *
 * @param string $expression Expression to evaluate
 * @param ReplSession $session Current REPL session
 * @return IO<ContinueRepl> IO action that continues the REPL
 */
function showTypeCommand(string $expression, ReplSession $session): IO
{
    return new IO(function () use ($expression, $session) {
        // Evaluate the expression to get the value
        $result = evaluateExpression($expression, $session);

        // Use fold to handle both success and failure cases
        $result->fold(
            // Failure case: error is passed to this function
            function ($error) use ($session) {
                printLn(formatError($error, $session))->unsafeRun();
            }
        )(
            // Success case: result is passed to this function
            function ($evalResult) {
                // Use Phunkie's showType function to get the type
                $type = \Phunkie\Functions\show\showType($evalResult->value);
                printLn($type)->unsafeRun();
            }
        );

        return new ContinueRepl($session);
    });
}

/**
 * Shows the kind of an expression.
 *
 * Evaluates the expression and displays its kind using Phunkie's showKind function.
 *
 * @param string $expression Expression to evaluate
 * @param ReplSession $session Current REPL session
 * @return IO<ContinueRepl> IO action that continues the REPL
 */
function showKindCommand(string $expression, ReplSession $session): IO
{
    return new IO(function () use ($expression, $session) {
        // Evaluate the expression to get the value
        $result = evaluateExpression($expression, $session);

        // Use fold to handle both success and failure cases
        $result->fold(
            // Failure case: error is passed to this function
            function ($error) use ($session) {
                printLn(formatError($error, $session))->unsafeRun();
            }
        )(
            // Success case: result is passed to this function
            function ($evalResult) {
                // Get the type of the value
                $type = \Phunkie\Functions\show\showType($evalResult->value);

                // For :kind command on a value, we usually want the kind of the type constructor
                // e.g. Some(1) -> Option<Int> -> we want kind of "Option" (* -> *)
                // If we passed "Option<Int>" to showKind, we'd get "*"
                $baseType = $type;

                // Handle None -> Option
                if ($type === 'None') {
                    $baseType = 'Option';
                }

                // Handle Tuple syntax (Int, String) -> Pair
                elseif (str_starts_with($type, '(') && str_contains($type, ',')) {
                    $baseType = 'Pair';
                } elseif (preg_match('/^([^<(]+)/', $type, $matches)) {
                    $baseType = $matches[1];
                }

                $kind = \Phunkie\Functions\show\showKind($baseType);
                if ($kind->isDefined()) {
                    // Extract just the kind signature (e.g., "* -> *")
                    $kindInfo = $kind->get();
                    if (preg_match('/:: (.+)$/', $kindInfo, $matches)) {
                        printLn($matches[1])->unsafeRun();
                    } else {
                        printLn($kindInfo)->unsafeRun();
                    }
                } else {
                    printLn("Error: Could not calculate kind for: $type ($baseType)")->unsafeRun();
                }
            }
        );

        return new ContinueRepl($session);
    });
}



/**
 * Formats an error message with optional color support.
 *
 * @param \Phunkie\Console\Types\ReplError $error
 * @param ReplSession $session
 * @return string
 */
function formatError($error, ReplSession $session): string
{
    // Extract error type from class name (e.g., "EvaluationError" -> "Error")
    $className = get_class($error);
    $parts = explode('\\', $className);
    $errorType = end($parts);

    // Map error types to display format:
    // - EvaluationError -> "Error"
    // - ParseError -> "Parse error"
    // - TypeError -> "TypeError"
    if ($errorType === 'EvaluationError') {
        $errorType = 'Error';
    } elseif ($errorType === 'ParseError') {
        $errorType = 'Parse error';
    }
    // Otherwise keep as-is (e.g., "TypeError")

    if ($session->colorEnabled) {
        // Red error type, normal color for the rest
        return "\033[31m{$errorType}:\033[0m {$error->reason}";
    }

    return "{$errorType}: {$error->reason}";
}

/**
 * Evaluates an expression and displays the result.
 *
 * @param string $expression
 * @param ReplSession $session
 * @return IO<ContinueRepl>
 */
function evaluateAndDisplay(string $expression, ReplSession $session): IO
{
    $evalResult = evaluateExpression($expression, $session);

    // Use fold to handle both success and failure cases
    return $evalResult->fold(
        // Failure case: error is passed to this function
        fn ($error) => printLn(formatError($error, $session))
            ->map(fn () => new ContinueRepl($session))
    )(
        // Success case: result is passed to this function
        fn ($result) => displayResult($result, $session, $expression)
    );
}

/**
 * Displays an evaluation result and updates the session.
 *
 * @param EvaluationResult $result
 * @param ReplSession $session
 * @param string $expression
 * @return IO<ContinueRepl>
 */
function displayResult(EvaluationResult $result, ReplSession $session, string $expression): IO
{
    // Handle namespace declaration
    if ($result->assignedVariable === '__namespace__') {
        $namespaceName = $result->value;

        // Add to history
        $pair1 = (addToHistory($expression))->run($session);
        $newSession1 = $pair1->_1;

        // Update namespace
        $pair2 = (setNamespace($namespaceName))->run($newSession1);
        $newSession2 = $pair2->_1;

        $output = $namespaceName !== null
            ? "Namespace set to: $namespaceName"
            : "Namespace cleared";

        return printLn($output)
            ->map(fn () => new ContinueRepl($newSession2));
    }

    // Handle use statement
    if ($result->assignedVariable === '__use__') {
        $imports = $result->value;

        // Add to history
        $pair1 = (addToHistory($expression))->run($session);
        $newSession = $pair1->_1;

        // Add each import to the session
        foreach ($imports as $import) {
            $pair = (addUseStatement($import['alias'], $import['fullName']))->run($newSession);
            $newSession = $pair->_1;
        }

        $importCount = count($imports);
        $output = "Imported $importCount " . ($importCount === 1 ? 'class/function' : 'classes/functions');

        return printLn($output)
            ->map(fn () => new ContinueRepl($newSession));
    }

    // Handle silent operations (property assignments, etc.) that shouldn't produce output
    // BUT: Traits, Interfaces, and Classes should still show "defined" message
    if ($result->assignedVariable === '__no_output__') {
        if (in_array($result->type, ['Trait', 'Interface', 'Class'])) {
            // These should show output even though they don't create variables
            // Extract the name from additionalAssignments
            $keys = array_keys($result->additionalAssignments);
            if (empty($keys)) {
                // Fallback to just saying "defined" without name
                $typeLower = strtolower($result->type);
                $output = $session->colorEnabled
                    ? "// \033[95m{$typeLower}\033[0m defined"
                    : "// {$typeLower} defined";
            } else {
                $firstKey = $keys[0];
                // Extract name from format like "trait StatusTrait"
                $parts = explode(' ', $firstKey);
                $name = $parts[1] ?? $firstKey;

                $typeLower = strtolower($result->type);
                $output = $session->colorEnabled
                    ? "// \033[95m{$typeLower}\033[0m $name defined"
                    : "// {$typeLower} $name defined";
            }

            // Add to history
            $pair = (addToHistory($expression))->run($session);
            $newSession = $pair->_1;

            return printLn($output)
                ->map(fn () => new ContinueRepl($newSession));
        }

        // Other silent operations - add to history but don't print anything
        $pair = (addToHistory($expression))->run($session);
        $newSession = $pair->_1;

        return new IO(fn () => new ContinueRepl($newSession));
    }

    // Check if this is an enum definition
    if ($result->type === 'EnumDefinition') {
        // Add to history
        $pair = (addToHistory($expression))->run($session);
        $newSession = $pair->_1;

        // Format output
        $output = $session->colorEnabled
            ? "\033[90m// enum {$result->value} defined\033[0m"  // Gray comment
            : "// enum {$result->value} defined";

        return printLn($output)
            ->map(fn () => new ContinueRepl($newSession));
    }

    // Check if this was an assignment with a specific variable name
    if ($result->assignedVariable !== null) {
        $varName = $result->assignedVariable;
        // Add to history
        $pair1 = (addToHistory($expression))->run($session);
        $newSession1 = $pair1->_1;

        // Store the result with the assigned variable name
        $pair2 = (setVariable($varName, $result->value))->run($newSession1);
        $newSession2 = $pair2->_1;

        // Store any additional assignments (e.g., from list() destructuring)
        $currentSession = $newSession2;
        foreach ($result->additionalAssignments as $additionalVarName => $additionalValue) {
            $pair = (setVariable($additionalVarName, $additionalValue))->run($currentSession);
            $currentSession = $pair->_1;
        }

        // Special handling for function definitions
        if ($result->type === 'Function') {
            // Remove $ prefix from function names for display
            $displayName = str_starts_with($varName, '$') ? substr($varName, 1) : $varName;
            $output = $session->colorEnabled
                ? "// \033[95mfunction\033[0m $displayName defined"
                : "// function $displayName defined";

            return printLn($output)
                ->map(fn () => new ContinueRepl($currentSession));
        }

        // Special handling for class definitions
        if ($result->type === 'Class') {
            $output = $session->colorEnabled
                ? "// \033[95mclass\033[0m $varName defined"
                : "// class $varName defined";

            return printLn($output)
                ->map(fn () => new ContinueRepl($currentSession));
        }

        // Special handling for interface definitions
        if ($result->type === 'Interface') {
            $output = $session->colorEnabled
                ? "// \033[95minterface\033[0m $varName defined"
                : "// interface $varName defined";

            return printLn($output)
                ->map(fn () => new ContinueRepl($currentSession));
        }

        // Special handling for trait definitions
        if ($result->type === 'Trait') {
            $output = $session->colorEnabled
                ? "// \033[95mtrait\033[0m $varName defined"
                : "// trait $varName defined";

            return printLn($output)
                ->map(fn () => new ContinueRepl($currentSession));
        }

        // Format output with bold variable name, pink type, and bold value if colors are enabled
        $output = $session->colorEnabled
            ? "\033[1m$varName\033[0m: \033[1m\033[95m{$result->type}\033[0m = \033[1m{$result->format()}\033[0m"  // Bold var, bold pink type, bold value
            : "$varName: {$result->type} = {$result->format()}";

        return printLn($output)
            ->map(fn () => new ContinueRepl($currentSession));
    }

    // Check if this is an output statement (echo, print, var_dump, etc.)
    // These should not get auto-assigned to a variable
    if ($result->isOutputStatement) {
        // Add to history but don't create a variable
        $pair = (addToHistory($expression))->run($session);
        $newSession = $pair->_1;

        // Add a newline after the output to prevent prompt from running into it
        echo "\n";

        return new IO(fn () => new ContinueRepl($newSession));
    }

    // Generate next variable name for auto-assignment
    $pair1 = (nextVariable())->run($session);
    $newSession1 = $pair1->_1;
    $varName = $pair1->_2;

    // Add to history
    $pair2 = (addToHistory($expression))->run($newSession1);
    $newSession2 = $pair2->_1;

    // Store the result
    $pair3 = (setVariable($varName, $result->value))->run($newSession2);
    $newSession3 = $pair3->_1;

    // Store any additional assignments (e.g., from inc/dec operators)
    $currentSession = $newSession3;
    foreach ($result->additionalAssignments as $additionalVarName => $additionalValue) {
        $pair = (setVariable($additionalVarName, $additionalValue))->run($currentSession);
        $currentSession = $pair->_1;
    }

    // Format output with bold variable name, bold pink type, and bold value if colors are enabled
    $output = $session->colorEnabled
        ? "\033[1m$varName\033[0m: \033[1m\033[95m{$result->type}\033[0m = \033[1m{$result->format()}\033[0m"  // Bold var, bold pink type, bold value
        : "$varName: {$result->type} = {$result->format()}";

    return printLn($output)
        ->map(fn () => new ContinueRepl($currentSession));
}

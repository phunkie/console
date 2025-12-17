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

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar;
use Phunkie\Console\Types\EvaluationResult;
use Phunkie\Console\Types\EvaluationError;
use Phunkie\Console\Types\TypeError;
use Phunkie\Console\Types\ReplError;
use Phunkie\Console\Types\ReplSession;
use Phunkie\Validation\Validation;

use function Success;
use function Failure;

/**
 * Pure function to evaluate a PHP expression.
 *
 * @param string $input The expression to evaluate
 * @param ReplSession $session The current session state
 * @return Validation<ReplError, EvaluationResult>
 */
function evaluateExpression(string $input, ReplSession $session): Validation
{
    /** @var Validation<ReplError, array> $parsed */
    $parsed = \Phunkie\Console\Functions\parseInput($input);
    return $parsed->flatMap(fn(array $ast) => evaluateAst($ast, $session));
}

/**
 * Evaluates an AST to produce a result.
 *
 * @param array $ast
 * @param ReplSession $session
 * @return Validation<EvaluationError, EvaluationResult>
 */
function evaluateAst(array $ast, ReplSession $session): Validation
{
    if (empty($ast)) {
        return Failure(new EvaluationError('', 'Empty expression'));
    }

    $stmt = $ast[0];

    // Handle namespace declarations
    if ($stmt instanceof Node\Stmt\Namespace_) {
        return evaluateNamespace($stmt, $session);
    }

    // Handle use statements
    if ($stmt instanceof Node\Stmt\Use_) {
        return evaluateUseStatement($stmt, $session);
    }

    // Handle expression statements
    if ($stmt instanceof Node\Stmt\Expression) {
        $result = evaluateNode($stmt->expr, $session);

        // If this is an assignment, we need to track the variable name
        // Skip variable variables ($$var) as they're already handled in evaluateAssignment
        if ($stmt->expr instanceof Expr\Assign
            && $stmt->expr->var instanceof Expr\Variable
            && is_string($stmt->expr->var->name)) {
            return $result->map(function ($evalResult) use ($stmt) {
                $varName = '$' . $stmt->expr->var->name;
                // Create a new result with assignment metadata
                return new EvaluationResult(
                    $evalResult->value,
                    $evalResult->type,
                    $varName
                );
            });
        }

        return $result;
    }

    // Handle if statements
    if ($stmt instanceof Node\Stmt\If_) {
        try {
            return evaluateIfStatement($stmt, $session);
        } catch (FunctionReturnException $e) {
            // Return statement outside of function context
            // In the REPL, we'll just return the value instead of erroring
            return Success(EvaluationResult::of($e->value, getType($e->value)));
        }
    }

    // Handle function definitions
    if ($stmt instanceof Node\Stmt\Function_) {
        return evaluateFunctionDefinition($stmt, $session);
    }

    // Handle class definitions
    if ($stmt instanceof Node\Stmt\Class_) {
        return evaluateClassDefinition($stmt, $session);
    }

    // Handle enum definitions
    if ($stmt instanceof Node\Stmt\Enum_) {
        return evaluateEnumDefinition($stmt, $session);
    }

    // Handle interface definitions
    if ($stmt instanceof Node\Stmt\Interface_) {
        return evaluateInterfaceDefinition($stmt, $session);
    }

    // Handle trait definitions
    if ($stmt instanceof Node\Stmt\Trait_) {
        return evaluateTraitDefinition($stmt, $session);
    }

    // Handle echo statement
    if ($stmt instanceof Node\Stmt\Echo_) {
        return evaluateEchoStatement($stmt, $session);
    }

    // Handle for loops
    if ($stmt instanceof Node\Stmt\For_) {
        try {
            return evaluateForLoop($stmt, $session);
        } catch (FunctionReturnException $e) {
            // Return statement outside of function context
            return Success(EvaluationResult::of($e->value, getType($e->value)));
        }
    }

    // Handle while loops
    if ($stmt instanceof Node\Stmt\While_) {
        try {
            return evaluateWhileLoop($stmt, $session);
        } catch (FunctionReturnException $e) {
            // Return statement outside of function context
            return Success(EvaluationResult::of($e->value, getType($e->value)));
        }
    }

    // Handle do-while loops
    if ($stmt instanceof Node\Stmt\Do_) {
        try {
            return evaluateDoWhileLoop($stmt, $session);
        } catch (FunctionReturnException $e) {
            // Return statement outside of function context
            return Success(EvaluationResult::of($e->value, getType($e->value)));
        }
    }

    // Handle foreach loops
    if ($stmt instanceof Node\Stmt\Foreach_) {
        try {
            return evaluateForeachLoop($stmt, $session);
        } catch (FunctionReturnException $e) {
            // Return statement outside of function context
            return Success(EvaluationResult::of($e->value, getType($e->value)));
        }
    }

    return Failure(new EvaluationError('', 'Unsupported statement type: ' . get_class($stmt)));
}

/**
 * Evaluates a single AST node.
 *
 * @param Node $node
 * @param ReplSession $session
 * @return Validation<EvaluationError, EvaluationResult>
 */
function evaluateNode(Node $node, ReplSession $session): Validation
{
    return match (true) {
        $node instanceof Scalar\Int_
            => Success(EvaluationResult::of($node->value, 'Int')),

        $node instanceof Scalar\Float_
            => Success(EvaluationResult::of($node->value, 'Float')),

        $node instanceof Scalar\String_
            => Success(EvaluationResult::of($node->value, 'String')),

        $node instanceof Scalar\InterpolatedString
            => evaluateInterpolatedString($node, $session),

        $node instanceof Expr\ConstFetch && $node->name->toString() === 'true'
            => Success(EvaluationResult::of(true, 'Bool')),

        $node instanceof Expr\ConstFetch && $node->name->toString() === 'false'
            => Success(EvaluationResult::of(false, 'Bool')),

        $node instanceof Expr\ConstFetch && $node->name->toString() === 'null'
            => Success(EvaluationResult::of(null, 'Null')),

        $node instanceof Expr\ConstFetch && $node->name->toString() === 'None'
            => Success(EvaluationResult::of(\None(), getType(\None()))),

        $node instanceof Expr\ConstFetch
            => evaluateConstant($node),

        $node instanceof Expr\ClassConstFetch
            => evaluateClassConstFetch($node, $session),

        $node instanceof Expr\Variable
            => evaluateVariableNode($node, $session),

        $node instanceof Expr\Assign
            => evaluateAssignment($node, $session),

        $node instanceof Expr\BinaryOp
            => evaluateBinaryOp($node, $session),

        $node instanceof Expr\BooleanNot
        || $node instanceof Expr\UnaryPlus
        || $node instanceof Expr\UnaryMinus
        || $node instanceof Expr\BitwiseNot
            => evaluateUnaryOp($node, $session),

        $node instanceof Expr\StaticCall
            => evaluateStaticCall($node, $session),

        $node instanceof Expr\MethodCall
            => evaluateMethodCall($node, $session),

        $node instanceof Expr\NullsafeMethodCall
            => evaluateNullsafeMethodCall($node, $session),

        $node instanceof Expr\PropertyFetch
            => evaluatePropertyFetch($node, $session),

        $node instanceof Expr\NullsafePropertyFetch
            => evaluateNullsafePropertyFetch($node, $session),

        $node instanceof Expr\FuncCall
            => evaluateFunctionCall($node, $session),

        $node instanceof Expr\Array_
            => evaluateArray($node, $session),

        $node instanceof Expr\ArrayDimFetch
            => evaluateArrayAccess($node, $session),

        $node instanceof Expr\ArrowFunction
            => evaluateArrowFunction($node, $session),

        $node instanceof Expr\Closure
            => evaluateClosure($node, $session),

        $node instanceof Expr\Ternary
            => evaluateTernary($node, $session),

        $node instanceof Expr\Match_
            => evaluateMatch($node, $session),

        $node instanceof Expr\Yield_
            => evaluateYield($node, $session),

        $node instanceof Expr\New_
            => evaluateNew($node, $session),

        $node instanceof Expr\Print_
            => evaluatePrint($node, $session),

        $node instanceof Expr\Throw_
            => evaluateThrow($node, $session),

        $node instanceof Expr\Instanceof_
            => evaluateInstanceof($node, $session),

        $node instanceof Expr\Clone_
            => evaluateClone($node, $session),

        $node instanceof Expr\ErrorSuppress
            => evaluateErrorSuppress($node, $session),

        $node instanceof Expr\PreInc
        || $node instanceof Expr\PreDec
        || $node instanceof Expr\PostInc
        || $node instanceof Expr\PostDec
            => evaluateIncDec($node, $session),

        $node instanceof Node\Scalar\MagicConst
            => evaluateMagicConstant($node, $session),

        default => Failure(new EvaluationError(
            get_class($node),
            'Unsupported expression type: ' . get_class($node)
        ))
    };
}

/**
 * Evaluates a variable node (handles both simple variables and variable variables).
 *
 * @param Expr\Variable $node
 * @param ReplSession $session
 * @return Validation<EvaluationError, EvaluationResult>
 */
function evaluateVariableNode(Expr\Variable $node, ReplSession $session): Validation
{
    // Check if this is a variable-variable ($$var)
    if ($node->name instanceof Node) {
        // Evaluate the inner expression to get the variable name
        return evaluateNode($node->name, $session)->flatMap(
            /** @param EvaluationResult $result */
            function ($result) use ($session) {
                $varName = $result->value;

                if (!is_string($varName)) {
                    return Failure(new EvaluationError('$$var', 'Variable variable name must be a string'));
                }

                return evaluateVariable($varName, $session);
            }
        );
    }

    // Simple variable - name is a string
    // At this point, if $node->name was a Node it would have been handled above,
    // so it must be a string
    /** @var string $varName */
    $varName = $node->name;
    return evaluateVariable($varName, $session);
}

/**
 * Evaluates a variable reference.
 *
 * @param string $name
 * @param ReplSession $session
 * @return Validation<EvaluationError, EvaluationResult>
 */
function evaluateVariable(string $name, ReplSession $session): Validation
{
    $option = $session->variables->get('$' . $name);

    if ($option->isEmpty()) {
        return Failure(new EvaluationError('$' . $name, 'Variable not found'));
    }

    $value = $option->get();
    return Success(EvaluationResult::of($value, getType($value)));
}

/**
 * Evaluates an interpolated string (e.g., "Hello $name" or heredoc with variables).
 *
 * @param Scalar\InterpolatedString $node
 * @param ReplSession $session
 * @return Validation<EvaluationError, EvaluationResult>
 */
function evaluateInterpolatedString(Scalar\InterpolatedString $node, ReplSession $session): Validation
{
    $result = '';

    foreach ($node->parts as $part) {
        if ($part instanceof Node\InterpolatedStringPart) {
            // Plain string part - just append it
            $result .= $part->value;
        } elseif ($part instanceof Expr\Variable) {
            // Variable to interpolate - evaluate it
            $varResult = evaluateVariable($part->name, $session);
            if ($varResult->isLeft()) {
                return $varResult;
            }
            $value = $varResult->getOrElse(null)->value;
            // Convert value to string for interpolation
            $result .= match (true) {
                is_string($value) => $value,
                is_numeric($value) => (string) $value,
                is_bool($value) => $value ? '1' : '',
                is_null($value) => '',
                is_array($value) => 'Array',
                is_object($value) && method_exists($value, '__toString') => (string) $value,
                is_object($value) => get_class($value),
                default => ''
            };
        } else {
            // For more complex expressions like "Hello {$obj->prop}"
            $exprResult = evaluateNode($part, $session);
            if ($exprResult->isLeft()) {
                return $exprResult;
            }
            $value = $exprResult->getOrElse(null)->value;
            // Convert value to string for interpolation
            $result .= match (true) {
                is_string($value) => $value,
                is_numeric($value) => (string) $value,
                is_bool($value) => $value ? '1' : '',
                is_null($value) => '',
                is_array($value) => 'Array',
                is_object($value) && method_exists($value, '__toString') => (string) $value,
                is_object($value) => get_class($value),
                default => ''
            };
        }
    }

    return Success(EvaluationResult::of($result, 'String'));
}

/**
 * Resolves a class or function name based on current namespace and use statements.
 *
 * @param string $name The name to resolve
 * @param ReplSession $session
 * @return string The fully qualified name
 */
function resolveName(string $name, ReplSession $session): string
{
    // If already fully qualified (starts with \), return as is
    if (str_starts_with($name, '\\')) {
        return substr($name, 1); // Remove leading backslash
    }

    // Check if it's in use statements
    $useOption = $session->useStatements->get($name);
    if (!$useOption->isEmpty()) {
        return $useOption->get();
    }

    // Check if it contains a namespace separator
    if (str_contains($name, '\\')) {
        // It's already partially qualified, check if first part is in use statements
        $parts = explode('\\', $name);
        $firstPart = $parts[0];
        $useOption = $session->useStatements->get($firstPart);
        if (!$useOption->isEmpty()) {
            // Replace first part with its full name
            $parts[0] = $useOption->get();
            return implode('\\', $parts);
        }

        // If in a namespace, prepend current namespace
        if ($session->currentNamespace !== null) {
            return $session->currentNamespace . '\\' . $name;
        }

        return $name;
    }

    // Simple name - if in a namespace, prepend it
    if ($session->currentNamespace !== null) {
        return $session->currentNamespace . '\\' . $name;
    }

    return $name;
}

/**
 * Evaluates a static method call (e.g., Some::of(42)).
 *
 * @param Expr\StaticCall $node
 * @param ReplSession $session
 * @return Validation<EvaluationError, EvaluationResult>
 */
function evaluateStaticCall(Expr\StaticCall $node, ReplSession $session): Validation
{
    try {
        // Check if this is a first-class callable (e.g., Class::method(...))
        if (count($node->args) === 1 && $node->args[0] instanceof Node\VariadicPlaceholder) {
            return evaluateFirstClassCallable($node, $session);
        }

        $className = $node->class->toString();
        $methodName = $node->name->toString();

        // Resolve the class name using namespace and use statements
        $resolvedClassName = resolveName($className, $session);

        // Check if any arguments use named syntax
        $hasNamedArgs = false;
        foreach ($node->args as $arg) {
            if ($arg->name !== null) {
                $hasNamedArgs = true;
                break;
            }
        }

        // Handle named arguments
        if ($hasNamedArgs) {
            try {
                $reflection = new \ReflectionMethod($resolvedClassName, $methodName);
                $params = $reflection->getParameters();

                // Build an array mapping parameter names to positions
                $paramMap = [];
                foreach ($params as $i => $param) {
                    $paramMap[$param->getName()] = $i;
                }

                // Evaluate and organize arguments
                $orderedArgs = [];
                $nextPositionalIndex = 0;

                foreach ($node->args as $arg) {
                    $result = evaluateNode($arg->value, $session);
                    if ($result->isLeft()) {
                        return $result;
                    }
                    $evaluatedValue = $result->getOrElse(null)->value;

                    if ($arg->name !== null) {
                        // Named argument
                        $argName = $arg->name->toString();
                        if (!isset($paramMap[$argName])) {
                            throw new \RuntimeException("Unknown parameter: $argName");
                        }
                        $orderedArgs[$paramMap[$argName]] = $evaluatedValue;
                    } else {
                        // Positional argument
                        while (isset($orderedArgs[$nextPositionalIndex])) {
                            $nextPositionalIndex++;
                        }
                        $orderedArgs[$nextPositionalIndex] = $evaluatedValue;
                        $nextPositionalIndex++;
                    }
                }

                // Sort by parameter position and get values
                ksort($orderedArgs);
                $args = array_values($orderedArgs);

                // Call the static method
                $value = call_user_func_array([$resolvedClassName, $methodName], $args);
                return Success(EvaluationResult::of($value, getType($value)));
            } catch (\ReflectionException $e) {
                throw new \RuntimeException("Cannot use named arguments with method: $className::$methodName");
            }
        }

        // Evaluate arguments (positional only)
        $args = [];
        foreach ($node->args as $arg) {
            $result = evaluateNode($arg->value, $session);
            if ($result->isLeft()) {
                return $result;
            }
            $value = $result->getOrElse(null)->value;

            // Check if this is a spread operator (unpack flag)
            if ($arg->unpack) {
                // Value must be an array or iterable
                if (!is_array($value) && !($value instanceof \Traversable)) {
                    return Failure(new EvaluationError(
                        get_class($node),
                        'Only arrays and Traversables can be unpacked'
                    ));
                }

                // Spread the array/iterable into the arguments
                foreach ($value as $v) {
                    $args[] = $v;
                }
            } else {
                $args[] = $value;
            }
        }

        // Call the static method
        $value = call_user_func_array([$resolvedClassName, $methodName], $args);

        return Success(EvaluationResult::of($value, getType($value)));
    } catch (\Throwable $e) {
        return Failure(new EvaluationError(
            get_class($node),
            'Static call failed: ' . $e->getMessage()
        ));
    }
}

/**
 * Evaluates a method call (e.g., $var0->map(...)).
 *
 * @param Expr\MethodCall $node
 * @param ReplSession $session
 * @return Validation<EvaluationError, EvaluationResult>
 */
function evaluateMethodCall(Expr\MethodCall $node, ReplSession $session): Validation
{
    try {
        // Check if this is a first-class callable (e.g., $obj->method(...))
        if (count($node->args) === 1 && $node->args[0] instanceof Node\VariadicPlaceholder) {
            return evaluateFirstClassCallable($node, $session);
        }

        // Evaluate the object first
        $objResult = evaluateNode($node->var, $session);
        if ($objResult->isLeft()) {
            return $objResult;
        }

        $obj = $objResult->getOrElse(null)->value;
        $methodName = $node->name->toString();

        // Check if any arguments use named syntax
        $hasNamedArgs = false;
        foreach ($node->args as $arg) {
            if ($arg->name !== null) {
                $hasNamedArgs = true;
                break;
            }
        }

        // Handle named arguments
        if ($hasNamedArgs) {
            try {
                $reflection = new \ReflectionMethod($obj, $methodName);
                $params = $reflection->getParameters();

                // Build an array mapping parameter names to positions
                $paramMap = [];
                foreach ($params as $i => $param) {
                    $paramMap[$param->getName()] = $i;
                }

                // Evaluate and organize arguments
                $orderedArgs = [];
                $nextPositionalIndex = 0;

                foreach ($node->args as $arg) {
                    $result = evaluateNode($arg->value, $session);
                    if ($result->isLeft()) {
                        return $result;
                    }
                    $evaluatedValue = $result->getOrElse(null)->value;

                    if ($arg->name !== null) {
                        // Named argument
                        $argName = $arg->name->toString();
                        if (!isset($paramMap[$argName])) {
                            throw new \RuntimeException("Unknown parameter: $argName");
                        }
                        $orderedArgs[$paramMap[$argName]] = $evaluatedValue;
                    } else {
                        // Positional argument
                        while (isset($orderedArgs[$nextPositionalIndex])) {
                            $nextPositionalIndex++;
                        }
                        $orderedArgs[$nextPositionalIndex] = $evaluatedValue;
                        $nextPositionalIndex++;
                    }
                }

                // Sort by parameter position and get values
                ksort($orderedArgs);
                $args = array_values($orderedArgs);

                // Call the method
                $value = call_user_func_array([$obj, $methodName], $args);
                return Success(EvaluationResult::of($value, getType($value)));
            } catch (\ReflectionException $e) {
                throw new \RuntimeException("Cannot use named arguments with method: $methodName");
            }
        }

        // Evaluate arguments (positional only)
        $args = [];
        foreach ($node->args as $arg) {
            $result = evaluateNode($arg->value, $session);
            if ($result->isLeft()) {
                return $result;
            }
            $value = $result->getOrElse(null)->value;

            // Check if this is a spread operator (unpack flag)
            if ($arg->unpack) {
                // Value must be an array or iterable
                if (!is_array($value) && !($value instanceof \Traversable)) {
                    return Failure(new EvaluationError(
                        get_class($node),
                        'Only arrays and Traversables can be unpacked'
                    ));
                }

                // Spread the array/iterable into the arguments
                foreach ($value as $v) {
                    $args[] = $v;
                }
            } else {
                $args[] = $value;
            }
        }

        // Check if method is callable
        if (!is_callable([$obj, $methodName])) {
            return Failure(new EvaluationError(
                get_class($node),
                sprintf('Uncaught Error: Call to undefined method %s::%s()', is_object($obj) ? get_class($obj) : gettype($obj), $methodName)
            ));
        }

        // Call the method
        $value = call_user_func_array([$obj, $methodName], $args);

        return Success(EvaluationResult::of($value, getType($value)));
    } catch (\Throwable $e) {
        return Failure(new EvaluationError(
            get_class($node),
            'Method call failed: ' . $e->getMessage()
        ));
    }
}

/**
 * Evaluates a nullsafe method call (e.g., $var0?->map(...)).
 *
 * @param Expr\NullsafeMethodCall $node
 * @param ReplSession $session
 * @return Validation<EvaluationError, EvaluationResult>
 */
function evaluateNullsafeMethodCall(Expr\NullsafeMethodCall $node, ReplSession $session): Validation
{
    try {
        // Evaluate the object first
        $objResult = evaluateNode($node->var, $session);
        if ($objResult->isLeft()) {
            return $objResult;
        }

        $obj = $objResult->getOrElse(null)->value;

        // If the object is null, return null
        if ($obj === null) {
            return Success(EvaluationResult::of(null, 'Null'));
        }

        $methodName = $node->name->toString();

        // Evaluate arguments
        $args = [];
        foreach ($node->args as $arg) {
            $result = evaluateNode($arg->value, $session);
            if ($result->isLeft()) {
                return $result;
            }
            $args[] = $result->getOrElse(null)->value;
        }

        // Call the method
        $value = call_user_func_array([$obj, $methodName], $args);

        return Success(EvaluationResult::of($value, getType($value)));
    } catch (\Throwable $e) {
        return Failure(new EvaluationError(
            get_class($node),
            'Nullsafe method call failed: ' . $e->getMessage()
        ));
    }
}

/**
 * Evaluates a property fetch (e.g., $var0->property or Color::Red->name).
 *
 * @param Expr\PropertyFetch $node
 * @param ReplSession $session
 * @return Validation<EvaluationError, EvaluationResult>
 */
function evaluatePropertyFetch(Expr\PropertyFetch $node, ReplSession $session): Validation
{
    try {
        // Evaluate the object first
        $objResult = evaluateNode($node->var, $session);
        if ($objResult->isLeft()) {
            return $objResult;
        }

        $obj = $objResult->getOrElse(null)->value;
        $propertyName = $node->name->toString();

        // Access the property
        $value = $obj->$propertyName;

        return Success(EvaluationResult::of($value, getType($value)));
    } catch (\Throwable $e) {
        return Failure(new EvaluationError(
            get_class($node),
            'Property fetch failed: ' . $e->getMessage()
        ));
    }
}

/**
 * Evaluates a nullsafe property fetch (e.g., $var0?->property).
 *
 * @param Expr\NullsafePropertyFetch $node
 * @param ReplSession $session
 * @return Validation<EvaluationError, EvaluationResult>
 */
function evaluateNullsafePropertyFetch(Expr\NullsafePropertyFetch $node, ReplSession $session): Validation
{
    try {
        // Evaluate the object first
        $objResult = evaluateNode($node->var, $session);
        if ($objResult->isLeft()) {
            return $objResult;
        }

        $obj = $objResult->getOrElse(null)->value;

        // If the object is null, return null
        if ($obj === null) {
            return Success(EvaluationResult::of(null, 'Null'));
        }

        $propertyName = $node->name->toString();

        // Access the property
        $value = $obj->$propertyName;

        return Success(EvaluationResult::of($value, getType($value)));
    } catch (\Throwable $e) {
        return Failure(new EvaluationError(
            get_class($node),
            'Nullsafe property fetch failed: ' . $e->getMessage()
        ));
    }
}

/**
 * Evaluates a function call (e.g., Some(42), ImmList(1, 2, 3), $fn(1, 2)).
 *
 * @param Expr\FuncCall $node
 * @param ReplSession $session
 * @return Validation<EvaluationError, EvaluationResult>
 */
function evaluateFunctionCall(Expr\FuncCall $node, ReplSession $session): Validation
{
    try {
        // Check if this is a first-class callable (e.g., strlen(...))
        if (count($node->args) === 1 && $node->args[0] instanceof Node\VariadicPlaceholder) {
            return evaluateFirstClassCallable($node, $session);
        }

        // Check if this is a variable function call (e.g., $fn())
        if ($node->name instanceof Expr\Variable) {
            $varName = '$' . $node->name->name;
            $option = $session->variables->get($varName);

            if ($option->isEmpty()) {
                return Failure(new EvaluationError($varName, 'Variable not found'));
            }

            $func = $option->get();

            // If the value is a string, it might be a function name stored in the session
            if (is_string($func)) {
                // Try to resolve it as a user-defined function
                $sessionFuncName = '$' . $func;
                $funcOption = $session->variables->get($sessionFuncName);
                if (!$funcOption->isEmpty()) {
                    $func = $funcOption->get();
                } else {
                    // Check if it's a built-in function
                    if (!function_exists($func)) {
                        return Failure(new EvaluationError($varName, 'Variable is not callable'));
                    }
                }
            } elseif (!is_callable($func)) {
                return Failure(new EvaluationError($varName, 'Variable is not callable'));
            }

            // Check if any arguments use named syntax
            $hasNamedArgs = false;
            foreach ($node->args as $arg) {
                if ($arg->name !== null) {
                    $hasNamedArgs = true;
                    break;
                }
            }

            if ($hasNamedArgs) {
                // For user-defined functions with named arguments, we need to reorder
                // This requires reflection or parameter info which we don't have for closures
                return Failure(new EvaluationError(
                    $varName,
                    'Named arguments are not supported for variable function calls'
                ));
            }

            // Evaluate arguments (positional only)
            $args = [];
            foreach ($node->args as $arg) {
                $result = evaluateNode($arg->value, $session);
                if ($result->isLeft()) {
                    return $result;
                }
                $value = $result->getOrElse(null)->value;

                // Check if this is a spread operator (unpack flag)
                if ($arg->unpack) {
                    // Value must be an array or iterable
                    if (!is_array($value) && !($value instanceof \Traversable)) {
                        return Failure(new EvaluationError(
                            get_class($node),
                            'Only arrays and Traversables can be unpacked'
                        ));
                    }

                    // Spread the array/iterable into the arguments
                    foreach ($value as $v) {
                        $args[] = $v;
                    }
                } else {
                    $args[] = $value;
                }
            }

            // Call the function
            $value = call_user_func_array($func, $args);

            return Success(EvaluationResult::of($value, getType($value)));
        }

        // Check if this is an expression-based function call (e.g., $funcs[0]())
        // This handles cases where the function name is not a simple Name node
        if (!($node->name instanceof Node\Name)) {
            // Evaluate the expression to get the callable
            $callableResult = evaluateNode($node->name, $session);
            if ($callableResult->isLeft()) {
                return $callableResult;
            }

            $func = $callableResult->getOrElse(null)->value;

            if (!is_callable($func)) {
                return Failure(new EvaluationError('FuncCall', 'Expression is not callable'));
            }

            // Evaluate arguments
            $args = [];
            foreach ($node->args as $arg) {
                $argResult = evaluateNode($arg->value, $session);
                if ($argResult->isLeft()) {
                    return $argResult;
                }
                $args[] = $argResult->getOrElse(null)->value;
            }

            // Call the function
            $result = $func(...$args);
            return Success(EvaluationResult::of($result, getType($result)));
        }

        // Regular named function call
        $funcName = $node->name->toString();

        // Resolve the function name using namespace and use statements
        $resolvedFuncName = resolveName($funcName, $session);

        // Check if any arguments use named syntax
        $hasNamedArgs = false;
        foreach ($node->args as $arg) {
            if ($arg->name !== null) {
                $hasNamedArgs = true;
                break;
            }
        }

        // First check if it's a user-defined function in the session (stored with $ prefix)
        $sessionFuncName = '$' . $funcName;
        $option = $session->variables->get($sessionFuncName);
        if (!$option->isEmpty()) {
            $func = $option->get();
            if (is_callable($func)) {
                // Handle named arguments for user-defined functions
                if ($hasNamedArgs) {
                    // Try to get the function metadata (stored with __meta__ prefix)
                    $metaName = '$__meta__' . $funcName;
                    $metaOption = $session->variables->get($metaName);

                    if ($metaOption->isEmpty()) {
                        return Failure(new EvaluationError(
                            $funcName,
                            'Named arguments are not supported for this function'
                        ));
                    }

                    $metadata = $metaOption->get();
                    $params = $metadata['params'];

                    // Build an array mapping parameter names to positions
                    $paramMap = [];
                    foreach ($params as $i => $param) {
                        $paramMap[$param->var->name] = $i;
                    }

                    // Evaluate and organize arguments
                    $orderedArgs = [];
                    $nextPositionalIndex = 0;

                    foreach ($node->args as $arg) {
                        $result = evaluateNode($arg->value, $session);
                        if ($result->isLeft()) {
                            return $result;
                        }
                        $evaluatedValue = $result->getOrElse(null)->value;

                        if ($arg->name !== null) {
                            // Named argument
                            $argName = $arg->name->toString();
                            if (!isset($paramMap[$argName])) {
                                return Failure(new EvaluationError(
                                    $funcName,
                                    "Unknown parameter: $argName"
                                ));
                            }
                            $orderedArgs[$paramMap[$argName]] = $evaluatedValue;
                        } else {
                            // Positional argument - find next available position
                            while (isset($orderedArgs[$nextPositionalIndex])) {
                                $nextPositionalIndex++;
                            }
                            $orderedArgs[$nextPositionalIndex] = $evaluatedValue;
                            $nextPositionalIndex++;
                        }
                    }

                    // Fill in default values for missing parameters
                    $finalArgs = [];
                    foreach ($metadata['params'] as $i => $param) {
                        if (isset($orderedArgs[$i])) {
                            $finalArgs[] = $orderedArgs[$i];
                        } elseif ($param->default !== null) {
                            // Evaluate default value
                            $defaultResult = evaluateNode($param->default, $session);
                            if ($defaultResult->isLeft()) {
                                return $defaultResult;
                            }
                            $finalArgs[] = $defaultResult->getOrElse(null)->value;
                        } else {
                            // Required parameter missing - let the function handle the error
                            break;
                        }
                    }

                    // Call function using spread operator
                    // Type validation is done inside the function closure itself
                    $value = $func(...$finalArgs);
                    return Success(EvaluationResult::of($value, getType($value)));
                }

                // Evaluate arguments (positional only)
                $args = [];
                foreach ($node->args as $arg) {
                    $result = evaluateNode($arg->value, $session);
                    if ($result->isLeft()) {
                        return $result;
                    }
                    $value = $result->getOrElse(null)->value;

                    // Check if this is a spread operator (unpack flag)
                    if ($arg->unpack) {
                        // Value must be an array or iterable
                        if (!is_array($value) && !($value instanceof \Traversable)) {
                            return Failure(new EvaluationError(
                                get_class($node),
                                'Only arrays and Traversables can be unpacked'
                            ));
                        }

                        // Spread the array/iterable into the arguments
                        foreach ($value as $v) {
                            $args[] = $v;
                        }
                    } else {
                        $args[] = $value;
                    }
                }

                // Call function using spread operator
                // Type validation is done inside the function closure itself
                $value = $func(...$args);
                return Success(EvaluationResult::of($value, getType($value)));
            }
        }

        // Handle named arguments for built-in and library functions
        if ($hasNamedArgs) {
            // Get the actual function to call
            $actualFunc = null;
            if (function_exists($resolvedFuncName)) {
                $actualFunc = $resolvedFuncName;
            } elseif (function_exists($funcName)) {
                $actualFunc = $funcName;
            } else {
                throw new \RuntimeException("Function not found: $funcName (resolved to: $resolvedFuncName)");
            }

            // Use reflection to get parameter names
            try {
                $reflection = new \ReflectionFunction($actualFunc);
                $params = $reflection->getParameters();

                // Build an array mapping parameter names to positions
                $paramMap = [];
                foreach ($params as $i => $param) {
                    $paramMap[$param->getName()] = $i;
                }

                // Evaluate and organize arguments
                $orderedArgs = [];
                $nextPositionalIndex = 0;

                foreach ($node->args as $arg) {
                    $result = evaluateNode($arg->value, $session);
                    if ($result->isLeft()) {
                        return $result;
                    }
                    $evaluatedValue = $result->getOrElse(null)->value;

                    if ($arg->name !== null) {
                        // Named argument
                        $argName = $arg->name->toString();
                        if (!isset($paramMap[$argName])) {
                            throw new \RuntimeException("Unknown parameter: $argName");
                        }
                        $orderedArgs[$paramMap[$argName]] = $evaluatedValue;
                    } else {
                        // Positional argument
                        // Find the next available position that hasn't been filled
                        while (isset($orderedArgs[$nextPositionalIndex])) {
                            $nextPositionalIndex++;
                        }
                        $orderedArgs[$nextPositionalIndex] = $evaluatedValue;
                        $nextPositionalIndex++;
                    }
                }

                // Sort by parameter position and get values
                ksort($orderedArgs);
                $args = array_values($orderedArgs);

                // Call the function
                $value = call_user_func_array($actualFunc, $args);
                return Success(EvaluationResult::of($value, getType($value)));
            } catch (\ReflectionException $e) {
                throw new \RuntimeException("Cannot use named arguments with function: $funcName");
            }
        }

        // Evaluate arguments (positional only)
        $args = [];
        foreach ($node->args as $arg) {
            $result = evaluateNode($arg->value, $session);
            if ($result->isLeft()) {
                return $result;
            }
            $value = $result->getOrElse(null)->value;

            // Check if this is a spread operator (unpack flag)
            if ($arg->unpack) {
                // Value must be an array or iterable
                if (!is_array($value) && !($value instanceof \Traversable)) {
                    return Failure(new EvaluationError(
                        get_class($node),
                        'Only arrays and Traversables can be unpacked'
                    ));
                }

                // Spread the array/iterable into the arguments
                foreach ($value as $v) {
                    $args[] = $v;
                }
            } else {
                $args[] = $value;
            }
        }

        // Try to call the resolved function name first, fall back to original if it doesn't exist
        if (function_exists($resolvedFuncName)) {
            $value = call_user_func_array($resolvedFuncName, $args);
        } elseif (function_exists($funcName)) {
            $value = call_user_func_array($funcName, $args);
        } else {
            throw new \RuntimeException("Function not found: $funcName (resolved to: $resolvedFuncName)");
        }

        // Check if this is an output function that shouldn't get auto-assigned
        $outputFunctions = ['var_dump', 'print_r', 'var_export', 'debug_zval_dump', 'debug_print_backtrace'];
        $isOutputFunction = in_array(strtolower($funcName), $outputFunctions)
                           || in_array(strtolower($resolvedFuncName), $outputFunctions);

        return Success(EvaluationResult::of($value, getType($value), null, [], $isOutputFunction));
    } catch (\TypeError $e) {
        // Preserve TypeError as a separate error type
        // Clean up the error message to remove internal implementation details
        $cleanMessage = cleanErrorMessage($e->getMessage());
        return Failure(new TypeError(
            get_class($node),
            $cleanMessage
        ));
    } catch (\Throwable $e) {
        $cleanMessage = cleanErrorMessage($e->getMessage());
        return Failure(new EvaluationError(
            get_class($node),
            'Function call failed: ' . $cleanMessage
        ));
    }
}

/**
 * Gets the type name of a value.
 *
 * @param mixed $value
 * @return string
 */
function getType(mixed $value): string
{
    return match (true) {
        is_null($value) => 'Null',
        is_bool($value) => 'Bool',
        is_int($value) => 'Int',
        is_float($value) => 'Float',
        is_string($value) => 'String',
        is_array($value) => 'Array',
        $value instanceof \Closure => 'Callable',
        $value instanceof \Generator => 'Generator',
        is_object($value) => getObjectType($value),
        default => 'Unknown'
    };
}

/**
 * Gets the type name for an object.
 *
 * @param object $obj
 * @return string
 */
function getObjectType(object $obj): string
{
    $class = get_class($obj);

    // Handle anonymous classes - format as "class@anonymous"
    if (str_contains($class, 'class@anonymous')) {
        return 'class@anonymous';
    }

    // Handle enum cases
    if (enum_exists($class)) {
        return $class;
    }

    // Use showType() if available (for Phunkie types with Show trait)
    // But catch errors for types missing the 'kind' constant (ImmSet, ImmMap)
    if (method_exists($obj, 'showType')) {
        try {
            return $obj->showType();
        } catch (\Error $e) {
            // If showType() fails due to missing kind constant, use class name
            if (str_contains($e->getMessage(), '::kind')) {
                if (str_contains($class, 'Phunkie\\Types\\')) {
                    $parts = explode('\\', $class);
                    $name = end($parts);

                    // For ImmSet and ImmMap, get type variables manually
                    if (method_exists($obj, 'getTypeVariables')) {
                        $typeVars = $obj->getTypeVariables();
                        if (!empty($typeVars)) {
                            return sprintf("%s<%s>", $name, implode(", ", $typeVars));
                        }
                    }

                    return $name;
                }
            }
            throw $e;
        }
    }

    // Extract simple class name for Phunkie types
    if (str_contains($class, 'Phunkie\\Types\\')) {
        $parts = explode('\\', $class);
        return end($parts);
    }

    return $class;
}

/**
 * Evaluates a constant expression.
 *
 * @param Expr\ConstFetch $node
 * @return Validation<EvaluationError, EvaluationResult>
 */
function evaluateConstant(Expr\ConstFetch $node): Validation
{
    try {
        $constName = $node->name->toString();

        if (defined($constName)) {
            $value = constant($constName);
            return Success(EvaluationResult::of($value, getType($value)));
        }

        return Failure(new EvaluationError(
            $constName,
            "Undefined constant: $constName"
        ));
    } catch (\Throwable $e) {
        return Failure(new EvaluationError(
            get_class($node),
            'Constant evaluation failed: ' . $e->getMessage()
        ));
    }
}

/**
 * Evaluates a class constant fetch (e.g., Color::Red for enum cases, MyClass::CONSTANT for class constants).
 *
 * @param Expr\ClassConstFetch $node
 * @param ReplSession $session
 * @return Validation<EvaluationError, EvaluationResult>
 */
function evaluateClassConstFetch(Expr\ClassConstFetch $node, ReplSession $session): Validation
{
    try {
        $className = $node->class->toString();

        // Handle dynamic constant fetch (PHP 8.3+): Class::{$var}
        if ($node->name instanceof Expr) {
            $nameResult = evaluateNode($node->name, $session);
            if ($nameResult->isLeft()) {
                return $nameResult;
            }
            $constName = $nameResult->getOrElse(null)->value;

            if (!is_string($constName)) {
                return Failure(new EvaluationError(
                    'ClassConstFetch',
                    'Dynamic constant name must be a string'
                ));
            }
        } else {
            $constName = $node->name->toString();
        }

        // Check if it's an enum
        if (enum_exists($className)) {
            // Try to get the enum case
            $cases = $className::cases();
            foreach ($cases as $case) {
                if ($case->name === $constName) {
                    return Success(EvaluationResult::of($case, getType($case)));
                }
            }

            return Failure(new EvaluationError(
                "$className::$constName",
                "Undefined enum case: $className::$constName"
            ));
        }

        // Check if it's a class constant
        if (class_exists($className) || interface_exists($className)) {
            if (defined("$className::$constName")) {
                $value = constant("$className::$constName");
                return Success(EvaluationResult::of($value, getType($value)));
            }

            return Failure(new EvaluationError(
                "$className::$constName",
                "Undefined class constant: $className::$constName"
            ));
        }

        return Failure(new EvaluationError(
            $className,
            "Undefined class or enum: $className"
        ));
    } catch (\Throwable $e) {
        return Failure(new EvaluationError(
            get_class($node),
            'Class constant fetch failed: ' . $e->getMessage()
        ));
    }
}

/**
 * Evaluates an arrow function and returns a Callable.
 *
 * @param Expr\ArrowFunction $node
 * @param ReplSession $session
 * @return Validation<EvaluationError, EvaluationResult>
 */
function evaluateArrowFunction(Expr\ArrowFunction $node, ReplSession $session): Validation
{
    try {
        // Capture the arrow function AST and session for later execution
        $arrowFn = function (...$args) use ($node, $session) {
            // Create a new session with the function parameters bound
            $newVars = $session->variables;
            foreach ($node->params as $i => $param) {
                $paramName = '$' . $param->var->name;
                $newVars = $newVars->plus($paramName, $args[$i] ?? null);
            }

            $newSession = new ReplSession(
                $session->history,
                $newVars,
                $session->colorEnabled,
                $session->variableCounter
            );

            // Evaluate the arrow function body
            $result = evaluateNode($node->expr, $newSession);

            if ($result->isLeft()) {
                // Get the error using fold
                $error = $result->fold(fn($e) => $e)(fn($r) => null);
                throw new \RuntimeException('Arrow function evaluation failed: ' . $error->reason);
            }

            return $result->getOrElse(null)->value;
        };

        return Success(EvaluationResult::of($arrowFn, 'Callable'));
    } catch (\Throwable $e) {
        return Failure(new EvaluationError(
            'ArrowFunction',
            'Arrow function creation failed: ' . $e->getMessage()
        ));
    }
}

/**
 * Evaluates a closure (anonymous function).
 *
 * @param Expr\Closure $node
 * @param ReplSession $session
 * @return Validation<EvaluationError, EvaluationResult>
 */
function evaluateClosure(Expr\Closure $node, ReplSession $session): Validation
{
    try {
        // Capture used variables from the use clause
        $useVars = [];
        foreach ($node->uses as $use) {
            $varName = '$' . $use->var->name;
            $varOption = $session->variables->get($varName);
            if ($varOption->isDefined()) {
                $useVars[$varName] = $varOption->get();
            }
        }

        // Create the closure
        $closure = function (...$args) use ($node, $session, $useVars) {
            // Create a new session with the function parameters bound
            $newVars = $session->variables;

            // Add use clause variables
            foreach ($useVars as $name => $value) {
                $newVars = $newVars->plus($name, $value);
            }

            // Add function parameters
            foreach ($node->params as $i => $param) {
                $paramName = '$' . $param->var->name;
                $newVars = $newVars->plus($paramName, $args[$i] ?? null);
            }

            $newSession = new ReplSession(
                $session->history,
                $newVars,
                $session->colorEnabled,
                $session->variableCounter
            );

            // Evaluate the closure body (statements)
            $returnValue = null;
            foreach ($node->stmts as $stmt) {
                if ($stmt instanceof Node\Stmt\Return_) {
                    if ($stmt->expr !== null) {
                        $result = evaluateNode($stmt->expr, $newSession);
                        if ($result->isLeft()) {
                            $error = $result->fold(fn($e) => $e)(fn($r) => null);
                            throw new \RuntimeException('Closure evaluation failed: ' . $error->reason);
                        }
                        $returnValue = $result->getOrElse(null)->value;
                    }
                    break;
                } elseif ($stmt instanceof Node\Stmt\Expression) {
                    $result = evaluateNode($stmt->expr, $newSession);
                    if ($result->isLeft()) {
                        $error = $result->fold(fn($e) => $e)(fn($r) => null);
                        throw new \RuntimeException('Closure evaluation failed: ' . $error->reason);
                    }
                    // Update session if this is an assignment
                    if ($stmt->expr instanceof Expr\Assign) {
                        $varName = $stmt->expr->var instanceof Expr\Variable && is_string($stmt->expr->var->name)
                            ? '$' . $stmt->expr->var->name
                            : null;
                        if ($varName) {
                            $newVars = $newVars->plus($varName, $result->getOrElse(null)->value);
                            $newSession = new ReplSession(
                                $newSession->history,
                                $newVars,
                                $newSession->colorEnabled,
                                $newSession->variableCounter
                            );
                        }
                    }
                }
            }

            return $returnValue;
        };

        return Success(EvaluationResult::of($closure, 'Callable'));
    } catch (\Throwable $e) {
        return Failure(new EvaluationError(
            'Closure',
            'Closure creation failed: ' . $e->getMessage()
        ));
    }
}

/**
 * Evaluates a ternary expression (used for if-else structures).
 *
 * @param Expr\Ternary $node
 * @param ReplSession $session
 * @return Validation<EvaluationError, EvaluationResult>
 */
function evaluateTernary(Expr\Ternary $node, ReplSession $session): Validation
{
    try {
        // Evaluate the condition
        $condResult = evaluateNode($node->cond, $session);
        if ($condResult->isLeft()) {
            return $condResult;
        }

        $condition = $condResult->getOrElse(null)->value;

        // Evaluate the appropriate branch
        if ($condition) {
            // If 'if' part is null, return the condition value (short ternary)
            if ($node->if === null) {
                return $condResult;
            }
            return evaluateNode($node->if, $session);
        } else {
            return evaluateNode($node->else, $session);
        }
    } catch (\Throwable $e) {
        return Failure(new EvaluationError(
            'Ternary',
            'Ternary evaluation failed: ' . $e->getMessage()
        ));
    }
}

/**
 * Evaluates a match expression.
 *
 * @param Expr\Match_ $node
 * @param ReplSession $session
 * @return Validation<EvaluationError, EvaluationResult>
 */
function evaluateMatch(Expr\Match_ $node, ReplSession $session): Validation
{
    try {
        // Evaluate the condition expression
        $condResult = evaluateNode($node->cond, $session);
        if ($condResult->isLeft()) {
            return $condResult;
        }

        $condValue = $condResult->getOrElse(null)->value;

        // Iterate through match arms
        foreach ($node->arms as $arm) {
            // Check if this is the default arm
            if ($arm->conds === null) {
                return evaluateNode($arm->body, $session);
            }

            // Check each condition in the arm
            foreach ($arm->conds as $cond) {
                $armCondResult = evaluateNode($cond, $session);
                if ($armCondResult->isLeft()) {
                    return $armCondResult;
                }

                $armCondValue = $armCondResult->getOrElse(null)->value;

                // If match found, evaluate the body
                if ($condValue === $armCondValue) {
                    return evaluateNode($arm->body, $session);
                }
            }
        }

        // No match found and no default case
        return Failure(new EvaluationError(
            'Match',
            'No matching case found in match expression'
        ));
    } catch (\Throwable $e) {
        return Failure(new EvaluationError(
            'Match',
            'Match evaluation failed: ' . $e->getMessage()
        ));
    }
}

/**
 * Evaluates an if statement.
 *
 * @param Node\Stmt\If_ $stmt
 * @param ReplSession $session
 * @return Validation<EvaluationError, EvaluationResult>
 * @throws FunctionReturnException When a return statement is encountered in any branch
 */
function evaluateIfStatement(Node\Stmt\If_ $stmt, ReplSession $session): Validation
{
    try {
        // Evaluate the condition
        $condResult = evaluateNode($stmt->cond, $session);
        if ($condResult->isLeft()) {
            return $condResult;
        }

        $condition = $condResult->getOrElse(null)->value;

        // Evaluate the appropriate branch
        if ($condition) {
            // Execute the if branch - get the last expression value
            // Note: evaluateStmtBlock may throw FunctionReturnException
            return evaluateStmtBlock($stmt->stmts, $session);
        } else {
            // Check for elseif or else clauses
            if (!empty($stmt->elseifs)) {
                foreach ($stmt->elseifs as $elseif) {
                    $elseifCondResult = evaluateNode($elseif->cond, $session);
                    if ($elseifCondResult->isLeft()) {
                        return $elseifCondResult;
                    }

                    if ($elseifCondResult->getOrElse(null)->value) {
                        return evaluateStmtBlock($elseif->stmts, $session);
                    }
                }
            }

            // Execute else branch if present
            if ($stmt->else !== null) {
                return evaluateStmtBlock($stmt->else->stmts, $session);
            }

            // No else branch, return null
            return Success(EvaluationResult::of(null, 'Null'));
        }
    } catch (FunctionReturnException $e) {
        // Re-throw FunctionReturnException to propagate returns up the call stack
        throw $e;
    } catch (\Throwable $e) {
        return Failure(new EvaluationError(
            'If',
            'If statement evaluation failed: ' . $e->getMessage()
        ));
    }
}

/**
 * Evaluates a for loop.
 *
 * @param Node\Stmt\For_ $stmt
 * @param ReplSession $session
 * @return Validation<EvaluationError, EvaluationResult>
 */
function evaluateForLoop(Node\Stmt\For_ $stmt, ReplSession $session): Validation
{
    try {
        // Evaluate initialization expressions and update session
        $currentVars = $session->variables;
        foreach ($stmt->init as $initExpr) {
            $result = evaluateNode($initExpr, $session);
            if ($result->isLeft()) {
                return $result;
            }
            // Check if this was an assignment and update the variables map
            $evalResult = $result->getOrElse(null);
            if ($evalResult->assignedVariable !== null) {
                $currentVars = $currentVars->plus($evalResult->assignedVariable, $evalResult->value);
            }
        }

        // Create updated session for the loop
        $loopSession = new ReplSession(
            $session->history,
            $currentVars,
            $session->colorEnabled,
            $session->variableCounter,
            $session->incompleteInput,
            $session->currentNamespace,
            $session->useStatements
        );

        // Loop
        while (true) {
            // Evaluate condition expressions
            $conditionMet = true;
            foreach ($stmt->cond as $condExpr) {
                $result = evaluateNode($condExpr, $loopSession);
                if ($result->isLeft()) {
                    return $result;
                }
                if (!$result->getOrElse(null)->value) {
                    $conditionMet = false;
                    break;
                }
            }

            if (!$conditionMet) {
                break;
            }

            // Execute loop body
            $bodyResult = evaluateStmtBlock($stmt->stmts, $loopSession);
            if ($bodyResult->isLeft()) {
                return $bodyResult;
            }

            // Evaluate loop expressions (increment) and update session
            $currentVars = $loopSession->variables;
            foreach ($stmt->loop as $loopExpr) {
                $result = evaluateNode($loopExpr, $loopSession);
                if ($result->isLeft()) {
                    return $result;
                }
                // Check if this was an assignment/increment and update the variables map
                $evalResult = $result->getOrElse(null);
                if ($evalResult->assignedVariable !== null) {
                    $currentVars = $currentVars->plus($evalResult->assignedVariable, $evalResult->value);
                }
                // Handle additional assignments (from $i++ operations)
                foreach ($evalResult->additionalAssignments as $varName => $value) {
                    $currentVars = $currentVars->plus($varName, $value);
                }
            }

            // Update loop session with new variables
            $loopSession = new ReplSession(
                $loopSession->history,
                $currentVars,
                $loopSession->colorEnabled,
                $loopSession->variableCounter,
                $loopSession->incompleteInput,
                $loopSession->currentNamespace,
                $loopSession->useStatements
            );
        }

        return Success(EvaluationResult::of(null, 'Null'));
    } catch (FunctionReturnException $e) {
        throw $e;
    } catch (\Throwable $e) {
        return Failure(new EvaluationError(
            'For',
            'For loop evaluation failed: ' . $e->getMessage()
        ));
    }
}

/**
 * Evaluates a while loop.
 *
 * @param Node\Stmt\While_ $stmt
 * @param ReplSession $session
 * @return Validation<EvaluationError, EvaluationResult>
 */
function evaluateWhileLoop(Node\Stmt\While_ $stmt, ReplSession $session): Validation
{
    try {
        $loopSession = $session;

        while (true) {
            // Evaluate condition
            $condResult = evaluateNode($stmt->cond, $loopSession);
            if ($condResult->isLeft()) {
                return $condResult;
            }

            if (!$condResult->getOrElse(null)->value) {
                break;
            }

            // Execute loop body and capture variable updates
            $blockResult = evaluateStmtBlockWithSession($stmt->stmts, $loopSession);
            if ($blockResult->isLeft()) {
                /** @var \Phunkie\Validation\Failure $blockResult */
                return Failure($blockResult->fold(fn($e) => $e)(fn($s) => $s));
            }

            // Update loop session with any variable changes from the body
            $stmtBlockResult = $blockResult->getOrElse(null);
            $loopSession = $stmtBlockResult->updatedSession;
        }

        return Success(EvaluationResult::of(null, 'Null'));
    } catch (FunctionReturnException $e) {
        throw $e;
    } catch (\Throwable $e) {
        return Failure(new EvaluationError(
            'While',
            'While loop evaluation failed: ' . $e->getMessage()
        ));
    }
}

/**
 * Evaluates a do-while loop.
 *
 * @param Node\Stmt\Do_ $stmt
 * @param ReplSession $session
 * @return Validation<EvaluationError, EvaluationResult>
 */
function evaluateDoWhileLoop(Node\Stmt\Do_ $stmt, ReplSession $session): Validation
{
    try {
        $loopSession = $session;

        do {
            // Execute loop body and capture variable updates
            $blockResult = evaluateStmtBlockWithSession($stmt->stmts, $loopSession);
            if ($blockResult->isLeft()) {
                /** @var \Phunkie\Validation\Failure $blockResult */
                return Failure($blockResult->fold(fn($e) => $e)(fn($s) => $s));
            }

            // Update loop session with any variable changes from the body
            $stmtBlockResult = $blockResult->getOrElse(null);
            $loopSession = $stmtBlockResult->updatedSession;

            // Evaluate condition
            $condResult = evaluateNode($stmt->cond, $loopSession);
            if ($condResult->isLeft()) {
                return $condResult;
            }
        } while ($condResult->getOrElse(null)->value);

        return Success(EvaluationResult::of(null, 'Null'));
    } catch (FunctionReturnException $e) {
        throw $e;
    } catch (\Throwable $e) {
        return Failure(new EvaluationError(
            'DoWhile',
            'Do-while loop evaluation failed: ' . $e->getMessage()
        ));
    }
}

/**
 * Evaluates a foreach loop.
 *
 * @param Node\Stmt\Foreach_ $stmt
 * @param ReplSession $session
 * @return Validation<EvaluationError, EvaluationResult>
 */
function evaluateForeachLoop(Node\Stmt\Foreach_ $stmt, ReplSession $session): Validation
{
    try {
        // Evaluate the expression to iterate over
        $exprResult = evaluateNode($stmt->expr, $session);
        if ($exprResult->isLeft()) {
            return $exprResult;
        }

        $iterable = $exprResult->getOrElse(null)->value;

        // Iterate
        foreach ($iterable as $key => $value) {
            // Build updated variables map
            $currentVars = $session->variables;

            // Set the value variable
            if ($stmt->valueVar instanceof Expr\Variable && is_string($stmt->valueVar->name)) {
                $currentVars = $currentVars->plus('$' . $stmt->valueVar->name, $value);
            }

            // Set the key variable if present
            if ($stmt->keyVar !== null && $stmt->keyVar instanceof Expr\Variable && is_string($stmt->keyVar->name)) {
                $currentVars = $currentVars->plus('$' . $stmt->keyVar->name, $key);
            }

            // Create updated session for this iteration
            $iterSession = new ReplSession(
                $session->history,
                $currentVars,
                $session->colorEnabled,
                $session->variableCounter,
                $session->incompleteInput,
                $session->currentNamespace,
                $session->useStatements
            );

            // Execute loop body
            $bodyResult = evaluateStmtBlock($stmt->stmts, $iterSession);
            if ($bodyResult->isLeft()) {
                return $bodyResult;
            }
        }

        return Success(EvaluationResult::of(null, 'Null'));
    } catch (FunctionReturnException $e) {
        throw $e;
    } catch (\Throwable $e) {
        return Failure(new EvaluationError(
            'Foreach',
            'Foreach loop evaluation failed: ' . $e->getMessage()
        ));
    }
}

/**
 * Evaluates an enum definition.
 *
 * @param Node\Stmt\Enum_ $stmt
 * @param ReplSession $session
 * @return Validation<EvaluationError, EvaluationResult>
 */
function evaluateEnumDefinition(Node\Stmt\Enum_ $stmt, ReplSession $session): Validation
{
    try {
        $enumName = $stmt->name->toString();

        // Use PHP-Parser's pretty printer to convert the AST back to PHP code
        $printer = new \PhpParser\PrettyPrinter\Standard();
        $code = $printer->prettyPrint([$stmt]);

        // Set up error handler to catch fatal errors from eval()
        $errorMessage = null;
        set_error_handler(function ($severity, $message, $file, $line) use (&$errorMessage) {
            $errorMessage = $message;
            return true; // Don't execute PHP's internal error handler
        });

        try {
            // Use eval() to define the enum in the current scope
            eval($code);
        } finally {
            restore_error_handler();
        }

        // Check if an error occurred during eval
        if ($errorMessage !== null) {
            return Failure(new EvaluationError(
                $enumName,
                $errorMessage
            ));
        }

        // Check if enum was successfully defined
        if (!enum_exists($enumName)) {
            return Failure(new EvaluationError(
                $enumName,
                "Failed to define enum: $enumName"
            ));
        }

        // Return a special result indicating an enum was defined
        return Success(EvaluationResult::of($enumName, 'EnumDefinition'));
    } catch (\Throwable $e) {
        return Failure(new EvaluationError(
            'Enum',
            'Enum definition failed: ' . $e->getMessage()
        ));
    }
}

/**
 * Evaluates a print expression.
 *
 * @param Expr\Print_ $node
 * @param ReplSession $session
 * @return Validation<EvaluationError, EvaluationResult>
 */
function evaluatePrint(Expr\Print_ $node, ReplSession $session): Validation
{
    try {
        // Evaluate the expression
        $result = evaluateNode($node->expr, $session);
        if ($result->isLeft()) {
            return $result;
        }
        $value = $result->getOrElse(null)->value;

        // Convert value to string for printing
        $output = '';
        if (is_string($value)) {
            $output = $value;
        } elseif (is_bool($value)) {
            $output = $value ? '1' : '';
        } elseif (is_null($value)) {
            // null outputs nothing
        } elseif (is_scalar($value)) {
            $output = (string) $value;
        } elseif (is_array($value)) {
            $output = 'Array';
        } elseif (is_object($value)) {
            if (method_exists($value, '__toString')) {
                $output = (string) $value;
            } else {
                $output = 'Object';
            }
        }

        // Actually print the output
        echo $output;

        // Print returns 1 (unlike echo which returns null)
        // Mark as output statement so it doesn't get auto-assigned to a variable
        return Success(EvaluationResult::of(1, 'Int', null, [], true));
    } catch (\Throwable $e) {
        return Failure(new EvaluationError(
            'Print',
            'Print expression failed: ' . $e->getMessage()
        ));
    }
}

/**
 * Evaluates a throw expression.
 *
 * @param Expr\Throw_ $node
 * @param ReplSession $session
 * @return Validation<EvaluationError, EvaluationResult>
 */
function evaluateThrow(Expr\Throw_ $node, ReplSession $session): Validation
{
    try {
        // Evaluate the exception expression
        $result = evaluateNode($node->expr, $session);
        if ($result->isLeft()) {
            return $result;
        }

        $exception = $result->getOrElse(null)->value;

        // Verify it's an exception object
        if (!($exception instanceof \Throwable)) {
            return Failure(new EvaluationError(
                'Throw',
                'Throw expression requires a Throwable instance, got ' . getType($exception)
            ));
        }

        // Return a Failure with the exception wrapped in an EvaluationError
        return Failure(new EvaluationError(
            get_class($exception),
            $exception->getMessage()
        ));
    } catch (\Throwable $e) {
        return Failure(new EvaluationError(
            'Throw',
            'Throw expression evaluation failed: ' . $e->getMessage()
        ));
    }
}

/**
 * Evaluates an instanceof check.
 *
 * @param Expr\Instanceof_ $node
 * @param ReplSession $session
 * @return Validation<EvaluationError, EvaluationResult>
 */
function evaluateInstanceof(Expr\Instanceof_ $node, ReplSession $session): Validation
{
    try {
        // Evaluate the left operand (the object to check)
        $leftResult = evaluateNode($node->expr, $session);
        if ($leftResult->isLeft()) {
            return $leftResult;
        }
        $object = $leftResult->getOrElse(null)->value;

        // Resolve the class name from the right operand
        $className = null;

        if ($node->class instanceof Node\Name) {
            // Static class name (e.g., instanceof MyClass)
            $className = $node->class->toString();
            $className = resolveName($className, $session);
        } elseif ($node->class instanceof Expr\Variable) {
            // Variable class name (e.g., instanceof $className)
            $classNameResult = evaluateNode($node->class, $session);
            if ($classNameResult->isLeft()) {
                return $classNameResult;
            }
            $className = $classNameResult->getOrElse(null)->value;

            if (!is_string($className)) {
                return Failure(new EvaluationError(
                    'Instanceof',
                    'Class name must be a string'
                ));
            }
        } else {
            // Other expressions that evaluate to a class name
            $classNameResult = evaluateNode($node->class, $session);
            if ($classNameResult->isLeft()) {
                return $classNameResult;
            }
            $className = $classNameResult->getOrElse(null)->value;

            if (!is_string($className)) {
                return Failure(new EvaluationError(
                    'Instanceof',
                    'Class name must be a string'
                ));
            }
        }

        // Perform the instanceof check
        $result = $object instanceof $className;

        return Success(EvaluationResult::of($result, 'Bool'));
    } catch (\Throwable $e) {
        return Failure(new EvaluationError(
            'Instanceof',
            'Instanceof check failed: ' . $e->getMessage()
        ));
    }
}

/**
 * Evaluates a clone expression (e.g., clone $obj).
 *
 * @param Expr\Clone_ $node
 * @param ReplSession $session
 * @return Validation<EvaluationError, EvaluationResult>
 */
function evaluateClone(Expr\Clone_ $node, ReplSession $session): Validation
{
    try {
        // Evaluate the expression to clone
        $result = evaluateNode($node->expr, $session);
        if ($result->isLeft()) {
            return $result;
        }
        $value = $result->getOrElse(null)->value;

        // Verify that the value is an object
        if (!is_object($value)) {
            return Failure(new EvaluationError(
                'Clone',
                'Cannot clone non-object (' . \gettype($value) . ')'
            ));
        }

        // Clone the object using PHP's clone operator
        $clonedValue = clone $value;

        return Success(EvaluationResult::of($clonedValue, getType($clonedValue)));
    } catch (\Throwable $e) {
        return Failure(new EvaluationError(
            'Clone',
            'Clone expression failed: ' . $e->getMessage()
        ));
    }
}

/**
 * Evaluates an error suppression expression (@).
 *
 * @param Expr\ErrorSuppress $node
 * @param ReplSession $session
 * @return Validation<EvaluationError, EvaluationResult>
 */
function evaluateErrorSuppress(Expr\ErrorSuppress $node, ReplSession $session): Validation
{
    try {
        // Set up error handler to suppress errors
        $oldHandler = set_error_handler(function () {
            // Suppress all errors and warnings
            return true;
        });

        try {
            // Evaluate the expression with errors suppressed
            $result = evaluateNode($node->expr, $session);

            // If the result is a Failure (e.g., undefined variable), convert to null
            // The @ operator should suppress these errors and return null
            if ($result->isLeft()) {
                return Success(EvaluationResult::of(null, 'Null'));
            }

            return $result;
        } finally {
            // Restore previous error handler
            if ($oldHandler !== null) {
                set_error_handler($oldHandler);
            } else {
                restore_error_handler();
            }
        }
    } catch (\Throwable $e) {
        return Failure(new EvaluationError(
            'ErrorSuppress',
            'Error suppression failed: ' . $e->getMessage()
        ));
    }
}

/**
 * Evaluates increment/decrement operators (++, --).
 *
 * @param Expr\PreInc|Expr\PreDec|Expr\PostInc|Expr\PostDec $node
 * @param ReplSession $session
 * @return Validation<EvaluationError, EvaluationResult>
 */
function evaluateIncDec(Expr $node, ReplSession $session): Validation
{
    try {
        // Get the variable being incremented/decremented
        if (!($node->var instanceof Expr\Variable)) {
            return Failure(new EvaluationError(
                'IncDec',
                'Increment/decrement only supports simple variables'
            ));
        }

        $varName = '$' . $node->var->name;

        // Get current value
        $option = $session->variables->get($varName);
        if ($option->isEmpty()) {
            return Failure(new EvaluationError($varName, 'Variable not found'));
        }

        $currentValue = $option->get();

        // Ensure it's a number
        if (!is_int($currentValue) && !is_float($currentValue)) {
            return Failure(new EvaluationError(
                $varName,
                'Increment/decrement requires numeric value, got ' . gettype($currentValue)
            ));
        }

        // Calculate new value based on operation
        $newValue = ($node instanceof Expr\PreInc || $node instanceof Expr\PostInc)
            ? $currentValue + 1
            : $currentValue - 1;

        // Determine return value (pre-inc/dec returns new value, post-inc/dec returns old value)
        $returnValue = ($node instanceof Expr\PreInc || $node instanceof Expr\PreDec)
            ? $newValue
            : $currentValue;

        // Update the variable in the session
        // For inc/dec, we return the value but update the variable via additional assignments
        // We DON'T set assignedVariable because that would store returnValue instead of newValue
        return Success(EvaluationResult::of(
            $returnValue,
            getType($returnValue),
            null,  // No assigned variable - this is an expression result
            [$varName => $newValue]  // Use additional assignments to update the variable
        ));
    } catch (\Throwable $e) {
        return Failure(new EvaluationError(
            'IncDec',
            'Increment/decrement failed: ' . $e->getMessage()
        ));
    }
}

/**
 * Evaluates magic constants (__LINE__, __FILE__, etc.).
 *
 * @param Node\Scalar\MagicConst $node
 * @param ReplSession $session
 * @return Validation<EvaluationError, EvaluationResult>
 */
function evaluateMagicConstant(Node\Scalar\MagicConst $node, ReplSession $session): Validation
{
    try {
        $value = match (true) {
            $node instanceof Node\Scalar\MagicConst\Line => 1,
            $node instanceof Node\Scalar\MagicConst\File => 'php://stdin',
            $node instanceof Node\Scalar\MagicConst\Dir => getcwd(),
            $node instanceof Node\Scalar\MagicConst\Function_ => '',
            $node instanceof Node\Scalar\MagicConst\Class_ => '',
            $node instanceof Node\Scalar\MagicConst\Method => '',
            $node instanceof Node\Scalar\MagicConst\Namespace_ => '',
            $node instanceof Node\Scalar\MagicConst\Trait_ => '',
            default => null,
        };

        return Success(EvaluationResult::of($value, getType($value)));
    } catch (\Throwable $e) {
        return Failure(new EvaluationError(
            'MagicConst',
            'Magic constant evaluation failed: ' . $e->getMessage()
        ));
    }
}

/**
 * Evaluates a first-class callable (e.g., strlen(...), Class::method(...)).
 *
 * @param Expr\FuncCall|Expr\StaticCall|Expr\MethodCall $node
 * @param ReplSession $session
 * @return Validation<EvaluationError, EvaluationResult>
 */
function evaluateFirstClassCallable($node, ReplSession $session): Validation
{
    try {
        $callable = null;

        if ($node instanceof Expr\FuncCall) {
            // Function callable: strlen(...)
            if ($node->name instanceof Node\Name) {
                $funcName = $node->name->toString();

                // Check if it's a user-defined function stored in session
                $sessionFuncName = '$' . $funcName;
                $funcOption = $session->variables->get($sessionFuncName);

                if (!$funcOption->isEmpty()) {
                    $func = $funcOption->get();
                    // For user-defined functions stored as AST, we can't easily create a Closure
                    // We would need to eval the function definition again or store it differently
                    return Failure(new EvaluationError(
                        $funcName,
                        'First-class callable syntax not supported for user-defined functions yet'
                    ));
                }

                // Built-in function
                if (!function_exists($funcName)) {
                    return Failure(new EvaluationError($funcName, 'Function not found'));
                }

                $callable = \Closure::fromCallable($funcName);
            }
        } elseif ($node instanceof Expr\StaticCall) {
            // Static method callable: Class::method(...)
            $className = $node->class->toString();
            $methodName = $node->name->toString();

            // Resolve the class name using namespace and use statements
            $resolvedClassName = resolveName($className, $session);

            if (!class_exists($resolvedClassName)) {
                return Failure(new EvaluationError($className, 'Class not found'));
            }

            if (!method_exists($resolvedClassName, $methodName)) {
                return Failure(new EvaluationError("$className::$methodName", 'Method not found'));
            }

            $callable = \Closure::fromCallable([$resolvedClassName, $methodName]);
        } elseif ($node instanceof Expr\MethodCall) {
            // Instance method callable: $obj->method(...)
            $objResult = evaluateNode($node->var, $session);
            if ($objResult->isLeft()) {
                return $objResult;
            }

            $obj = $objResult->getOrElse(null)->value;
            $methodName = $node->name->toString();

            if (!is_object($obj)) {
                return Failure(new EvaluationError('MethodCall', 'Cannot call method on non-object'));
            }

            if (!method_exists($obj, $methodName)) {
                $className = get_class($obj);
                return Failure(new EvaluationError("$className::$methodName", 'Method not found'));
            }

            $callable = \Closure::fromCallable([$obj, $methodName]);
        }

        if ($callable === null) {
            return Failure(new EvaluationError('FirstClassCallable', 'Could not create callable'));
        }

        return Success(EvaluationResult::of($callable, 'Callable'));
    } catch (\Throwable $e) {
        return Failure(new EvaluationError(
            'FirstClassCallable',
            'First-class callable creation failed: ' . $e->getMessage()
        ));
    }
}

/**
 * Evaluates an echo statement.
 *
 * @param Node\Stmt\Echo_ $stmt
 * @param ReplSession $session
 * @return Validation<EvaluationError, EvaluationResult>
 */
function evaluateEchoStatement(Node\Stmt\Echo_ $stmt, ReplSession $session): Validation
{
    try {
        // Evaluate each expression and echo it
        $output = '';
        foreach ($stmt->exprs as $expr) {
            $result = evaluateNode($expr, $session);
            if ($result->isLeft()) {
                return $result;
            }
            $value = $result->getOrElse(null)->value;

            // Convert value to string for echoing
            if (is_string($value)) {
                $output .= $value;
            } elseif (is_bool($value)) {
                $output .= $value ? '1' : '';
            } elseif (is_null($value)) {
                // null outputs nothing
            } elseif (is_scalar($value)) {
                $output .= (string) $value;
            } elseif (is_array($value)) {
                $output .= 'Array';
            } elseif (is_object($value)) {
                if (method_exists($value, '__toString')) {
                    $output .= (string) $value;
                } else {
                    $output .= 'Object';
                }
            }
        }

        // Actually echo the output
        echo $output;

        // Return null as the result (echo doesn't return a value)
        // Mark as output statement so it doesn't get auto-assigned to a variable
        return Success(EvaluationResult::of(null, 'Null', null, [], true));
    } catch (\Throwable $e) {
        return Failure(new EvaluationError(
            'Echo',
            'Echo statement failed: ' . $e->getMessage()
        ));
    }
}

/**
 * Special exception used to signal early returns from function bodies.
 * This allows returns within control structures (if, while, etc.) to properly exit the function.
 */
class FunctionReturnException extends \Exception
{
    public function __construct(public mixed $value)
    {
        parent::__construct();
    }
}

/**
 * Result from evaluating a statement block with session tracking.
 */
class StmtBlockResult
{
    public function __construct(
        public readonly Validation $result,
        public readonly ReplSession $updatedSession
    ) {}
}

/**
 * Evaluates a block of statements, tracking variable changes and returning the updated session.
 * This is used by while/do-while loops to capture variable updates from the loop body.
 *
 * @param array $stmts
 * @param ReplSession $session
 * @return Validation<EvaluationError, StmtBlockResult>
 * @throws FunctionReturnException When a return statement is encountered
 */
function evaluateStmtBlockWithSession(array $stmts, ReplSession $session): Validation
{
    if (empty($stmts)) {
        return Success(new StmtBlockResult(
            Success(EvaluationResult::of(null, 'Null')),
            $session
        ));
    }

    $currentSession = $session;
    $lastResult = Success(EvaluationResult::of(null, 'Null'));

    foreach ($stmts as $stmt) {
        if ($stmt instanceof Node\Stmt\Expression) {
            $result = evaluateNode($stmt->expr, $currentSession);
            if ($result->isLeft()) {
                /** @var \Phunkie\Validation\Failure $result */
                return Failure($result->fold(fn($e) => $e)(fn($s) => $s));
            }
            $lastResult = $result;

            // Track variable assignments
            $evalResult = $result->getOrElse(null);
            if ($evalResult->assignedVariable !== null) {
                $currentVars = $currentSession->variables->plus(
                    $evalResult->assignedVariable,
                    $evalResult->value
                );
                $currentSession = new ReplSession(
                    $currentSession->history,
                    $currentVars,
                    $currentSession->colorEnabled,
                    $currentSession->variableCounter,
                    $currentSession->incompleteInput,
                    $currentSession->currentNamespace,
                    $currentSession->useStatements
                );
            }

            // Track additional assignments (from operations like $i++)
            foreach ($evalResult->additionalAssignments as $varName => $value) {
                $currentVars = $currentSession->variables->plus($varName, $value);
                $currentSession = new ReplSession(
                    $currentSession->history,
                    $currentVars,
                    $currentSession->colorEnabled,
                    $currentSession->variableCounter,
                    $currentSession->incompleteInput,
                    $currentSession->currentNamespace,
                    $currentSession->useStatements
                );
            }
        } elseif ($stmt instanceof Node\Stmt\Echo_) {
            $result = evaluateEchoStatement($stmt, $currentSession);
            if ($result->isLeft()) {
                /** @var \Phunkie\Validation\Failure $result */
                return Failure($result->fold(fn($e) => $e)(fn($s) => $s));
            }
            $lastResult = $result;
        } else {
            // For other statement types, use the regular evaluateStmtBlock
            // (this handles if statements, nested loops, returns, etc.)
            $result = evaluateStmtBlock([$stmt], $currentSession);
            if ($result->isLeft()) {
                /** @var \Phunkie\Validation\Failure $result */
                return Failure($result->fold(fn($e) => $e)(fn($s) => $s));
            }
            $lastResult = $result;
        }
    }

    return Success(new StmtBlockResult(
        $lastResult,
        $currentSession
    ));
}

/**
 * Evaluates a block of statements and returns the last expression value.
 * Throws FunctionReturnException when a return statement is encountered.
 *
 * @param array $stmts
 * @param ReplSession $session
 * @return Validation<EvaluationError, EvaluationResult>
 * @throws FunctionReturnException When a return statement is encountered
 */
function evaluateStmtBlock(array $stmts, ReplSession $session): Validation
{
    if (empty($stmts)) {
        return Success(EvaluationResult::of(null, 'Null'));
    }

    $lastResult = Success(EvaluationResult::of(null, 'Null'));

    foreach ($stmts as $stmt) {
        if ($stmt instanceof Node\Stmt\Return_) {
            // Handle return statement in blocks - throw exception to signal early return
            if ($stmt->expr !== null) {
                $result = evaluateNode($stmt->expr, $session);
                if ($result->isLeft()) {
                    $error = $result->fold(fn($e) => $e)(fn($r) => null);
                    throw new \RuntimeException('Return expression evaluation failed: ' . $error->reason);
                }
                throw new FunctionReturnException($result->getOrElse(null)->value);
            }
            throw new FunctionReturnException(null);
        } elseif ($stmt instanceof Node\Stmt\If_) {
            // Handle if statement in blocks - returns can throw FunctionReturnException
            $result = evaluateIfStatement($stmt, $session);
            if ($result->isLeft()) {
                return $result;
            }
            $lastResult = $result;
        } elseif ($stmt instanceof Node\Stmt\For_) {
            // Handle for loop in blocks
            $result = evaluateForLoop($stmt, $session);
            if ($result->isLeft()) {
                return $result;
            }
            $lastResult = $result;
        } elseif ($stmt instanceof Node\Stmt\While_) {
            // Handle while loop in blocks
            $result = evaluateWhileLoop($stmt, $session);
            if ($result->isLeft()) {
                return $result;
            }
            $lastResult = $result;
        } elseif ($stmt instanceof Node\Stmt\Do_) {
            // Handle do-while loop in blocks
            $result = evaluateDoWhileLoop($stmt, $session);
            if ($result->isLeft()) {
                return $result;
            }
            $lastResult = $result;
        } elseif ($stmt instanceof Node\Stmt\Foreach_) {
            // Handle foreach loop in blocks
            $result = evaluateForeachLoop($stmt, $session);
            if ($result->isLeft()) {
                return $result;
            }
            $lastResult = $result;
        } elseif ($stmt instanceof Node\Stmt\Echo_) {
            // Handle echo statement in blocks
            $result = evaluateEchoStatement($stmt, $session);
            if ($result->isLeft()) {
                return $result;
            }
            $lastResult = $result;
        } elseif ($stmt instanceof Node\Stmt\Expression) {
            $result = evaluateNode($stmt->expr, $session);
            if ($result->isLeft()) {
                return $result;
            }
            $lastResult = $result;
        } else {
            return Failure(new EvaluationError(
                get_class($stmt),
                'Unsupported statement type in block: ' . get_class($stmt)
            ));
        }
    }

    return $lastResult;
}

/**
 * Evaluates a binary operation (e.g., +, -, *, /, ., etc.).
 *
 * @param Expr\BinaryOp $node
 * @param ReplSession $session
 * @return Validation<EvaluationError, EvaluationResult>
 */
function evaluateBinaryOp(Expr\BinaryOp $node, ReplSession $session): Validation
{
    // Special handling for null coalescing to implement short-circuit evaluation
    if ($node instanceof Expr\BinaryOp\Coalesce) {
        $leftResult = evaluateNode($node->left, $session);
        if ($leftResult->isLeft()) {
            return $leftResult;
        }
        $left = $leftResult->getOrElse(null)->value;

        // Only evaluate right if left is null
        if ($left !== null) {
            return Success(EvaluationResult::of($left, getType($left)));
        }

        $rightResult = evaluateNode($node->right, $session);
        if ($rightResult->isLeft()) {
            return $rightResult;
        }
        $right = $rightResult->getOrElse(null)->value;

        return Success(EvaluationResult::of($right, getType($right)));
    }

    // Evaluate left and right operands for all other operations
    $leftResult = evaluateNode($node->left, $session);
    if ($leftResult->isLeft()) {
        return $leftResult;
    }
    $left = $leftResult->getOrElse(null)->value;

    $rightResult = evaluateNode($node->right, $session);
    if ($rightResult->isLeft()) {
        return $rightResult;
    }
    $right = $rightResult->getOrElse(null)->value;

    try {
        $result = match (true) {
            // Arithmetic operations
            $node instanceof Expr\BinaryOp\Plus => $left + $right,
            $node instanceof Expr\BinaryOp\Minus => $left - $right,
            $node instanceof Expr\BinaryOp\Mul => $left * $right,
            $node instanceof Expr\BinaryOp\Div => $left / $right,
            $node instanceof Expr\BinaryOp\Mod => $left % $right,
            $node instanceof Expr\BinaryOp\Pow => $left ** $right,

            // String concatenation
            $node instanceof Expr\BinaryOp\Concat => $left . $right,

            // Logical operations
            $node instanceof Expr\BinaryOp\BooleanAnd => $left && $right,
            $node instanceof Expr\BinaryOp\BooleanOr => $left || $right,
            $node instanceof Expr\BinaryOp\LogicalAnd => $left and $right,
            $node instanceof Expr\BinaryOp\LogicalOr => $left or $right,
            $node instanceof Expr\BinaryOp\LogicalXor => $left xor $right,

            // Comparison operations
            $node instanceof Expr\BinaryOp\Equal => $left == $right,
            $node instanceof Expr\BinaryOp\NotEqual => $left != $right,
            $node instanceof Expr\BinaryOp\Identical => $left === $right,
            $node instanceof Expr\BinaryOp\NotIdentical => $left !== $right,
            $node instanceof Expr\BinaryOp\Greater => $left > $right,
            $node instanceof Expr\BinaryOp\GreaterOrEqual => $left >= $right,
            $node instanceof Expr\BinaryOp\Smaller => $left < $right,
            $node instanceof Expr\BinaryOp\SmallerOrEqual => $left <= $right,
            $node instanceof Expr\BinaryOp\Spaceship => $left <=> $right,

            // Bitwise operations
            $node instanceof Expr\BinaryOp\BitwiseAnd => $left & $right,
            $node instanceof Expr\BinaryOp\BitwiseOr => $left | $right,
            $node instanceof Expr\BinaryOp\BitwiseXor => $left ^ $right,
            $node instanceof Expr\BinaryOp\ShiftLeft => $left << $right,
            $node instanceof Expr\BinaryOp\ShiftRight => $left >> $right,

            // Note: Coalesce is handled above with short-circuit evaluation

            default => throw new \RuntimeException('Unsupported binary operation: ' . get_class($node))
        };

        return Success(EvaluationResult::of($result, getType($result)));
    } catch (\Throwable $e) {
        return Failure(new EvaluationError(
            get_class($node),
            'Binary operation failed: ' . $e->getMessage()
        ));
    }
}

/**
 * Evaluates a unary operation.
 *
 * @param Expr\UnaryPlus|Expr\UnaryMinus|Expr\BooleanNot|Expr\BitwiseNot $node
 * @param ReplSession $session
 * @return Validation<EvaluationError, EvaluationResult>
 */
function evaluateUnaryOp(Expr $node, ReplSession $session): Validation
{
    // Evaluate the operand
    $exprResult = evaluateNode($node->expr, $session);
    if ($exprResult->isLeft()) {
        return $exprResult;
    }
    $value = $exprResult->getOrElse(null)->value;

    try {
        $result = match (get_class($node)) {
            Expr\UnaryPlus::class => +$value,
            Expr\UnaryMinus::class => -$value,
            Expr\BooleanNot::class => !$value,
            Expr\BitwiseNot::class => ~$value,

            default => throw new \RuntimeException('Unsupported unary operation: ' . get_class($node))
        };

        return Success(EvaluationResult::of($result, getType($result)));
    } catch (\Throwable $e) {
        return Failure(new EvaluationError(
            get_class($node),
            'Unary operation failed: ' . $e->getMessage()
        ));
    }
}

/**
 * Evaluates an array literal.
 *
 * @param Expr\Array_ $node
 * @param ReplSession $session
 * @return Validation<EvaluationError, EvaluationResult>
 */
function evaluateArray(Expr\Array_ $node, ReplSession $session): Validation
{
    try {
        $array = [];

        foreach ($node->items as $item) {
            // PHPStan says this is always ArrayItem
            $key = $item->key;
            // Evaluate the value
            $valueResult = evaluateNode($item->value, $session);
            if ($valueResult->isLeft()) {
                return $valueResult;
            }
            $value = $valueResult->getOrElse(null)->value;

            // Check if this is a spread operator (unpack flag)
            if ($item->unpack) {
                // Value must be an array or iterable
                if (!is_array($value) && !($value instanceof \Traversable)) {
                    return Failure(new EvaluationError(
                        get_class($node),
                        'Only arrays and Traversables can be unpacked'
                    ));
                }

                // Spread the array/iterable into the result
                foreach ($value as $k => $v) {
                    // Preserve string keys, but use numeric indices for numeric keys
                    if (is_string($k)) {
                        $array[$k] = $v;
                    } else {
                        $array[] = $v;
                    }
                }
            } else {
                // Handle key if present
                if ($item->key !== null) {
                    $keyResult = evaluateNode($item->key, $session);
                    if ($keyResult->isLeft()) {
                        return $keyResult;
                    }
                    $key = $keyResult->getOrElse(null)->value;
                    $array[$key] = $value;
                } else {
                    $array[] = $value;
                }
            }
        }

        return Success(EvaluationResult::of($array, 'Array'));
    } catch (\Throwable $e) {
        return Failure(new EvaluationError(
            get_class($node),
            'Array evaluation failed: ' . $e->getMessage()
        ));
    }
}

/**
 * Evaluates array access (e.g., $arr[0], $arr["key"]).
 *
 * @param Expr\ArrayDimFetch $node
 * @param ReplSession $session
 * @return Validation<EvaluationError, EvaluationResult>
 */
function evaluateArrayAccess(Expr\ArrayDimFetch $node, ReplSession $session): Validation
{
    try {
        // Evaluate the array variable
        $arrayResult = evaluateNode($node->var, $session);
        if ($arrayResult->isLeft()) {
            return $arrayResult;
        }

        $array = $arrayResult->getOrElse(null)->value;

        if (!is_array($array)) {
            return Failure(new EvaluationError(
                'ArrayAccess',
                'Cannot use array access on non-array type: ' . getType($array)
            ));
        }

        // Evaluate the dimension (index/key)
        if ($node->dim === null) {
            return Failure(new EvaluationError(
                'ArrayAccess',
                'Array access requires an index'
            ));
        }

        $dimResult = evaluateNode($node->dim, $session);
        if ($dimResult->isLeft()) {
            return $dimResult;
        }

        $index = $dimResult->getOrElse(null)->value;

        // Access the array element
        if (!array_key_exists($index, $array)) {
            return Failure(new EvaluationError(
                'ArrayAccess',
                'Undefined array index: ' . var_export($index, true)
            ));
        }

        $value = $array[$index];
        return Success(EvaluationResult::of($value, getType($value)));
    } catch (\Throwable $e) {
        return Failure(new EvaluationError(
            'ArrayAccess',
            'Array access failed: ' . $e->getMessage()
        ));
    }
}

/**
 * Evaluates an assignment expression.
 *
 * @param Expr\Assign $node
 * @param ReplSession $session
 * @return Validation<EvaluationError, EvaluationResult>
 */
function evaluateAssignment(Expr\Assign $node, ReplSession $session): Validation
{
    try {
        // Handle list() destructuring assignment
        if ($node->var instanceof Expr\List_) {
            return evaluateListAssignment($node, $session);
        }

        // Handle array element assignment
        if ($node->var instanceof Expr\ArrayDimFetch) {
            return evaluateArrayElementAssignment($node, $session);
        }

        // Handle property assignment ($obj->prop = value)
        if ($node->var instanceof Expr\PropertyFetch) {
            return evaluatePropertyAssignment($node, $session);
        }

        // Simple variable assignment
        if (!($node->var instanceof Expr\Variable)) {
            return Failure(new EvaluationError(
                get_class($node->var),
                'Unsupported assignment target: ' . get_class($node->var)
            ));
        }

        // Handle variable variables ($$var = value)
        if ($node->var->name instanceof Expr\Variable || $node->var->name instanceof Node) {
            // Evaluate the variable name
            return evaluateNode($node->var->name, $session)->flatMap(
                /** @param EvaluationResult $nameResult */
                function ($nameResult) use ($node, $session) {
                    $varName = $nameResult->value;

                    if (!is_string($varName)) {
                        return Failure(new EvaluationError('$$var', 'Variable variable name must be a string'));
                    }

                    // Evaluate the value to assign
                    return evaluateNode($node->expr, $session)->map(
                        /** @param EvaluationResult $valueResult */
                        function ($valueResult) use ($varName) {
                            $value = $valueResult->value;
                            return EvaluationResult::of($value, getType($value), '$' . $varName);
                        }
                    );
                }
            );
        }

        $varName = '$' . $node->var->name;

        // Evaluate the value to assign
        $valueResult = evaluateNode($node->expr, $session);
        if ($valueResult->isLeft()) {
            return $valueResult;
        }

        $value = $valueResult->getOrElse(null)->value;

        // Return the value with the assigned variable name so the REPL knows to use it
        $result = EvaluationResult::of($value, getType($value), $varName);

        return Success($result);
    } catch (\Throwable $e) {
        return Failure(new EvaluationError(
            'Assignment',
            'Assignment failed: ' . $e->getMessage()
        ));
    }
}

/**
 * Evaluates array element assignment (e.g., $arr[0] = 42).
 *
 * @param Expr\Assign $node
 * @param ReplSession $session
 * @return Validation<EvaluationError, EvaluationResult>
 */
function evaluateArrayElementAssignment(Expr\Assign $node, ReplSession $session): Validation
{
    try {
        if (!($node->var instanceof Expr\ArrayDimFetch)) {
            return Failure(new EvaluationError(
                'ArrayAssignment',
                'Expected array dimension fetch, got ' . get_class($node->var)
            ));
        }
        $arrayDimFetch = $node->var;

        // Get the base variable name
        if (!($arrayDimFetch->var instanceof Expr\Variable)) {
            return Failure(new EvaluationError(
                'ArrayAssignment',
                'Array element assignment only supports simple variables'
            ));
        }

        $varName = '$' . $arrayDimFetch->var->name;

        // Get the existing array
        $option = $session->variables->get($varName);
        if ($option->isEmpty()) {
            return Failure(new EvaluationError($varName, 'Variable not found'));
        }

        $array = $option->get();
        if (!is_array($array)) {
            return Failure(new EvaluationError(
                $varName,
                'Cannot use array access on non-array type: ' . getType($array)
            ));
        }

        // Evaluate the index
        if ($arrayDimFetch->dim === null) {
            return Failure(new EvaluationError(
                'ArrayAssignment',
                'Array assignment requires an index'
            ));
        }

        $dimResult = evaluateNode($arrayDimFetch->dim, $session);
        if ($dimResult->isLeft()) {
            return $dimResult;
        }
        $index = $dimResult->getOrElse(null)->value;

        // Evaluate the value to assign
        $valueResult = evaluateNode($node->expr, $session);
        if ($valueResult->isLeft()) {
            return $valueResult;
        }
        $value = $valueResult->getOrElse(null)->value;

        // Update the array
        $array[$index] = $value;

        // Return the updated array with the variable name so it gets stored
        return Success(EvaluationResult::of($array, 'Array', $varName));
    } catch (\Throwable $e) {
        return Failure(new EvaluationError(
            'ArrayAssignment',
            'Array element assignment failed: ' . $e->getMessage()
        ));
    }
}

/**
 * Evaluates property assignment (e.g., $obj->prop = 42).
 *
 * @param Expr\Assign $node
 * @param ReplSession $session
 * @return Validation<EvaluationError, EvaluationResult>
 */
function evaluatePropertyAssignment(Expr\Assign $node, ReplSession $session): Validation
{
    try {
        if (!($node->var instanceof Expr\PropertyFetch)) {
            return Failure(new EvaluationError(
                'PropertyAssignment',
                'Expected property fetch, got ' . get_class($node->var)
            ));
        }
        $propertyFetch = $node->var;

        // Navigate to the target object by evaluating the left side except the final property
        $obj = null;
        $propName = null;

        // Handle nested property fetches (e.g., $obj->nested->prop)
        if ($propertyFetch->var instanceof Expr\PropertyFetch) {
            // Evaluate the entire chain except the final property
            $result = evaluateNode($propertyFetch->var, $session);
            if ($result->isLeft()) {
                return $result;
            }
            $obj = $result->getOrElse(null)->value;
        } elseif ($propertyFetch->var instanceof Expr\Variable) {
            // Simple case: $obj->prop
            $varName = '$' . $propertyFetch->var->name;
            $option = $session->variables->get($varName);
            if ($option->isEmpty()) {
                return Failure(new EvaluationError($varName, 'Variable not found'));
            }
            $obj = $option->get();
        } else {
            return Failure(new EvaluationError(
                'PropertyAssignment',
                'Unsupported property assignment target'
            ));
        }

        if (!is_object($obj)) {
            return Failure(new EvaluationError(
                'PropertyAssignment',
                'Cannot access property on non-object type: ' . getType($obj)
            ));
        }

        // Get the property name
        if (!($propertyFetch->name instanceof Node\Identifier)) {
            return Failure(new EvaluationError(
                'PropertyAssignment',
                'Dynamic property names are not supported'
            ));
        }
        $propName = $propertyFetch->name->name;

        // Evaluate the value to assign
        $valueResult = evaluateNode($node->expr, $session);
        if ($valueResult->isLeft()) {
            return $valueResult;
        }
        $value = $valueResult->getOrElse(null)->value;

        // Set the property
        $obj->$propName = $value;

        // Return the assigned value without creating a new variable
        // Property assignments are side-effects and shouldn't create REPL variables
        return Success(EvaluationResult::of($value, getType($value), '__no_output__'));
    } catch (\Throwable $e) {
        return Failure(new EvaluationError(
            'PropertyAssignment',
            'Property assignment failed: ' . $e->getMessage()
        ));
    }
}

/**
 * Evaluates a namespace declaration.
 *
 * @param Node\Stmt\Namespace_ $stmt
 * @param ReplSession $session
 * @return Validation<EvaluationError, EvaluationResult>
 */
function evaluateNamespace(Node\Stmt\Namespace_ $stmt, ReplSession $session): Validation
{
    try {
        // Get the namespace name
        $namespaceName = $stmt->name ? $stmt->name->toString() : null;

        // Return a special result that signals the REPL to update the namespace
        // We use a special marker in the assignedVariable field
        return Success(new EvaluationResult(
            $namespaceName,
            'Namespace',
            '__namespace__'
        ));
    } catch (\Throwable $e) {
        return Failure(new EvaluationError(
            'Namespace',
            'Namespace declaration failed: ' . $e->getMessage()
        ));
    }
}

/**
 * Evaluates list() destructuring assignment (e.g., list($a, $b) = [1, 2]).
 *
 * @param Expr\Assign $node
 * @param ReplSession $session
 * @return Validation<EvaluationError, EvaluationResult>
 */
function evaluateListAssignment(Expr\Assign $node, ReplSession $session): Validation
{
    try {
        if ($node->var instanceof Expr\List_ || $node->var instanceof Expr\Array_) {
            $list = $node->var;
        } else {
            return Failure(new EvaluationError(
                'ListAssignment',
                'Expected list() or [...] on left-hand side, got ' . get_class($node->var)
            ));
        }

        // Evaluate the right-hand side (should be an array)
        $rhsResult = evaluateNode($node->expr, $session);
        if ($rhsResult->isLeft()) {
            return $rhsResult;
        }

        $array = $rhsResult->getOrElse(null)->value;
        if (!is_array($array)) {
            return Failure(new EvaluationError(
                'ListAssignment',
                'list() requires an array on the right-hand side'
            ));
        }

        // Collect all variable assignments
        $firstVarName = null;
        $firstValue = null;
        $additionalAssignments = [];

        foreach ($list->items as $i => $item) {
            if ($item === null) {
                continue; // Skip empty list items
            }

            if (!($item->value instanceof Expr\Variable)) {
                return Failure(new EvaluationError(
                    'ListAssignment',
                    'list() items must be variables'
                ));
            }

            $varName = '$' . $item->value->name;

            // Get value from array by index
            $value = $array[$i] ?? null;

            // Store the first variable for the return result
            if ($firstVarName === null) {
                $firstVarName = $varName;
                $firstValue = $value;
            } else {
                // Add subsequent variables to additional assignments
                $additionalAssignments[$varName] = $value;
            }
        }

        // Return the first assigned variable with additional assignments
        if ($firstVarName !== null) {
            return Success(EvaluationResult::of(
                $firstValue,
                getType($firstValue),
                $firstVarName,
                $additionalAssignments
            ));
        }

        return Success(EvaluationResult::of(null, 'Null'));
    } catch (\Throwable $e) {
        return Failure(new EvaluationError(
            'ListAssignment',
            'list() assignment failed: ' . $e->getMessage()
        ));
    }
}

/**
 * Validates if a value matches a single type specification.
 *
 * @param mixed $value The value to check
 * @param Node\Identifier|Node\Name $type The type to check against
 * @return bool True if the value matches the type
 */
function isTypeValid(mixed $value, Node\Identifier|Node\Name $type): bool
{
    $typeName = $type->toString();

    if ($type instanceof Node\Identifier) {
        // Scalar type (int, string, bool, float, array, callable, etc.)
        return match ($typeName) {
            'int' => is_int($value),
            'float' => is_float($value) || is_int($value),
            'string' => is_string($value),
            'bool' => is_bool($value),
            'array' => is_array($value),
            'callable' => is_callable($value),
            'object' => is_object($value),
            'mixed' => true,
            'null' => is_null($value),
            default => false
        };
    }

    // Class/interface type (this is a Name)
    return is_object($value) && is_a($value, $typeName);
}

/**
 * Validates if a value matches a union type (any of the types must match).
 *
 * @param mixed $value The value to check
 * @param Node\UnionType $unionType The union type specification
 * @return bool True if the value matches any of the types
 */
function isUnionTypeValid(mixed $value, Node\UnionType $unionType): bool
{
    // UnionType->types contains: Identifier | Name | IntersectionType
    foreach ($unionType->types as $type) {
        if ($type instanceof Node\IntersectionType) {
            // Union containing intersection (e.g., (A&B)|C)
            if (isIntersectionTypeValid($value, $type)) {
                return true;
            }
        } else {
            // Regular type (Identifier or Name)
            if (isTypeValid($value, $type)) {
                return true;
            }
        }
    }
    return false;
}

/**
 * Validates if a value matches an intersection type (all types must match).
 *
 * @param mixed $value The value to check
 * @param Node\IntersectionType $intersectionType The intersection type specification
 * @return bool True if the value matches all of the types
 */
function isIntersectionTypeValid(mixed $value, Node\IntersectionType $intersectionType): bool
{
    // IntersectionType->types contains: Identifier | Name only
    foreach ($intersectionType->types as $type) {
        // All types in intersection must be Identifier or Name
        if (!isTypeValid($value, $type)) {
            return false;
        }
    }
    return true;
}

/**
 * Validates if a value matches a complex type (including union and intersection types).
 *
 * @param mixed $value The value to check
 * @param Node\Identifier|Node\Name|Node\UnionType|Node\IntersectionType $type The type specification
 * @return bool True if the value matches the type
 */
function isComplexTypeValid(mixed $value, mixed $type): bool
{
    if ($type instanceof Node\UnionType) {
        return isUnionTypeValid($value, $type);
    } elseif ($type instanceof Node\IntersectionType) {
        return isIntersectionTypeValid($value, $type);
    } else {
        return isTypeValid($value, $type);
    }
}

/**
 * Gets a string representation of a type for error messages.
 *
 * @param Node\Identifier|Node\Name|Node\UnionType|Node\IntersectionType $type
 * @return string The type name as a string
 */
function getTypeName(mixed $type): string
{
    if ($type instanceof Node\UnionType) {
        $names = array_map(fn($t) => getTypeName($t), $type->types);
        return implode('|', $names);
    } elseif ($type instanceof Node\IntersectionType) {
        $names = array_map(fn($t) => getTypeName($t), $type->types);
        return implode('&', $names);
    } else {
        return $type->toString();
    }
}

/**
 * Checks if a type contains any class/interface types that need existence validation.
 * Returns all class names that need validation.
 *
 * @param mixed $type The type node to check
 * @return array<string> Array of class/interface names that need validation
 */
function getClassNamesForValidation(mixed $type): array
{
    if ($type instanceof Node\Name) {
        return [$type->toString()];
    } elseif ($type instanceof Node\UnionType) {
        // Check all types in the union
        $names = [];
        foreach ($type->types as $t) {
            $names = array_merge($names, getClassNamesForValidation($t));
        }
        return $names;
    } elseif ($type instanceof Node\IntersectionType) {
        // Check all types in the intersection
        $names = [];
        foreach ($type->types as $t) {
            $names = array_merge($names, getClassNamesForValidation($t));
        }
        return $names;
    }
    return [];
}

/**
 * Evaluates a use statement.
 *
 * @param Node\Stmt\Use_ $stmt
 * @param ReplSession $session
 * @return Validation<EvaluationError, EvaluationResult>
 */
function evaluateUseStatement(Node\Stmt\Use_ $stmt, ReplSession $session): Validation
{
    try {
        $imports = [];

        foreach ($stmt->uses as $use) {
            // Get the full name
            $fullName = $use->name->toString();

            // Get the alias (either explicit or last part of the name)
            if ($use->alias !== null) {
                $alias = $use->alias->toString();
            } else {
                $parts = explode('\\', $fullName);
                $alias = end($parts);
            }

            $imports[] = ['alias' => $alias, 'fullName' => $fullName];
        }

        // Return a special result that signals the REPL to update use statements
        // We use a special marker in the assignedVariable field
        return Success(new EvaluationResult(
            $imports,
            'Use',
            '__use__'
        ));
    } catch (\Throwable $e) {
        return Failure(new EvaluationError(
            'Use',
            'Use statement failed: ' . $e->getMessage()
        ));
    }
}

/**
 * Evaluates a function definition.
 *
 * @param Node\Stmt\Function_ $node
 * @param ReplSession $session
 * @return Validation<EvaluationError, EvaluationResult>
 */
function evaluateFunctionDefinition(Node\Stmt\Function_ $node, ReplSession $session): Validation
{
    try {
        $funcName = $node->name->toString();

        // Validate parameter type hints
        foreach ($node->params as $param) {
            if ($param->type !== null) {
                // Check if any types in the type hint need existence validation
                $classNames = getClassNamesForValidation($param->type);
                foreach ($classNames as $typeName) {
                    // Check if the type is a class/interface that exists
                    if (!class_exists($typeName) && !interface_exists($typeName) && !in_array($typeName, ['self', 'parent', 'static'])) {
                        // It's a class type hint but class doesn't exist
                        return Failure(new EvaluationError(
                            $funcName,
                            "Class '$typeName' not found"
                        ));
                    }
                }
            }
        }

        // Validate return type hint
        if ($node->returnType !== null) {
            $classNames = getClassNamesForValidation($node->returnType);
            foreach ($classNames as $typeName) {
                // Check if the type is a class/interface that exists
                if (!class_exists($typeName) && !interface_exists($typeName) && !in_array($typeName, ['self', 'parent', 'static'])) {
                    // It's a class type hint but class doesn't exist
                    return Failure(new EvaluationError(
                        $funcName,
                        "Class '$typeName' not found"
                    ));
                }
            }
        }

        // Check if the function body contains yield expressions
        $hasYield = false;
        foreach ($node->stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\Expression && $stmt->expr instanceof Expr\Yield_) {
                $hasYield = true;
                break;
            }
        }

        // Create the function
        if ($hasYield) {
            // For generator functions, we need to create a function that returns a Generator
            $func = function (...$args) use ($node, $session, $funcName) {
                // Validate argument count and types before executing
                foreach ($node->params as $i => $param) {
                    // Check if argument is missing for required parameter
                    if ($i >= count($args)) {
                        // Check if this parameter has a default value
                        if ($param->default === null) {
                            // Required parameter is missing
                            $expectedCount = count(array_filter($node->params, fn($p) => $p->default === null));
                            throw new \TypeError(
                                "$funcName() expects at least $expectedCount argument" . ($expectedCount === 1 ? '' : 's') . ", " . count($args) . " given"
                            );
                        }
                        // Has default value, skip type checking
                        continue;
                    }

                    if ($param->type !== null) {
                        $argValue = $args[$i];

                        // Check type compatibility using the complex type validator
                        $isValid = isComplexTypeValid($argValue, $param->type);

                        if (!$isValid) {
                            $typeName = getTypeName($param->type);
                            $actualType = is_object($argValue) ? get_class($argValue) : get_debug_type($argValue);
                            throw new \TypeError(
                                "$funcName(): Argument #" . ($i + 1) . " (\$" . $param->var->name . ") must be of type $typeName, $actualType given"
                            );
                        }
                    }
                }

                // Create a new session with the function parameters bound
                $newVars = $session->variables;
                foreach ($node->params as $i => $param) {
                    $paramName = '$' . $param->var->name;

                    // Handle variadic parameters (e.g., ...$args)
                    if ($param->variadic) {
                        // Collect all remaining arguments into an array
                        $variadicArgs = array_slice($args, $i);
                        $newVars = $newVars->plus($paramName, $variadicArgs);
                        break; // Variadic parameter must be last
                    }

                    // Use argument if provided, otherwise evaluate default value
                    if ($i < count($args)) {
                        $newVars = $newVars->plus($paramName, $args[$i]);
                    } elseif ($param->default !== null) {
                        // Evaluate the default value expression
                        $defaultResult = evaluateNode($param->default, $session);
                        if ($defaultResult->isLeft()) {
                            $error = $defaultResult->fold(fn($e) => $e)(fn($r) => null);
                            throw new \RuntimeException('Default parameter evaluation failed: ' . $error->reason);
                        }
                        $defaultValue = $defaultResult->getOrElse(null)->value;
                        $newVars = $newVars->plus($paramName, $defaultValue);
                    } else {
                        $newVars = $newVars->plus($paramName, null);
                    }
                }

                $newSession = new ReplSession(
                    $session->history,
                    $newVars,
                    $session->colorEnabled,
                    $session->variableCounter
                );

                // Execute the function body as a generator
                foreach ($node->stmts as $stmt) {
                    if ($stmt instanceof Node\Stmt\Expression && $stmt->expr instanceof Expr\Yield_) {
                        // Evaluate the yield value
                        $yieldResult = evaluateNode($stmt->expr->value, $newSession);
                        if ($yieldResult->isLeft()) {
                            $error = $yieldResult->fold(fn($e) => $e)(fn($r) => null);
                            throw new \RuntimeException('Yield evaluation failed: ' . $error->reason);
                        }

                        $value = $yieldResult->getOrElse(null)->value;

                        // If there's a key, evaluate it
                        if ($stmt->expr->key !== null) {
                            $keyResult = evaluateNode($stmt->expr->key, $newSession);
                            if ($keyResult->isLeft()) {
                                $error = $keyResult->fold(fn($e) => $e)(fn($r) => null);
                                throw new \RuntimeException('Yield key evaluation failed: ' . $error->reason);
                            }
                            yield $keyResult->getOrElse(null)->value => $value;
                        } else {
                            yield $value;
                        }
                    } elseif ($stmt instanceof Node\Stmt\Expression) {
                        // Handle expression statements in the function body
                        $result = evaluateNode($stmt->expr, $newSession);
                        if ($result->isLeft()) {
                            $error = $result->fold(fn($e) => $e)(fn($r) => null);
                            throw new \RuntimeException('Statement evaluation failed: ' . $error->reason);
                        }
                    }
                    // Other statement types (Return, etc.) can be ignored here
                }
            };
        } else {
            // For regular functions
            $func = function (...$args) use ($node, $session, $funcName) {
                // Validate argument count and types before executing
                foreach ($node->params as $i => $param) {
                    // Check if argument is missing for required parameter
                    if ($i >= count($args)) {
                        // Check if this parameter has a default value
                        if ($param->default === null) {
                            // Required parameter is missing
                            $expectedCount = count(array_filter($node->params, fn($p) => $p->default === null));
                            throw new \TypeError(
                                "$funcName() expects at least $expectedCount argument" . ($expectedCount === 1 ? '' : 's') . ", " . count($args) . " given"
                            );
                        }
                        // Has default value, skip type checking
                        continue;
                    }

                    if ($param->type !== null) {
                        $argValue = $args[$i];

                        // Check type compatibility using the complex type validator
                        $isValid = isComplexTypeValid($argValue, $param->type);

                        if (!$isValid) {
                            $typeName = getTypeName($param->type);
                            $actualType = is_object($argValue) ? get_class($argValue) : get_debug_type($argValue);
                            throw new \TypeError(
                                "$funcName(): Argument #" . ($i + 1) . " (\$" . $param->var->name . ") must be of type $typeName, $actualType given"
                            );
                        }
                    }
                }

                // Create a new session with the function parameters bound
                $newVars = $session->variables;
                foreach ($node->params as $i => $param) {
                    $paramName = '$' . $param->var->name;

                    // Handle variadic parameters (e.g., ...$args)
                    if ($param->variadic) {
                        // Collect all remaining arguments into an array
                        $variadicArgs = array_slice($args, $i);
                        $newVars = $newVars->plus($paramName, $variadicArgs);
                        break; // Variadic parameter must be last
                    }

                    // Use argument if provided, otherwise evaluate default value
                    if ($i < count($args)) {
                        $newVars = $newVars->plus($paramName, $args[$i]);
                    } elseif ($param->default !== null) {
                        // Evaluate the default value expression
                        $defaultResult = evaluateNode($param->default, $session);
                        if ($defaultResult->isLeft()) {
                            $error = $defaultResult->fold(fn($e) => $e)(fn($r) => null);
                            throw new \RuntimeException('Default parameter evaluation failed: ' . $error->reason);
                        }
                        $defaultValue = $defaultResult->getOrElse(null)->value;
                        $newVars = $newVars->plus($paramName, $defaultValue);
                    } else {
                        $newVars = $newVars->plus($paramName, null);
                    }
                }

                $newSession = new ReplSession(
                    $session->history,
                    $newVars,
                    $session->colorEnabled,
                    $session->variableCounter
                );

                // Execute the function body - use evaluateStmtBlock which handles all statement types
                // evaluateStmtBlock throws FunctionReturnException when a return is encountered
                try {
                    $stmtsToEvaluate = $node->stmts;
                    $result = evaluateStmtBlock($stmtsToEvaluate, $newSession);

                    if ($result->isLeft()) {
                        $error = $result->fold(fn($e) => $e)(fn($r) => null);
                        throw new \RuntimeException('Function body evaluation failed: ' . $error->reason);
                    }

                    // If we get here, no explicit return was encountered, return the last expression value
                    $evalResult = $result->getOrElse(null);
                    return $evalResult ? $evalResult->value : null;
                } catch (FunctionReturnException $e) {
                    // A return statement was encountered, return its value
                    return $e->value;
                }
            };
        }

        // Store the function in the global scope
        // We'll use a special variable name for functions
        $varName = '$' . $funcName;

        // Store function metadata to support named arguments
        // We store the parameter information so that named arguments can be reordered
        $metadata = ['params' => $node->params];
        $additionalAssignments = ['$__meta__' . $funcName => $metadata];

        return Success(EvaluationResult::of($func, 'Function', $varName, $additionalAssignments));
    } catch (\Throwable $e) {
        return Failure(new EvaluationError(
            'FunctionDefinition',
            'Function definition failed: ' . $e->getMessage()
        ));
    }
}

/**
 * Evaluates a yield expression.
 *
 * @param Expr\Yield_ $node
 * @param ReplSession $session
 * @return Validation<EvaluationError, EvaluationResult>
 */
function evaluateYield(Expr\Yield_ $node, ReplSession $session): Validation
{
    // Yield expressions should only be evaluated inside a generator function context
    // When encountered here, we'll just evaluate the value being yielded
    // The actual yielding happens in the generator function execution
    try {
        if ($node->value !== null) {
            $valueResult = evaluateNode($node->value, $session);
            if ($valueResult->isLeft()) {
                return $valueResult;
            }
            return $valueResult;
        }

        return Success(EvaluationResult::of(null, 'Null'));
    } catch (\Throwable $e) {
        return Failure(new EvaluationError(
            'Yield',
            'Yield evaluation failed: ' . $e->getMessage()
        ));
    }
}

/**
 * Evaluates object instantiation (new ClassName()).
 *
 * @param Expr\New_ $node
 * @param ReplSession $session
 * @return Validation<EvaluationError, EvaluationResult>
 */
function evaluateNew(Expr\New_ $node, ReplSession $session): Validation
{
    try {
        // Handle anonymous class
        if ($node->class instanceof Node\Stmt\Class_) {
            return evaluateAnonymousClass($node, $session);
        }

        // Get the class name
        if (!($node->class instanceof Node\Name)) {
            return Failure(new EvaluationError(
                'New',
                'Class name must be a Name node, got: ' . get_class($node->class)
            ));
        }

        $className = $node->class->toString();

        // Resolve the class name using namespace and use statements
        $resolvedClassName = resolveName($className, $session);

        // Check if class exists
        if (!class_exists($resolvedClassName)) {
            return Failure(new EvaluationError(
                $className,
                "Class not found: $className (resolved to: $resolvedClassName)"
            ));
        }

        // Evaluate constructor arguments
        $args = [];
        foreach ($node->args as $arg) {
            $result = evaluateNode($arg->value, $session);
            if ($result->isLeft()) {
                return $result;
            }
            $value = $result->getOrElse(null)->value;

            // Check if this is a spread operator (unpack flag)
            if ($arg->unpack) {
                // Value must be an array or iterable
                if (!is_array($value) && !($value instanceof \Traversable)) {
                    return Failure(new EvaluationError(
                        get_class($node),
                        'Only arrays and Traversables can be unpacked'
                    ));
                }

                // Spread the array/iterable into the arguments
                foreach ($value as $v) {
                    $args[] = $v;
                }
            } else {
                $args[] = $value;
            }
        }

        // Instantiate the class
        $instance = new $resolvedClassName(...$args);

        return Success(EvaluationResult::of($instance, getType($instance)));
    } catch (\Throwable $e) {
        return Failure(new EvaluationError(
            'New',
            'Object instantiation failed: ' . $e->getMessage()
        ));
    }
}

/**
 * Creates an AST node from a PHP value.
 *
 * @param mixed $value
 * @return Node\Expr
 */
function createValueNode(mixed $value): Node\Expr
{
    return match (true) {
        is_null($value) => new Expr\ConstFetch(new Node\Name('null')),
        is_bool($value) => new Expr\ConstFetch(new Node\Name($value ? 'true' : 'false')),
        is_int($value) => new Scalar\LNumber($value),
        is_float($value) => new Scalar\DNumber($value),
        is_string($value) => new Scalar\String_($value),
        is_array($value) => createArrayNode($value),
        is_object($value) => createObjectPlaceholder($value),
        default => new Expr\ConstFetch(new Node\Name('null'))
    };
}

/**
 * Creates an array AST node from a PHP array.
 *
 * @param array $array
 * @return Expr\Array_
 */
function createArrayNode(array $array): Expr\Array_
{
    $items = [];
    foreach ($array as $key => $value) {
        $items[] = new Expr\ArrayItem(
            createValueNode($value),
            is_int($key) ? null : new Scalar\String_($key)
        );
    }
    return new Expr\Array_($items);
}

/**
 * Creates a placeholder for an object value.
 * We'll use a variable name that will be injected into the eval context.
 *
 * @param object $obj
 * @return Node\Expr
 */
function createObjectPlaceholder(object $obj): Node\Expr
{
    // Generate a unique variable name for this object
    static $objectCounter = 0;
    $varName = '__obj_' . $objectCounter++;

    // Store the object in a global array that eval can access
    $GLOBALS['__repl_objects'][$varName] = $obj;

    // Return an expression to access this specific object
    return new Expr\ArrayDimFetch(
        new Expr\ArrayDimFetch(
            new Expr\Variable('GLOBALS'),
            new Scalar\String_('__repl_objects')
        ),
        new Scalar\String_($varName)
    );
}

/**
 * Evaluates an anonymous class instantiation (new class { ... }).
 *
 * @param Expr\New_ $node
 * @param ReplSession $session
 * @return Validation<EvaluationError, EvaluationResult>
 */
function evaluateAnonymousClass(Expr\New_ $node, ReplSession $session): Validation
{
    try {
        $classNode = $node->class;

        // Validate parent class if any
        if ($classNode->extends !== null) {
            $parentClass = $classNode->extends->toString();
            $resolvedParent = resolveName($parentClass, $session);

            if (!class_exists($resolvedParent)) {
                return Failure(new EvaluationError(
                    'AnonymousClass',
                    "Cannot extend non-existent class: $parentClass"
                ));
            }

            $reflection = new \ReflectionClass($resolvedParent);
            if ($reflection->isFinal()) {
                return Failure(new EvaluationError(
                    'AnonymousClass',
                    "Cannot extend final class $parentClass"
                ));
            }
        }

        // Validate interfaces
        foreach ($classNode->implements as $interface) {
            $interfaceName = $interface->toString();
            $resolvedInterface = resolveName($interfaceName, $session);

            if (!interface_exists($resolvedInterface)) {
                return Failure(new EvaluationError(
                    'AnonymousClass',
                    "Cannot implement non-existent interface: $interfaceName"
                ));
            }
        }

        // Evaluate constructor arguments
        $args = [];
        foreach ($node->args as $arg) {
            $result = evaluateNode($arg->value, $session);
            if ($result->isLeft()) {
                return $result;
            }
            $args[] = $result->getOrElse(null)->value;
        }

        // Convert the anonymous class AST to PHP code and eval it
        $printer = new \PhpParser\PrettyPrinter\Standard();

        // We need to replace the arguments with evaluated values
        // Create scalar nodes for the evaluated arguments
        $evaluatedArgNodes = [];
        foreach ($args as $argValue) {
            // Create a node that represents the evaluated value
            $evaluatedArgNodes[] = new Node\Arg(createValueNode($argValue));
        }

        // Create a new expression with the anonymous class and evaluated arguments
        $newExpr = new Expr\New_($classNode, $evaluatedArgNodes);
        $code = $printer->prettyPrintExpr($newExpr);

        // Set up error handler
        $errorMessage = null;
        set_error_handler(function ($severity, $message, $file, $line) use (&$errorMessage) {
            $errorMessage = $message;
            return true;
        });

        try {
            // Eval the expression to create the anonymous class instance
            $instance = @eval("return $code;");
        } finally {
            restore_error_handler();
        }

        if ($errorMessage !== null) {
            return Failure(new EvaluationError(
                'AnonymousClass',
                $errorMessage
            ));
        }

        if ($instance === false || $instance === null) {
            return Failure(new EvaluationError(
                'AnonymousClass',
                'Failed to instantiate anonymous class'
            ));
        }

        return Success(EvaluationResult::of($instance, getType($instance)));
    } catch (\Throwable $e) {
        return Failure(new EvaluationError(
            'AnonymousClass',
            'Anonymous class instantiation failed: ' . $e->getMessage()
        ));
    }
}

/**
 * Evaluates a class definition.
 *
 * This function converts the class AST back to PHP code and evaluates it,
 * which effectively defines the class in the current runtime. The PHP-Parser
 * library automatically preserves attributes (via attrGroups property) when
 * parsing and the pretty printer outputs them correctly.
 *
 * @param Node\Stmt\Interface_ $interfaceNode
 * @param ReplSession $session
 * @return Validation<EvaluationError, EvaluationResult>
 */
function evaluateInterfaceDefinition(Node\Stmt\Interface_ $interfaceNode, ReplSession $session): Validation
{
    try {
        // Get the interface name
        $interfaceName = $interfaceNode->name?->toString();

        if ($interfaceName === null) {
            return Failure(new EvaluationError(
                'Interface',
                'Interface name must be a string'
            ));
        }

        // Check if interface already exists
        if ($session->isEntityDefined($interfaceName, 'interface')) {
            return Failure(new EvaluationError(
                $interfaceName,
                "Interface $interfaceName already exists"
            ));
        }

        // Validate parent interfaces
        foreach ($interfaceNode->extends as $parent) {
            $parentName = $parent->toString();
            $resolvedParent = resolveName($parentName, $session);

            if (!interface_exists($resolvedParent)) {
                return Failure(new EvaluationError(
                    $interfaceName,
                    "Cannot extend non-existent interface: $parentName"
                ));
            }
        }

        // Convert the interface AST to PHP code and eval it
        $printer = new \PhpParser\PrettyPrinter\Standard();
        $code = $printer->prettyPrint([$interfaceNode]);

        // Add namespace context if needed
        if ($session->currentNamespace !== null) {
            $namespace = $session->currentNamespace;
            $code = "namespace $namespace;\n" . $code;
        }

        // Set up error handler
        $errorMessage = null;
        set_error_handler(function ($severity, $message, $file, $line) use (&$errorMessage) {
            $errorMessage = $message;
            return true;
        });

        try {
            @eval($code);
        } finally {
            restore_error_handler();
        }

        if ($errorMessage !== null) {
            return Failure(new EvaluationError(
                $interfaceName,
                $errorMessage
            ));
        }

        // Verify interface was created
        if (!$session->isEntityDefined($interfaceName, 'interface', 1)) {
            return Failure(new EvaluationError(
                $interfaceName,
                'Failed to define interface'
            ));
        }

        // Return success with interface name but don't create a variable
        return Success(EvaluationResult::of(
            null,
            'Interface',
            '__no_output__',  // Don't create a numbered variable
            ["interface $interfaceName" => 'defined']
        ));
    } catch (\Throwable $e) {
        return Failure(new EvaluationError(
            'Interface',
            'Interface definition failed: ' . $e->getMessage()
        ));
    }
}

/**
 * Evaluates a trait definition.
 *
 * @param Node\Stmt\Trait_ $traitNode
 * @param ReplSession $session
 * @return Validation<EvaluationError, EvaluationResult>
 */
function evaluateTraitDefinition(Node\Stmt\Trait_ $traitNode, ReplSession $session): Validation
{
    try {
        // Get the trait name
        $traitName = $traitNode->name?->toString();

        if ($traitName === null) {
            return Failure(new EvaluationError(
                'Trait',
                'Trait must have a name'
            ));
        }

        // Check if trait already exists
        if (trait_exists($traitName, false)) {
            return Failure(new EvaluationError(
                $traitName,
                "Trait $traitName already exists"
            ));
        }

        // Convert the trait AST to PHP code and eval it
        $printer = new \PhpParser\PrettyPrinter\Standard();
        $code = $printer->prettyPrint([$traitNode]);

        // Add namespace context if needed
        if ($session->currentNamespace !== null) {
            $namespace = $session->currentNamespace;
            $code = "namespace $namespace;\n" . $code;
        }

        // Set up error handler
        $errorMessage = null;
        set_error_handler(function ($severity, $message, $file, $line) use (&$errorMessage) {
            $errorMessage = $message;
            return true;
        });

        try {
            @eval($code);
        } finally {
            restore_error_handler();
        }

        if ($errorMessage !== null) {
            return Failure(new EvaluationError(
                $traitName,
                $errorMessage
            ));
        }

        // Verify trait was created
        if (!$session->isEntityDefined($traitName, 'trait', 1)) {
            return Failure(new EvaluationError(
                $traitName,
                'Failed to define trait'
            ));
        }

        // Return success with trait name but don't create a variable
        return Success(EvaluationResult::of(
            null,
            'Trait',
            '__no_output__',  // Don't create a numbered variable
            ["trait $traitName" => 'defined']
        ));
    } catch (\Throwable $e) {
        return Failure(new EvaluationError(
            'Trait',
            'Trait definition failed: ' . $e->getMessage()
        ));
    }
}

/**
 * Evaluates a class definition.
 *
 * @param Node\Stmt\Class_ $classNode
 * @param ReplSession $session
 * @return Validation<EvaluationError, EvaluationResult>
 */
function evaluateClassDefinition(Node\Stmt\Class_ $classNode, ReplSession $session): Validation
{
    try {
        // Get the class name (required for non-anonymous classes)
        $className = $classNode->name?->toString();

        if ($className === null) {
            return Failure(new EvaluationError(
                'Class',
                'Anonymous classes can only be instantiated with new class { ... }, not as standalone definitions'
            ));
        }

        // Check if class already exists
        if (class_exists($className, false)) {
            return Failure(new EvaluationError(
                $className,
                "Class '$className' is already defined"
            ));
        }

        // Use the pretty printer to convert the AST back to PHP code
        // The pretty printer automatically handles attributes from the attrGroups property
        $prettyPrinter = new \PhpParser\PrettyPrinter\Standard();
        $classCode = $prettyPrinter->prettyPrint([$classNode]);

        // Validate the class definition without eval to catch potential errors
        // Check if parent class exists and is not final
        if ($classNode->extends !== null) {
            $parentClass = $classNode->extends->toString();
            if (!class_exists($parentClass)) {
                return Failure(new EvaluationError(
                    $className,
                    "Cannot extend non-existent class: $parentClass"
                ));
            }

            $reflection = new \ReflectionClass($parentClass);
            if ($reflection->isFinal()) {
                return Failure(new EvaluationError(
                    $className,
                    "Class $className cannot extend final class $parentClass"
                ));
            }
        }

        // Check if interfaces exist
        foreach ($classNode->implements as $interface) {
            $interfaceName = $interface->toString();
            if (!interface_exists($interfaceName)) {
                return Failure(new EvaluationError(
                    $className,
                    "Cannot implement non-existent interface: $interfaceName"
                ));
            }
        }

        // Set up error handler to catch warnings and notices from eval()
        $errorMessage = null;
        set_error_handler(function ($severity, $message, $file, $line) use (&$errorMessage) {
            $errorMessage = $message;
            return true; // Don't execute PHP's internal error handler
        });

        try {
            // Evaluate the class definition to define it in the runtime
            @eval($classCode); // @ suppresses fatal error output
        } finally {
            restore_error_handler();
        }

        // Check if an error occurred during eval
        if ($errorMessage !== null) {
            return Failure(new EvaluationError(
                $className,
                $errorMessage
            ));
        }

        // Verify the class was defined
        if (!$session->isEntityDefined($className, 'class', 1)) {
            return Failure(new EvaluationError(
                $className,
                "Failed to define class '$className'"
            ));
        }

        // Return success with the class name
        // Use the class name as assignedVariable so the REPL displays "// class X defined"
        return Success(EvaluationResult::of(
            $className,
            "Class",
            $className
        ));
    } catch (\Throwable $e) {
        return Failure(new EvaluationError(
            'Class',
            'Class definition failed: ' . $e->getMessage()
        ));
    }
}

/**
 * Cleans error messages by removing internal implementation details.
 *
 * Removes references to:
 * - eval()'d code paths
 * - ReplLoop.php references
 * - Line numbers from internal files
 *
 * @param string $message The original error message
 * @return string The cleaned error message
 */
function cleanErrorMessage(string $message): string
{
    // Remove references to eval()'d code with line numbers
    // Example: "in /path/to/ReplLoop.php(470) : eval()'d code on line 4"
    $message = preg_replace(
        '/\s+in\s+[^\s]+ReplLoop\.php\(\d+\)\s*:\s*eval\(\)\'d code on line \d+/',
        '',
        $message
    );

    // Remove "and exactly N expected" part to shorten the message
    $message = preg_replace('/\s+and exactly \d+ expected$/', '', $message);

    // Remove any remaining eval()'d code references
    $message = preg_replace('/\s+in\s+[^\s]+:\s*eval\(\)\'d code[^\s]*/', '', $message);

    // Clean up any double spaces that might result
    $message = preg_replace('/\s+/', ' ', $message);

    return trim($message);
}

/**
 * Checks if a class, interface, or trait exists at runtime.
 * This helper isolates dynamic existence checks to prevent PHPStan from
 * inferring "always false" based on static codebase analysis.
 *
 * @param string $name
 * @param string $kind 'class', 'interface', or 'trait'
 * @return bool
 */

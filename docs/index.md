# Phunkie Console Documentation

Welcome to the Phunkie Console documentation. This guide will help you understand and make the most of the interactive REPL for functional programming in PHP.

## Table of Contents

1. [Introduction](#introduction)
2. [Installation](#installation)
3. [Getting Started](#getting-started)
4. [REPL Commands](#repl-commands)
5. [Working with Types](#working-with-types)
6. [Session Management](#session-management)
7. [Advanced Features](#advanced-features)
8. [Examples](#examples)
9. [Architecture](#architecture)
10. [Troubleshooting](#troubleshooting)

## Introduction

Phunkie Console is an interactive REPL (Read-Eval-Print Loop) that provides a powerful environment for exploring functional programming concepts in PHP. It's built on top of the [Phunkie](https://github.com/phunkie/phunkie) library and offers features like type inspection, session persistence, and syntax highlighting.

### Key Features

- **Interactive Evaluation**: Type expressions and see results immediately
- **Type System Integration**: Query types and kinds of expressions
- **Persistent Session**: Variables and imports persist throughout your session
- **Multi-line Support**: Write complex functions and expressions spanning multiple lines
- **Import System**: Bring functions and classes into your REPL session
- **Rich Output Formatting**: Beautiful display of complex data structures
- **Syntax Highlighting**: Color-coded output for improved readability

## Installation

### Via Composer (Project)

```bash
composer require phunkie/console
```

### Via Composer (Global)

```bash
composer global require phunkie/console
```

### System Requirements

- PHP 8.2, 8.3, or 8.4
- Composer
- readline extension (usually included with PHP)

## Getting Started

### Starting the REPL

Launch the REPL from your project directory:

```bash
php vendor/bin/phunkie
```

Or if installed globally:

```bash
phunkie
```

### Enable Color Support

For syntax highlighting and colored output:

```bash
php vendor/bin/phunkie -c
```

### Your First Expression

```php
phunkie > 2 + 2
$var0: Int = 4
```

The REPL evaluates your expression, assigns it to a variable (`$var0`), displays its type (`Int`), and shows the result (`4`).

### Using Previous Results

Variables are automatically created and can be referenced:

```php
phunkie > 10 * 5
$var0: Int = 50

phunkie > $var0 + 25
$var1: Int = 75
```

## REPL Commands

Commands start with a colon (`:`) and provide meta-functionality for the REPL.

### `:quit` or `:exit`

Exit the REPL gracefully:

```php
phunkie > :quit
bye \o
```

### `:type <expression>`

Display the type of an expression without evaluating side effects:

```php
phunkie > :type ImmList(1, 2, 3)
ImmList

phunkie > :type Some(42)
Option

phunkie > :type "hello"
String
```

### `:kind <expression>`

Show the kind (type of a type) of a type constructor:

```php
phunkie > :kind Option
* -> *

phunkie > :kind ImmList
* -> *

phunkie > :kind Pair
* -> * -> *
```

Kinds explanation:
- `*` - A concrete type (like `Int`, `String`)
- `* -> *` - A type constructor taking one type parameter (like `Option<T>`)
- `* -> * -> *` - A type constructor taking two type parameters (like `Pair<A, B>`)

### `:import <function>`

Import a function into the current REPL session:

```php
phunkie > :import monad/flatten
imported function Phunkie\Functions\monad\flatten()

phunkie > flatten(Some(Some(42)))
$var0: Option<Int> = Some(42)

```

### `:load <filepath>`

Load a PHP file silently into the current session. All functions, classes, and variables defined in the file become available in the REPL. The file is executed without displaying any output.

```php
phunkie > :load helpers.php
// file helpers.php loaded

phunkie > // All functions and classes from helpers.php are now available
phunkie > myHelperFunction()
$var0: String = "Result from helper"
```

**Notes:**
- The file must have a `.php` or `.phunkie` extension
- The file path can be relative or absolute
- Any output (echo, print, etc.) in the file is suppressed
- All definitions (functions, classes, variables) persist in the session
- If the file contains errors, an error message will be displayed

### `:clear`

Clear the terminal screen (keeps session state):

```php
phunkie > :clear
```

### `:help`

Display help information:

```php
phunkie > :help
```

## Working with Types

### Immutable Lists

```php
phunkie > $list = ImmList(1, 2, 3, 4, 5)
$var0: ImmList = List(1, 2, 3, 4, 5)

phunkie > $list->head()
$var1: Int = 1

phunkie > $list->tail()
$var2: ImmList = List(2, 3, 4, 5)

phunkie > $list->map(fn($x) => $x * 2)
$var3: ImmList = List(2, 4, 6, 8, 10)

phunkie > $list->filter(fn($x) => $x % 2 === 0)
$var4: ImmList = List(2, 4)

phunkie > $list->reduce(fn($acc, $x) => $acc + $x, 0)
$var5: Int = 15
```

### Option Type

The Option type represents optional values (Some or None):

```php
phunkie > Some(42)
$var0: Option = Some(42)

phunkie > None()
$var1: Option = None

phunkie > Some(42)->map(fn($x) => $x * 2)
$var2: Option = Some(84)

phunkie > None()->map(fn($x) => $x * 2)
$var3: Option = None

phunkie > Some(42)->getOrElse(0)
$var4: Int = 42

phunkie > None()->getOrElse(0)
$var5: Int = 0

phunkie > Some(10)->flatMap(fn($x) => Some($x * 2))
$var6: Option = Some(20)
```

### Validation Type

Validation represents a value of one of two possible types (Failure or Success):

```php
phunkie > Success(42)
$var0: Validation = Success(42)

phunkie > Failure("error")
$var1: Validation = Failure("error")

phunkie > Success(42)->map(fn($x) => $x * 2)
$var2: Validation = Success(84)

phunkie > Failure("error")->map(fn($x) => $x * 2)
$var3: Validation = Failure("error")
```

### IO Monad

IO wraps side effects for controlled execution:

```php
phunkie > $io = IO(fn() => print("Hello World"))
$var0: IO = IO(...)

phunkie > $io->unsafeRun()
Hello World
$var1: Int = 1
```

Note: `print()` returns 1 in PHP, which is why `unsafeRun()` returns 1.

### Function Composition

```php
phunkie > $add5 = fn($x) => $x + 5
$add5: Callable = <function>

phunkie > $double = fn($x) => $x * 2
$double: Callable = <function>

phunkie > $composed = Function1($add5)->andThen($double)
$composed: Function1 = <function>

phunkie > $composed(10)
$var3: Int = 30
```

## Session Management

### Variables

All evaluated expressions are automatically assigned to variables:

```php
phunkie > 42
$var0: Int = 42

phunkie > "hello"
$var1: String = "hello"

phunkie > [1, 2, 3]
$var2: Array = [1, 2, 3]
```

### Explicit Variable Assignment

You can create your own variables:

```php
phunkie > $myList = ImmList(1, 2, 3)
$myList: ImmList = List(1, 2, 3)

phunkie > $doubled = $myList->map(fn($x) => $x * 2)
$doubled: ImmList = List(2, 4, 6)
```

### Session Persistence

Variables and imports persist throughout your REPL session:

```php
phunkie > $x = 10
$x: Int = 10

phunkie > $y = 20
$y: Int = 20

phunkie > $x + $y
$var0: Int = 30
```

## Advanced Features

### Multi-line Expressions

The REPL detects incomplete expressions and provides a continuation prompt:

```php
phunkie > function factorial($n) {
phunkie {   if ($n <= 1) {
phunkie {     what
phunkie {   }
phunkie {   return $n * factorial($n - 1);
phunkie { }
// defined function factorial()

phunkie > factorial(5)
$var0: Int = 120
```

### Control Flow Structures

```php
phunkie > if (true) {
phunkie {   echo "It's true!";
phunkie { }
It's true!
phunkie > for ($i = 0; $i < 3; $i++) {
phunkie {   echo "$i ";
phunkie { }
0 1 2
```

## Examples

### Example 1: List Processing

```php
phunkie > $numbers = ImmList(1, 2, 3, 4, 5, 6, 7, 8, 9, 10)
$var0: ImmList = List(1, 2, 3, 4, 5, 6, 7, 8, 9, 10)

phunkie > $evens = $numbers->filter(fn($x) => $x % 2 === 0)
$var1: ImmList = List(2, 4, 6, 8, 10)

phunkie > $squared = $evens->map(fn($x) => $x * $x)
$var2: ImmList = List(4, 16, 36, 64, 100)

phunkie > $sum = $squared->reduce(fn($acc, $x) => $acc + $x, 0)
$var3: Int = 220
```

### Example 2: Optional Values

```php
phunkie > function divide($a, $b) {
phunkie {   return $b === 0 ? None() : Some($a / $b);
phunkie { }
imported function divide()

phunkie > divide(10, 2)
$var0: Option = Some(5)

phunkie > divide(10, 0)
$var1: Option = None

phunkie > divide(10, 2)->getOrElse("Cannot divide")
$var2: Mixed = 5

phunkie > divide(10, 0)->getOrElse("Cannot divide")
$var3: String = "Cannot divide"
```

### Example 3: Validation and Error Handling

```php
phunkie > function validateAge($age) {
phunkie {   if ($age < 0) return Failure("Age cannot be negative");
phunkie {   if ($age > 150) return Failure("Age is unrealistic");
phunkie {   return Success($age);
phunkie { }
// defined function validateAge()

phunkie > validateAge(25)
$var0: Validation = Success(25)

phunkie > validateAge(-5)
$var1: Validation = Failure("Age cannot be negative")

phunkie > validateAge(200)
$var2: Validation = Failure("Age is unrealistic")
```

### Example 4: Chaining Operations

```php
phunkie > ImmList(1, 2, 3, 4, 5)
phunkie {   ->filter(fn($x) => $x % 2 === 1)
phunkie {   ->map(fn($x) => $x * $x)
phunkie {   ->reduce(fn($acc, $x) => $acc + $x, 0)
$var0: Int = 35
```

## Architecture

### Components

The Phunkie Console is built with a modular architecture:

#### Parser (`src/Functions/parsing.php`)
- Uses `nikic/php-parser` for robust PHP syntax analysis
- Detects incomplete expressions for multi-line support
- Parses REPL commands (`:type`, `:kind`, etc.)

#### Evaluator (`src/Functions/evaluation.php`)
- Safe evaluation of PHP expressions
- Handles variable assignment and persistence
- Manages function and class imports
- Detects output statements (echo, print, var_dump)

#### Session (`src/Functions/session.php`)
- State monad for functional state management
- Tracks variables, imports, and REPL state
- Maintains evaluation history

#### Display (`src/Functions/display.php`)
- Formatted output of evaluation results
- Type information display
- Syntax highlighting support

#### Terminal (`src/Functions/terminal.php`)
- Readline integration
- Command history
- Prompt rendering with color support

#### REPL Loop (`src/Repl/ReplLoop.php`)
- Main REPL orchestration
- Command routing
- Input/output coordination

### Data Flow

```
User Input → Parser → Evaluator → State Update → Display → Output
                ↓                      ↓
           Commands              Session State
```

1. User enters input via readline
2. Parser analyzes the input
3. Evaluator executes the expression
4. Session state is updated with new variables
5. Display formats the result
6. Output is shown to the user

## Troubleshooting

### REPL Won't Start

**Problem**: `phunkie: command not found`

**Solution**: Ensure the package is installed and the binary is accessible:
```bash
composer install
php vendor/bin/phunkie
```

### No Color Output

**Problem**: Colors aren't showing even with `-c` flag

**Solution**: Ensure your terminal supports ANSI colors and PHP's readline extension is installed:
```bash
php -m | grep readline
```

### Variables Not Persisting

**Problem**: Variables disappear between expressions

**Solution**: This shouldn't happen in normal operation. If it does, it may indicate a parsing error. Check that your expressions are syntactically valid.

### Multi-line Input Issues

**Problem**: Multi-line expressions aren't working

**Solution**: The REPL automatically detects incomplete expressions. Ensure your opening braces/brackets are matched:
```php
# This will prompt for continuation
phunkie > function test() {
phunkie {   return 42;
phunkie { }
```

### Syntax Errors

**Problem**: Unexpected syntax errors

**Solution**: Remember this is a PHP REPL - standard PHP syntax rules apply. Common issues:
- Missing semicolons in multi-statement expressions
- Unmatched quotes or brackets
- Invalid PHP syntax

### Import Failures

**Problem**: `:import` command fails

**Solution**: Ensure the function or class exists and is loaded:
```php
# Load your autoloader first
phunkie > require 'vendor/autoload.php'

# Then import
phunkie > :import YourNamespace\YourClass
```

### Performance Issues

**Problem**: REPL becomes slow after many evaluations

**Solution**: Consider restarting the REPL session. Long-running sessions can accumulate state.

## Best Practices

1. **Use Type Inspection**: Use `:type` and `:kind` to understand data structures before working with them
2. **Import Early**: Import functions and classes you'll need at the start of your session
3. **Break Down Complex Expressions**: Use intermediate variables for readability
4. **Leverage Multi-line**: Don't try to write everything on one line - use multi-line for complex functions
5. **Save Your Work**: The REPL doesn't persist between sessions - copy important code to files

## Next Steps

- Explore the [Phunkie library documentation](https://github.com/phunkie/phunkie)
- Learn about functional programming patterns
- Try building small programs in the REPL before moving them to files
- Experiment with monads and functors

## Contributing

Found a bug or have a feature request? Please open an issue on the GitHub repository.

Want to contribute code? Pull requests are welcome! See the main README for development setup instructions.

## License

MIT License - see LICENSE file for details.

# Phunkie Console

[![Tests](https://github.com/phunkie/console/workflows/Tests/badge.svg)](https://github.com/phunkie/console/actions)
[![PHP Version](https://img.shields.io/packagist/php-v/phunkie/console?color=8892BF)](https://packagist.org/packages/phunkie/console)
[![Latest Stable Version](https://img.shields.io/packagist/v/phunkie/console)](https://packagist.org/packages/phunkie/console)
[![Total Downloads](https://img.shields.io/packagist/dt/phunkie/console)](https://packagist.org/packages/phunkie/console)
[![License](https://img.shields.io/packagist/l/phunkie/console)](https://github.com/phunkie/console/blob/main/LICENSE)

A powerful, interactive REPL (Read-Eval-Print Loop) console for [Phunkie](https://github.com/phunkie/phunkie) - bringing functional programming to PHP.

## Features

- **Interactive REPL**: Evaluate PHP expressions and see results immediately
- **Functional Programming**: Full access to Phunkie's functional programming constructs
- **Type Inspection**: Query types and kinds of expressions with `:type` and `:kind` commands
- **Import System**: Import functions from Phunkie standard library on-the-fly
- **Syntax Highlighting**: Color-coded output for better readability
- **Multi-line Support**: Write complex expressions across multiple lines
- **Rich Output**: Formatted display of complex data structures

## Requirements

- PHP 8.2, 8.3, or 8.4
- Composer

## Installation

```bash
composer require phunkie/console
```

Or install globally:

```bash
composer global require phunkie/console
```

## Usage

Start the REPL:

```bash
php vendor/bin/phunkie
```

Or if installed globally:

```bash
$ phunkie
Welcome to phunkie console.

Type in expressions to have them evaluated.

phunkie >
```

### Basic Examples

```php
phunkie > 1 + 2
$var0: Int = 3

phunkie > ImmList(1, 2, 3, 4, 5)->map(fn($x) => $x * 2)
$var1: ImmList = List(2, 4, 6, 8, 10)

phunkie > Some(42)->map(fn($x) => $x * 2)
$var2: Option = Some(84)
```

### REPL Commands

- `:quit` or `:exit` - Exit the REPL
- `:type <expression>` - Show the type of an expression
- `:kind <type>` - Show the kind of a type
- `:import <function(s)>` - Import a function from the Phunkie standard library
- `:load <filepath>` - Load a PHP file silently (all definitions become available in the session)
- `:help` - Show help information

### Working with Options

```php
phunkie > Some(42)->getOrElse(0)
$var0: Int = 42

phunkie > None()->getOrElse(0)
$var1: Int = 0

phunkie > Some(10)->flatMap(fn($x) => Some($x * 2))
$var2: Option = Some(20)
```

### Working with Immutable Collections

```php
phunkie > $list = ImmList(1, 2, 3, 4, 5)
$var0: ImmList = List(1, 2, 3, 4, 5)

phunkie > $list->filter(fn($x) => $x > 2)
$var1: ImmList = List(3, 4, 5)

phunkie > $list->reduce(fn($acc, $x) => $acc + $x, 0)
$var2: Int = 15
```

### Multi-line Expressions

The REPL automatically detects incomplete expressions and prompts for continuation:

```php
phunkie > function double($x) {
phunkie {   return $x * 2;
phunkie { }
imported function double()

phunkie > double(21)
$var0: Int = 42
```

### Examples with REPL Commands

```php
phunkie > :type ImmList(1, 2, 3)
ImmList

phunkie > :kind Option
* -> *

phunkie > :import strlen
imported function strlen()

phunkie > strlen("hello")
$var0: Int = 5

phunkie > :load helpers.php
// file helpers.php loaded

phunkie > // Now all functions and classes from helpers.php are available
```

## Configuration

### Color Support

Enable colors with the `-c` flag:

```bash
php vendor/bin/phunkie -c
```

## Development

### Running Tests

Run all tests:

```bash
./vendor/bin/phpunit
./vendor/bin/behat
```

Run version-specific tests:

```bash
./bin/run-behat-tests.sh
```

### Test Coverage

The project maintains comprehensive test coverage:
- **Unit Tests**: Testing individual components with PHPUnit
- **Acceptance Tests**: End-to-end REPL functionality with Behat
- **Cross-version Testing**: Automated testing across PHP 8.2, 8.3, and 8.4

### Code Quality

```bash
# Static analysis
./vendor/bin/phpstan analyze

# Code style
./vendor/bin/php-cs-fixer fix
```

## Architecture

The console is built with a modular architecture:

- **Parser**: Uses nikic/php-parser for PHP syntax analysis
- **Evaluator**: Safe evaluation of PHP expressions
- **Session**: Stateful session management using the State monad
- **Display**: Formatted output with type information
- **Terminal**: Readline integration for command history

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Links

- [Phunkie Library](https://github.com/phunkie/phunkie)
- [Documentation](./docs/index.md)
- [Issue Tracker](https://github.com/phunkie/console/issues)

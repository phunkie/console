Feature: Throw expressions
  As a developer using the REPL
  I want to use throw as an expression in PHP 8.0+
  So that I can handle errors in ternary operators, null coalescing, arrow functions, and match expressions

  Scenario: Throw in ternary operator - condition is false
    Given I start the REPL
    When I enter "$x = false"
    And I enter "$y = $x ? 'valid' : throw new \Exception('Invalid value')"
    Then I should see output containing "Error"
    And I should see output containing "Invalid value"

  Scenario: Throw in ternary operator - condition is true
    Given I start the REPL
    When I enter "$x = true"
    And I enter "$y = $x ? 'valid' : throw new \Exception('Invalid value')"
    Then I should see output containing "$y: String = \"valid\""

  Scenario: Throw with null coalescing operator - value is null
    Given I start the REPL
    When I enter "$x = null"
    And I enter "$y = $x ?? throw new \Exception('Value is required')"
    Then I should see output containing "Error"
    And I should see output containing "Value is required"

  Scenario: Throw with null coalescing operator - value is not null
    Given I start the REPL
    When I enter "$x = 42"
    And I enter "$y = $x ?? throw new \Exception('Value is required')"
    Then I should see output containing "$y: Int = 42"

  Scenario: Throw in arrow function
    Given I start the REPL
    When I enter "$fn = fn() => throw new \RuntimeException('Not implemented')"
    Then I should see output containing "$fn: Callable = <function>"
    When I enter "$fn()"
    Then I should see output containing "Error"
    And I should see output containing "Not implemented"

  Scenario: Throw in match expression default case
    Given I start the REPL
    When I enter "$x = 5"
    And I enter "$result = match($x) { 1 => 'one', 2 => 'two', default => throw new \Exception('Unknown value') }"
    Then I should see output containing "Error"
    And I should see output containing "Unknown value"

  Scenario: Throw in match expression - matching case
    Given I start the REPL
    When I enter "$x = 1"
    And I enter "$result = match($x) { 1 => 'one', 2 => 'two', default => throw new \Exception('Unknown value') }"
    Then I should see output containing "$result: String = \"one\""

  Scenario: Standalone throw expression
    Given I start the REPL
    When I enter "throw new \InvalidArgumentException('This is an error')"
    Then I should see output containing "Error"
    And I should see output containing "This is an error"

  Scenario: Throw with custom exception class
    Given I start the REPL
    When I enter "class MyException extends \Exception {}"
    And I enter "throw new MyException('Custom error message')"
    Then I should see output containing "Error"
    And I should see output containing "Custom error message"

  Scenario: Throw in arrow function with parameter
    Given I start the REPL
    When I enter "$validate = fn($val) => $val > 0 ? $val : throw new \Exception('Must be positive')"
    When I enter "$validate(5)"
    Then I should see output containing "$var0: Int = 5"
    When I enter "$validate(-1)"
    Then I should see output containing "Error"
    And I should see output containing "Must be positive"

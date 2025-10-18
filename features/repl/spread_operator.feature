Feature: Spread Operator Support
  As a PHP developer
  I want to use spread operators in arrays and function calls
  So that I can unpack arrays and iterables

  Scenario: Array spreading with numeric arrays
    Given I start the REPL
    When I enter "$arr1 = [1, 2, 3]"
    And I enter "$arr2 = [4, 5, 6]"
    And I enter "[...$arr1, ...$arr2]"
    Then I should see output containing "$var0: Array = [1, 2, 3, 4, 5, 6]"

  Scenario: Array spreading with mixed values
    Given I start the REPL
    When I enter "$arr = [2, 3]"
    And I enter "[1, ...$arr, 4, 5]"
    Then I should see output containing "$var0: Array = [1, 2, 3, 4, 5]"

  Scenario: Array spreading with associative arrays
    Given I start the REPL
    When I enter '$arr1 = ["a" => 1, "b" => 2]'
    And I enter '$arr2 = ["c" => 3, "d" => 4]'
    And I enter "[...$arr1, ...$arr2]"
    Then I should see output containing '"a" => 1'
    And I should see output containing '"b" => 2'
    And I should see output containing '"c" => 3'
    And I should see output containing '"d" => 4'

  Scenario: Multiple array spreading
    Given I start the REPL
    When I enter "$a = [1, 2]"
    And I enter "$b = [3, 4]"
    And I enter "$c = [5, 6]"
    And I enter "[...$a, ...$b, ...$c]"
    Then I should see output containing "$var0: Array = [1, 2, 3, 4, 5, 6]"

  Scenario: Function call with spread operator
    Given I start the REPL
    When I enter "function sum(int $a, int $b, int $c) { return $a + $b + $c; }"
    And I enter "$args = [1, 2, 3]"
    And I enter "sum(...$args)"
    Then I should see output containing "$var0: Int = 6"

  Scenario: Function call with mixed arguments and spread
    Given I start the REPL
    When I enter "function greet(string $greeting, string $name, string $suffix) { return $greeting . ' ' . $name . $suffix; }"
    And I enter "$parts = ['World', '!']"
    And I enter 'greet("Hello", ...$parts)'
    Then I should see output containing '$var0: String = "Hello World!"'

  Scenario: Built-in function with spread operator
    Given I start the REPL
    When I enter "$numbers = [1, 5, 3, 9, 2]"
    And I enter "max(...$numbers)"
    Then I should see output containing "$var0: Int = 9"

  Scenario: Spread operator with empty array
    Given I start the REPL
    When I enter "$empty = []"
    And I enter "$arr = [1, 2]"
    And I enter "[...$empty, ...$arr]"
    Then I should see output containing "$var0: Array = [1, 2]"

  Scenario: Multiple spreads in array
    Given I start the REPL
    When I enter "$x = [1, 2]"
    And I enter "$y = [3, 4]"
    And I enter "[0, ...$x, ...$y, 5]"
    Then I should see output containing "$var0: Array = [0, 1, 2, 3, 4, 5]"

  Scenario: Nested array spreading
    Given I start the REPL
    When I enter "$inner = [2, 3]"
    And I enter "$outer = [1, ...$inner, 4]"
    And I enter "[0, ...$outer, 5]"
    Then I should see output containing "$var0: Array = [0, 1, 2, 3, 4, 5]"

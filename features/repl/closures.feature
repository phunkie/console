Feature: Closures
  As a PHP developer
  I want to use closures in the REPL
  So that I can create anonymous functions

  Scenario: Simple closure with return
    Given I start the REPL
    When I enter "$f = function($x) { return $x * 2; }"
    And I enter "$f(5)"
    Then I should see output containing "Int = 10"

  Scenario: Closure with use clause
    Given I start the REPL
    When I enter "$multiplier = 3"
    And I enter "$f = function($x) use ($multiplier) { return $x * $multiplier; }"
    And I enter "$f(4)"
    Then I should see output containing "Int = 12"

  Scenario: Closure with multiple parameters
    Given I start the REPL
    When I enter "$add = function($a, $b) { return $a + $b; }"
    And I enter "$add(10, 20)"
    Then I should see output containing "Int = 30"

  Scenario: Closure with multiple use variables
    Given I start the REPL
    When I enter "$x = 5"
    And I enter "$y = 10"
    And I enter "$f = function($z) use ($x, $y) { return $x + $y + $z; }"
    And I enter "$f(15)"
    Then I should see output containing "Int = 30"

  Scenario: Closure without return statement
    Given I start the REPL
    When I enter "$f = function() { $x = 42; }"
    And I enter "$result = $f()"
    Then I should see output containing "Null = null"

  Scenario: Closure passed to array_map
    Given I start the REPL
    When I enter "$double = function($x) { return $x * 2; }"
    And I enter "$result = array_map($double, [1, 2, 3])"
    And I enter "$result[0]"
    Then I should see output containing "Int = 2"

  Scenario: Closure passed to array_filter
    Given I start the REPL
    When I enter "$isEven = function($x) { return $x % 2 === 0; }"
    And I enter "$result = array_filter([1, 2, 3, 4, 5], $isEven)"
    And I enter "array_values($result)[0]"
    Then I should see output containing "Int = 2"

  Scenario: Closure with string operations
    Given I start the REPL
    When I enter "$upper = function($s) { return strtoupper($s); }"
    And I enter "$upper('hello')"
    Then I should see output containing "String = \"HELLO\""

  Scenario: Nested closures
    Given I start the REPL
    When I enter "$outer = function($x) { return function($y) use ($x) { return $x + $y; }; }"
    And I enter "$inner = $outer(10)"
    And I enter "$inner(5)"
    Then I should see output containing "Int = 15"

  Scenario: Closure with type hint
    Given I start the REPL
    When I enter "$f = function(int $x): int { return $x * 2; }"
    And I enter "$f(7)"
    Then I should see output containing "Int = 14"

Feature: Pipe Operator (PHP 8.5)
  As a PHP developer
  I want to use the pipe operator in the REPL
  So that I can read a chain of transformations left to right

  Scenario: Piping into a first-class callable
    Given I start the REPL
    When I enter "\"hi\" |> strtoupper(...)"
    Then I should see output containing "String = \"HI\""

  Scenario: Chaining pipes left to right
    Given I start the REPL
    When I enter "\" a \" |> trim(...) |> strtoupper(...)"
    Then I should see output containing "String = \"A\""

  Scenario: Piping into a parenthesised arrow function
    Given I start the REPL
    When I enter "5 |> (fn($n) => $n * 2)"
    Then I should see output containing "Int = 10"

  Scenario: Piping an array into a function
    Given I start the REPL
    When I enter "[3, 1, 2] |> array_sum(...)"
    Then I should see output containing "Int = 6"

  Scenario: Piping into a closure held in a variable
    Given I start the REPL
    When I enter "$double = fn($n) => $n * 2"
    And I enter "21 |> $double"
    Then I should see output containing "Int = 42"

  Scenario: Piping into a user defined function
    Given I start the REPL
    When I enter "function shout(string $s): string { return $s . \"!\"; }"
    And I enter "\"go\" |> shout(...)"
    Then I should see output containing "String = \"go!\""

  Scenario: Pipe binds looser than concatenation
    Given I start the REPL
    When I enter "\"a\" . \"bc\" |> strlen(...)"
    Then I should see output containing "Int = 3"

  Scenario: Pipe binds tighter than comparison
    Given I start the REPL
    When I enter "\"beep\" |> strlen(...) == 4"
    Then I should see output containing "Boolean = true"

  Scenario: Piping into a value that is not callable
    Given I start the REPL
    When I enter "5 |> 42"
    Then I should see an error containing "not callable"

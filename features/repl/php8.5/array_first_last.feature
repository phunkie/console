Feature: array_first() and array_last() (PHP 8.5)
  As a PHP developer
  I want to read the first and last element of an array
  So that I do not have to reach for reset() or array_key_last()

  Scenario: array_first() on a list
    Given I start the REPL
    When I enter "array_first([3, 4, 5])"
    Then I should see output containing "Int = 3"

  Scenario: array_last() on a list
    Given I start the REPL
    When I enter "array_last([3, 4, 5])"
    Then I should see output containing "Int = 5"

  Scenario: array_first() on an empty array
    Given I start the REPL
    When I enter "array_first([])"
    Then I should see output containing "Null = null"

  Scenario: array_last() on an empty array
    Given I start the REPL
    When I enter "array_last([])"
    Then I should see output containing "Null = null"

  Scenario: array_first() ignores keys
    Given I start the REPL
    When I enter "$data = [\"b\" => \"beta\", \"a\" => \"alpha\"]"
    And I enter "array_first($data)"
    Then I should see output containing "String = \"beta\""

  Scenario: array_last() ignores keys
    Given I start the REPL
    When I enter "$data = [\"b\" => \"beta\", \"a\" => \"alpha\"]"
    And I enter "array_last($data)"
    Then I should see output containing "String = \"alpha\""

  Scenario: array_first() does not move the internal pointer
    Given I start the REPL
    When I enter "$numbers = [10, 20, 30]"
    And I enter "array_first($numbers)"
    And I enter "array_first($numbers)"
    Then I should see output containing "Int = 10"

  Scenario: Piping an array into array_last()
    Given I start the REPL
    When I enter "[1, 2, 3] |> array_last(...)"
    Then I should see output containing "Int = 3"

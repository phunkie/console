Feature: Type casts
  As a PHP developer
  I want to use type casts in the REPL
  So that I can convert values between types

  Scenario: Casting a numeric string to int
    Given I start the REPL
    When I enter "(int) \"42\""
    Then I should see output containing "Int = 42"

  Scenario: Casting a float to int truncates
    Given I start the REPL
    When I enter "(int) 1.9"
    Then I should see output containing "Int = 1"

  Scenario: Casting a string to float
    Given I start the REPL
    When I enter "(float) \"3.5\""
    Then I should see output containing "Float = 3.5"

  Scenario: Casting an int to string
    Given I start the REPL
    When I enter "(string) 42"
    Then I should see output containing "String = \"42\""

  Scenario: Casting zero to bool
    Given I start the REPL
    When I enter "(bool) 0"
    Then I should see output containing "Bool = false"

  Scenario: Casting a non-empty string to bool
    Given I start the REPL
    When I enter "(bool) \"phunkie\""
    Then I should see output containing "Bool = true"

  Scenario: Casting a scalar to array
    Given I start the REPL
    When I enter "(array) \"a\""
    Then I should see output containing "Array = [\"a\"]"

  Scenario: Casting an array to object
    Given I start the REPL
    When I enter "$o = (object) [\"a\" => 1]"
    And I enter "$o->a"
    Then I should see output containing "Int = 1"

  Scenario: Casting an object to array
    Given I start the REPL
    When I enter "$values = (array) (object) [\"a\" => 1]"
    And I enter "$values[\"a\"]"
    Then I should see output containing "Int = 1"

  Scenario: Cast applies to a variable
    Given I start the REPL
    When I enter "$n = \"7\""
    And I enter "(int) $n"
    Then I should see output containing "Int = 7"

  Scenario: Cast binds tighter than arithmetic
    Given I start the REPL
    When I enter "(int) \"3\" + 4"
    Then I should see output containing "Int = 7"

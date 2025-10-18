Feature: Variable Variables ($$var)
  As a PHP developer
  I want to use variable variables in the REPL
  So that I can dynamically reference variable names

  Scenario: Basic variable variable
    Given I start the REPL
    When I enter "$name = 'x'"
    And I enter "$x = 42"
    And I enter "$$name"
    Then I should see output containing "Int = 42"

  Scenario: Variable variable assignment
    Given I start the REPL
    When I enter "$varname = 'myvar'"
    And I enter "$$varname = 'hello'"
    Then I should see output containing "$myvar: String = \"hello\""
    When I enter "$myvar"
    Then I should see output containing "String = \"hello\""

  Scenario: Variable variable with string
    Given I start the REPL
    When I enter "$foo = 'bar'"
    And I enter "$bar = 'baz'"
    And I enter "$$foo"
    Then I should see output containing "String = \"baz\""

  Scenario: Triple variable variables
    Given I start the REPL
    When I enter "$a = 'b'"
    And I enter "$b = 'c'"
    And I enter "$c = 100"
    And I enter "$$$a"
    Then I should see output containing "Int = 100"

  Scenario: Variable variable in expression
    Given I start the REPL
    When I enter "$name = 'value'"
    And I enter "$value = 10"
    And I enter "$$name + 5"
    Then I should see output containing "Int = 15"

  Scenario: Variable variable with array
    Given I start the REPL
    When I enter "$key = 'data'"
    And I enter "$data = [1, 2, 3]"
    And I enter "$$key[1]"
    Then I should see output containing "Int = 2"

  Scenario: Variable variable undefined
    Given I start the REPL
    When I enter "$name = 'undefined'"
    And I enter "$$name"
    Then I should see output containing "Error"

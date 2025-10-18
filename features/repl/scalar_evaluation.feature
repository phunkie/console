Feature: Evaluating Scalar Values
  As a developer using the REPL
  I want to evaluate scalar expressions
  So that I can experiment with basic PHP values

  Scenario: Evaluating integers
    Given I start the REPL
    When I enter "42"
    Then I should see output containing "$var0: Int = 42"

  Scenario: Evaluating strings
    Given I start the REPL
    When I enter '"hello world"'
    Then I should see output containing '$var0: String = "hello world"'

  Scenario: Evaluating booleans
    Given I start the REPL
    When I enter "true"
    Then I should see output containing "$var0: Bool = true"
    When I enter "false"
    Then I should see output containing "$var1: Bool = false"

  Scenario: Evaluating null
    Given I start the REPL
    When I enter "null"
    Then I should see output containing "$var0: Null = null"

  Scenario: Evaluating floats
    Given I start the REPL
    When I enter "3.14"
    Then I should see output containing "$var0: Float = 3.14"

Feature: Session State Management
  As a developer using the REPL
  I want my variables to persist across evaluations
  So that I can build complex expressions incrementally

  Scenario: Variables are stored and can be referenced
    Given I start the REPL
    When I enter "42"
    Then I should see output containing "$var0: Int = 42"
    When I enter "$var0"
    Then I should see output containing "$var1: Int = 42"

  Scenario: Multiple variables persist
    Given I start the REPL
    When I enter "Some(1)"
    And I enter "Some(2)"
    And I enter "Some(3)"
    Then the session should have 3 variables

  Scenario: Variable counter increments
    Given I start the REPL
    When I enter "1"
    Then I should see output containing "$var0"
    When I enter "2"
    Then I should see output containing "$var1"
    When I enter "3"
    Then I should see output containing "$var2"

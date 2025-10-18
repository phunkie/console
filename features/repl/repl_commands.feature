Feature: REPL Commands
  As a developer using the REPL
  I want to use commands to control and inspect the REPL
  So that I can work effectively

  Scenario: Using :help command
    Given I start the REPL
    When I enter ":help"
    Then I should see output containing "Phunkie Console - REPL Commands"
    And I should see output containing ":exit"
    And I should see output containing ":vars"
    And I should see output containing ":history"

  Scenario: Using :vars command with no variables
    Given I start the REPL
    When I enter ":vars"
    Then I should see output containing "No variables defined"

  Scenario: Using :vars command with variables
    Given I start the REPL
    When I enter "42"
    And I enter ":vars"
    Then I should see output containing "Defined variables"
    And I should see output containing "$var0"

  Scenario: Using :history command
    Given I start the REPL
    When I enter "42"
    And I enter "Some(1)"
    And I enter ":history"
    Then I should see output containing "Command history"
    And I should see output containing "42"
    And I should see output containing "Some(1)"

  Scenario: Using :exit command
    Given I start the REPL
    When I enter ":exit"
    Then the REPL should exit gracefully

  Scenario: Using :quit command
    Given I start the REPL
    When I enter ":quit"
    Then the REPL should exit gracefully

  Scenario: Using :reset command clears variables
    Given I start the REPL
    When I enter "42"
    And I enter "Some(1)"
    And I enter ":reset"
    Then I should see output containing "REPL state reset"
    When I enter ":vars"
    Then I should see output containing "No variables defined"

  Scenario: Using :reset command clears history
    Given I start the REPL
    When I enter "42"
    And I enter "Some(1)"
    And I enter ":reset"
    When I enter ":history"
    Then I should see output containing "No history"

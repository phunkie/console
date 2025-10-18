Feature: Error Handling
  As a developer using the REPL
  I want clear error messages when something goes wrong
  So that I can understand and fix my mistakes

  Scenario: Referencing undefined variables
    Given I start the REPL
    When I enter "$undefined"
    Then I should see output containing "Variable not found"
    And I should see output containing "$undefined"

  Scenario: Invalid syntax
    Given I start the REPL
    When I enter "this is not valid php"
    Then I should see output containing "Parse error"

  Scenario: Unknown REPL command
    Given I start the REPL
    When I enter ":unknown"
    Then I should see output containing "Unknown command"
    And I should see output containing ":unknown"

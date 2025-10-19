Feature: REPL Startup
  As a developer
  I want to start the REPL easily
  So that I can begin working with Phunkie

  Scenario: Starting the REPL without flags
    Given I start the REPL
    Then I should see the welcome banner
    And I should see "Welcome to phunkie console"
    And I should see "Type in expressions to have them evaluated"

  Scenario: Starting the REPL with color flag
    Given I start the REPL with colors
    Then I should see the welcome banner
    When I enter "1 + 1"
    Then the REPL should support colors

Feature: Evaluating repl scalars
  As a repl runner
  In order to better develop functional programs
  I should a nice repl to interact with

  Scenario: Integers
    Given I am running the repl
    When I type "42"
    And I press enter
    Then I should see "$var0: Int = 42"
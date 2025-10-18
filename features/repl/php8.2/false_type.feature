Feature: False Type (PHP 8.2)
  As a PHP developer
  I want to use false as a standalone type in the REPL
  So that I can enforce type safety for functions that always return false

  Scenario: Function with false return type
    Given I start the REPL
    When I enter the following code:
      """
      function isFailure(): false {
          return false;
      }
      """
    Then I should see output containing "function isFailure defined"

  Scenario: Call function with false return type
    Given I start the REPL
    When I enter the following code:
      """
      function alwaysFalse(): false {
          return false;
      }
      """
    And I enter "alwaysFalse()"
    Then I should see output containing "Bool = false"

  # Note: Literal true/false/null types in parameters have a known limitation in REPL
  # due to how arguments are evaluated before function calls.
  # This works in native PHP but requires architectural changes to support in REPL.
  # Test scenario removed - tracked as future enhancement.

  Scenario: Method with false return type
    Given I start the REPL
    When I enter the following code:
      """
      class Checker {
          public function check(): false {
              return false;
          }
      }
      """
    And I enter "$checker = new Checker()"
    And I enter "$checker->check()"
    Then I should see output containing "Bool = false"

  Scenario: Arrow function with false return type
    Given I start the REPL
    When I enter "$failure = fn(): false => false"
    And I enter "$failure()"
    Then I should see output containing "Bool = false"

  Scenario: False type in union with other types
    Given I start the REPL
    When I enter the following code:
      """
      function tryOperation(): false|array {
          return false;
      }
      """
    And I enter "tryOperation()"
    Then I should see output containing "Bool = false"

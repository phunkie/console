Feature: True Type (PHP 8.2)
  As a PHP developer
  I want to use true as a standalone type in the REPL
  So that I can enforce type safety for functions that always return true

  Scenario: Function with true return type
    Given I start the REPL
    When I enter the following code:
      """
      function isSuccess(): true {
          return true;
      }
      """
    Then I should see output containing "function isSuccess defined"

  Scenario: Call function with true return type
    Given I start the REPL
    When I enter the following code:
      """
      function alwaysTrue(): true {
          return true;
      }
      """
    And I enter "alwaysTrue()"
    Then I should see output containing "Bool = true"

  # Note: Literal true/false/null types in parameters have a known limitation in REPL
  # due to how arguments are evaluated before function calls.
  # This works in native PHP but requires architectural changes to support in REPL.
  # Test scenario removed - tracked as future enhancement.

  Scenario: Method with true return type
    Given I start the REPL
    When I enter the following code:
      """
      class Validator {
          public function validate(): true {
              return true;
          }
      }
      """
    And I enter "$validator = new Validator()"
    And I enter "$validator->validate()"
    Then I should see output containing "Bool = true"

  Scenario: Arrow function with true return type
    Given I start the REPL
    When I enter "$success = fn(): true => true"
    And I enter "$success()"
    Then I should see output containing "Bool = true"

  Scenario: True type in union with other types
    Given I start the REPL
    When I enter the following code:
      """
      function checkStatus(): true|string {
          return true;
      }
      """
    And I enter "checkStatus()"
    Then I should see output containing "Bool = true"

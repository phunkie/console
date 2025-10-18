Feature: Null Type (PHP 8.2)
  As a PHP developer
  I want to use null as a standalone type in the REPL
  So that I can enforce type safety for functions that always return null

  Scenario: Function with null return type
    Given I start the REPL
    When I enter the following code:
      """
      function alwaysNull(): null {
          return null;
      }
      """
    Then I should see output containing "function alwaysNull defined"

  Scenario: Call function with null return type
    Given I start the REPL
    When I enter the following code:
      """
      function returnNull(): null {
          return null;
      }
      """
    And I enter "returnNull()"
    Then I should see output containing "Null = null"

  Scenario: Function parameter with null type
    Given I start the REPL
    When I enter the following code:
      """
      function acceptNull(null $value): string {
          return "Got null!";
      }
      """
    And I enter "acceptNull(null)"
    Then I should see output containing "String = \"Got null!\""

  Scenario: Method with null return type
    Given I start the REPL
    When I enter the following code:
      """
      class NullProvider {
          public function provide(): null {
              return null;
          }
      }
      """
    And I enter "$provider = new NullProvider()"
    And I enter "$provider->provide()"
    Then I should see output containing "Null = null"

  Scenario: Arrow function with null return type
    Given I start the REPL
    When I enter "$getNull = fn(): null => null"
    And I enter "$getNull()"
    Then I should see output containing "Null = null"

  Scenario: Null type in union with other types
    Given I start the REPL
    When I enter the following code:
      """
      function findItem(): null|string {
          return null;
      }
      """
    And I enter "findItem()"
    Then I should see output containing "Null = null"

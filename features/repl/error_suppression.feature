Feature: Error Suppression Operator (@)
  As a PHP developer
  I want to use the @ operator in the REPL
  So that I can suppress error messages for specific expressions

  Scenario: Suppress warning from undefined array key
    Given I start the REPL
    When I enter "$arr = ['a' => 1]"
    And I enter "@$arr['b']"
    Then I should see output containing "Null = null"

  Scenario: Suppress warning from file operation
    Given I start the REPL
    When I enter "@file_get_contents('/nonexistent/file.txt')"
    Then I should see output containing "Bool = false"

  Scenario: Error suppression in variable assignment
    Given I start the REPL
    When I enter "$result = @$undefined_var"
    Then I should see output containing "$result: Null = null"

  Scenario: Error suppression with function call
    Given I start the REPL
    When I enter the following code:
      """
      function mayWarn() {
          trigger_error('This is a warning', E_USER_WARNING);
          return 42;
      }
      """
    And I enter "@mayWarn()"
    Then I should see output containing "Int = 42"

  Scenario: Error suppression with method call
    Given I start the REPL
    When I enter the following code:
      """
      class Logger {
          public function log() {
              trigger_error('Log warning', E_USER_WARNING);
              return 'logged';
          }
      }
      """
    And I enter "$logger = new Logger()"
    And I enter "@$logger->log()"
    Then I should see output containing "String = \"logged\""

  Scenario: Nested error suppression
    Given I start the REPL
    When I enter "$x = @(@$undefined['key'])"
    Then I should see output containing "$x: Null = null"

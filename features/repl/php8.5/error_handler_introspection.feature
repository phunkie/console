Feature: Error Handler Introspection (PHP 8.5)
  As a PHP developer
  I want to read the current error and exception handlers
  So that I can inspect them without the set-and-restore dance

  Scenario: get_error_handler() reports the handler the REPL installs
    Given I start the REPL
    When I enter "get_error_handler()"
    Then I should see output containing "Callable"

  Scenario: get_exception_handler() returns a handler once one is set
    Given I start the REPL
    When I enter "set_exception_handler(function ($e) { return null; })"
    And I enter "is_callable(get_exception_handler())"
    Then I should see output containing "Boolean = true"

  Scenario: get_exception_handler() is null once the handler is cleared
    Given I start the REPL
    When I enter "set_exception_handler(function ($e) { return null; })"
    And I enter "set_exception_handler(null)"
    And I enter "get_exception_handler()"
    Then I should see output containing "Null = null"

  Scenario: PHP_BUILD_DATE is available
    Given I start the REPL
    When I enter "is_string(PHP_BUILD_DATE)"
    Then I should see output containing "Boolean = true"

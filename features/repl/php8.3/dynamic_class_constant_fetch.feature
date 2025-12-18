Feature: Dynamic Class Constant Fetch (PHP 8.3)
  As a PHP developer
  I want to dynamically fetch class constants using variable syntax
  So that I can access constants programmatically

  Scenario: Dynamic constant fetch with variable
    Given I start the REPL
    When I enter the following code:
      """
      class Status {
          public const PENDING = 'pending';
          public const ACTIVE = 'active';
          public const COMPLETED = 'completed';
      }
      """
    And I enter "$const = 'PENDING'"
    And I enter "Status::{$const}"
    Then I should see output containing "String = \"pending\""

  Scenario: Dynamic constant fetch with expression
    Given I start the REPL
    When I enter the following code:
      """
      class Config {
          public const PROD = 'production';
          public const DEV = 'development';
      }
      """
    And I enter "$env = 'PROD'"
    And I enter "Config::{$env}"
    Then I should see output containing "String = \"production\""

  Scenario: Dynamic constant fetch in loop
    Given I start the REPL
    When I enter the following code:
      """
      class Colors {
          public const RED = '#FF0000';
          public const GREEN = '#00FF00';
          public const BLUE = '#0000FF';
      }
      """
    And I enter "$names = ['RED', 'GREEN', 'BLUE']"
    And I enter "Colors::{$names[0]}"
    Then I should see output containing "String = \"#FF0000\""

  Scenario: Dynamic constant with concatenation
    Given I start the REPL
    When I enter the following code:
      """
      class Limits {
          public const MAX_SIZE = 1024;
          public const MIN_SIZE = 1;
      }
      """
    And I enter "$type = 'MAX'"
    And I enter "$constName = $type . '_SIZE'"
    And I enter "Limits::{$constName}"
    Then I should see output containing "Int = 1024"

  Scenario: Dynamic constant on interface
    Given I start the REPL
    When I enter the following code:
      """
      interface HttpMethods {
          public const GET = 'GET';
          public const POST = 'POST';
          public const PUT = 'PUT';
      }
      """
    And I enter "$method = 'POST'"
    And I enter "HttpMethods::{$method}"
    Then I should see output containing "String = \"POST\""

  Scenario: Comparing with constant() function
    Given I start the REPL
    When I enter the following code:
      """
      class Test {
          public const VALUE = 42;
      }
      """
    And I enter "$name = 'VALUE'"
    And I enter "Test::{$name}"
    Then I should see output containing "Int = 42"
    When I enter "constant('Test::VALUE')"
    Then I should see output containing "Int = 42"

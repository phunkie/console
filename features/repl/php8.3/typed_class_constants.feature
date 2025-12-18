@php83
Feature: Typed Class Constants (PHP 8.3)
  As a PHP developer
  I want to use typed class constants in the REPL
  So that I can enforce type safety on constant values

  Scenario: Class constant with int type
    Given I start the REPL
    When I enter the following code:
      """
      class Config {
          public const int MAX_SIZE = 1024;
      }
      """
    And I enter "Config::MAX_SIZE"
    Then I should see output containing "Int = 1024"

  Scenario: Class constant with string type
    Given I start the REPL
    When I enter the following code:
      """
      class AppConfig {
          public const string NAME = "MyApp";
          public const string VERSION = "1.0.0";
      }
      """
    And I enter "AppConfig::NAME"
    Then I should see output containing "String = \"MyApp\""
    When I enter "AppConfig::VERSION"
    Then I should see output containing "String = \"1.0.0\""

  Scenario: Class constant with array type
    Given I start the REPL
    When I enter the following code:
      """
      class Settings {
          public const array OPTIONS = ['debug' => true, 'cache' => false];
      }
      """
    And I enter "Settings::OPTIONS"
    Then I should see output containing "Array"

  Scenario: Interface constant with type
    Given I start the REPL
    When I enter the following code:
      """
      interface Dimensions {
          public const int WIDTH = 800;
          public const int HEIGHT = 600;
      }
      """
    And I enter "Dimensions::WIDTH"
    Then I should see output containing "Int = 800"

  # Note: Enum constants currently have a known issue where they're
  # treated as enum cases. This will be addressed separately.

  Scenario: Enum constant with type - known limitation
    Given I start the REPL
    When I enter the following code:
      """
      enum Status {
          case PENDING;
          case ACTIVE;

          public const string DEFAULT_MESSAGE = "Processing";
      }
      """
    And I enter "Status::DEFAULT_MESSAGE"
    Then I should see output containing "Error"

  Scenario: Trait constant with type
    Given I start the REPL
    When I enter the following code:
      """
      trait Configurable {
          public const int TIMEOUT = 30;
      }
      """
    And I enter the following code:
      """
      class Service {
          use Configurable;
      }
      """
    And I enter "Service::TIMEOUT"
    Then I should see output containing "Int = 30"

  Scenario: Multiple typed constants
    Given I start the REPL
    When I enter the following code:
      """
      class Database {
          public const string HOST = "localhost";
          public const int PORT = 3306;
          public const bool SSL = true;
      }
      """
    And I enter "Database::HOST"
    Then I should see output containing "String = \"localhost\""
    When I enter "Database::PORT"
    Then I should see output containing "Int = 3306"
    When I enter "Database::SSL"
    Then I should see output containing "Bool = true"

  Scenario: Private typed constant
    Given I start the REPL
    When I enter the following code:
      """
      class Secret {
          private const string KEY = "secret123";

          public static function getKey(): string {
              return self::KEY;
          }
      }
      """
    And I enter "Secret::getKey()"
    Then I should see output containing "String = \"secret123\""

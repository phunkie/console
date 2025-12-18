@php83
Feature: Trait Constant Visibility (PHP 8.3)
  As a PHP developer
  I want to use visibility modifiers on trait constants
  So that I can control access to trait constants

  Scenario: Public trait constant
    Given I start the REPL
    When I enter the following code:
      """
      trait Config {
          public const VERSION = '1.0.0';
      }
      """
    And I enter the following code:
      """
      class App {
          use Config;
      }
      """
    And I enter "App::VERSION"
    Then I should see output containing "String = \"1.0.0\""

  Scenario: Private trait constant
    Given I start the REPL
    When I enter the following code:
      """
      trait Database {
          private const HOST = 'localhost';

          public static function getHost(): string {
              return self::HOST;
          }
      }
      """
    And I enter the following code:
      """
      class Connection {
          use Database;
      }
      """
    And I enter "Connection::getHost()"
    Then I should see output containing "String = \"localhost\""

  Scenario: Protected trait constant
    Given I start the REPL
    When I enter the following code:
      """
      trait Logger {
          protected const LOG_LEVEL = 'debug';

          public static function getLevel(): string {
              return self::LOG_LEVEL;
          }
      }
      """
    And I enter the following code:
      """
      class MyLogger {
          use Logger;
      }
      """
    And I enter "MyLogger::getLevel()"
    Then I should see output containing "String = \"debug\""

  Scenario: Multiple trait constants with different visibility
    Given I start the REPL
    When I enter the following code:
      """
      trait Settings {
          public const PUBLIC_KEY = 'public123';
          protected const PROTECTED_KEY = 'protected456';
          private const PRIVATE_KEY = 'private789';

          public static function getAll(): array {
              return [
                  'public' => self::PUBLIC_KEY,
                  'protected' => self::PROTECTED_KEY,
                  'private' => self::PRIVATE_KEY
              ];
          }
      }
      """
    And I enter the following code:
      """
      class Config {
          use Settings;
      }
      """
    And I enter "Config::PUBLIC_KEY"
    Then I should see output containing "String = \"public123\""
    When I enter "$all = Config::getAll()"
    And I enter "$all['private']"
    Then I should see output containing "String = \"private789\""

  Scenario: Typed trait constant with visibility
    Given I start the REPL
    When I enter the following code:
      """
      trait Limits {
          public const int MAX_SIZE = 1024;
          private const int MIN_SIZE = 1;
          protected const string DEFAULT_NAME = 'default';
      }
      """
    And I enter the following code:
      """
      class Validator {
          use Limits;

          public static function getMax(): int {
              return self::MAX_SIZE;
          }
      }
      """
    And I enter "Validator::MAX_SIZE"
    Then I should see output containing "Int = 1024"
    When I enter "Validator::getMax()"
    Then I should see output containing "Int = 1024"

  Scenario: Trait constant visibility inheritance
    Given I start the REPL
    When I enter the following code:
      """
      trait BaseTrait {
          protected const VALUE = 'base';
      }
      """
    And I enter the following code:
      """
      class ParentClass {
          use BaseTrait;
      }
      """
    And I enter the following code:
      """
      class ChildClass extends ParentClass {
          public static function getValue(): string {
              return self::VALUE;
          }
      }
      """
    And I enter "ChildClass::getValue()"
    Then I should see output containing "String = \"base\""

  Scenario: Multiple traits with constants
    Given I start the REPL
    When I enter the following code:
      """
      trait TraitA {
          public const A = 'from A';
      }
      """
    And I enter the following code:
      """
      trait TraitB {
          public const B = 'from B';
      }
      """
    And I enter the following code:
      """
      class Combined {
          use TraitA, TraitB;
      }
      """
    And I enter "Combined::A"
    Then I should see output containing "String = \"from A\""
    When I enter "Combined::B"
    Then I should see output containing "String = \"from B\""

  Scenario: Trait constant with default visibility (public)
    Given I start the REPL
    When I enter the following code:
      """
      trait DefaultVis {
          const IMPLICIT_PUBLIC = 'visible';
      }
      """
    And I enter the following code:
      """
      class TestClass {
          use DefaultVis;
      }
      """
    And I enter "TestClass::IMPLICIT_PUBLIC"
    Then I should see output containing "String = \"visible\""

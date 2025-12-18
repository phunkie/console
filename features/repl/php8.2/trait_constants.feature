Feature: Constants in Traits (PHP 8.2)
  As a PHP developer
  I want to define constants in traits in the REPL
  So that I can share constant values across multiple classes

  @fixme
  Scenario: Define trait with public constant
    Given I start the REPL
    When I enter the following code:
      """
      trait StatusTrait {
          public const ACTIVE = 'active';
          public const INACTIVE = 'inactive';
      }
      """
    Then I should see output containing "trait StatusTrait defined"

  Scenario: Use trait constant in class
    Given I start the REPL
    When I enter the following code:
      """
      trait ConfigTrait {
          public const VERSION = '1.0.0';

          public function getVersion(): string {
              return self::VERSION;
          }
      }
      """
    And I enter the following code:
      """
      class App {
          use ConfigTrait;
      }
      """
    And I enter "$app = new App()"
    And I enter "$app->getVersion()"
    Then I should see output containing "String = \"1.0.0\""

  Scenario: Trait with private constant
    Given I start the REPL
    When I enter the following code:
      """
      trait SecretTrait {
          private const SECRET_KEY = 'my-secret';

          public function getSecret(): string {
              return self::SECRET_KEY;
          }
      }
      """
    And I enter the following code:
      """
      class SecureApp {
          use SecretTrait;
      }
      """
    And I enter "$app = new SecureApp()"
    And I enter "$app->getSecret()"
    Then I should see output containing "String = \"my-secret\""

  Scenario: Multiple constants in trait
    Given I start the REPL
    When I enter the following code:
      """
      trait HttpTrait {
          public const GET = 'GET';
          public const POST = 'POST';
          public const PUT = 'PUT';
          public const DELETE = 'DELETE';

          public function getMethods(): array {
              return [self::GET, self::POST, self::PUT, self::DELETE];
          }
      }
      """
    And I enter the following code:
      """
      class HttpClient {
          use HttpTrait;
      }
      """
    And I enter "$client = new HttpClient()"
    And I enter "count($client->getMethods())"
    Then I should see output containing "Int = 4"

  @fixme
  Scenario: Trait constant with type
    Given I start the REPL
    When I enter the following code:
      """
      trait MathTrait {
          public const PI = 3.14159;
          public const E = 2.71828;

          public function getPi(): float {
              return self::PI;
          }
      }
      """
    And I enter the following code:
      """
      class Calculator {
          use MathTrait;
      }
      """
    And I enter "$calc = new Calculator()"
    And I enter "$calc->getPi()"
    Then I should see output containing "Float = 3.14159"

  Scenario: Access trait constant from class method
    Given I start the REPL
    When I enter the following code:
      """
      trait ErrorTrait {
          public const ERROR_NOT_FOUND = 404;
          public const ERROR_SERVER = 500;
      }
      """
    And I enter the following code:
      """
      class ErrorHandler {
          use ErrorTrait;

          public function getNotFoundCode(): int {
              return self::ERROR_NOT_FOUND;
          }
      }
      """
    And I enter "$handler = new ErrorHandler()"
    And I enter "$handler->getNotFoundCode()"
    Then I should see output containing "Int = 404"

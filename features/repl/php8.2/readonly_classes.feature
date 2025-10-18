Feature: Readonly Classes (PHP 8.2)
  As a PHP developer
  I want to use readonly classes in the REPL
  So that I can create fully immutable objects with syntactic sugar

  Scenario: Basic readonly class declaration
    Given I start the REPL
    When I enter the following code:
      """
      readonly class Point {
          public function __construct(
              public int $x,
              public int $y
          ) {}
      }
      """
    Then I should see output containing "class Point defined"

  Scenario: Create instance of readonly class
    Given I start the REPL
    When I enter the following code:
      """
      readonly class Coordinate {
          public function __construct(
              public float $latitude,
              public float $longitude
          ) {}
      }
      """
    And I enter "$coord = new Coordinate(40.7128, -74.0060)"
    And I enter "$coord->latitude"
    Then I should see output containing "Float = 40.7128"

  Scenario: Readonly class properties cannot be modified
    Given I start the REPL
    When I enter the following code:
      """
      readonly class Config {
          public function __construct(
              public string $env,
              public bool $debug
          ) {}
      }
      """
    And I enter "$config = new Config('production', false)"
    When I enter "$config->env = 'dev'"
    Then I should see output containing "Error"

  Scenario: Readonly class with multiple properties
    Given I start the REPL
    When I enter the following code:
      """
      readonly class User {
          public function __construct(
              public string $name,
              public string $email,
              public int $age
          ) {}
      }
      """
    And I enter "$user = new User('Alice', 'alice@example.com', 30)"
    And I enter "$user->name"
    Then I should see output containing "String = \"Alice\""
    When I enter "$user->email"
    Then I should see output containing "String = \"alice@example.com\""

  Scenario: Readonly class with typed properties
    Given I start the REPL
    When I enter the following code:
      """
      readonly class Product {
          public function __construct(
              public string $sku,
              public float $price,
              public array $tags
          ) {}
      }
      """
    And I enter "$product = new Product('ABC-123', 99.99, ['new', 'sale'])"
    And I enter "$product->price"
    Then I should see output containing "Float = 99.99"

  Scenario: Readonly class prevents dynamic properties
    Given I start the REPL
    When I enter the following code:
      """
      readonly class ImmutableData {
          public function __construct(
              public string $value
          ) {}
      }
      """
    And I enter "$data = new ImmutableData('test')"
    When I enter "$data->newProperty = 'value'"
    Then I should see output containing "Error"

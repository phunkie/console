Feature: Readonly Properties (PHP 8.1+)
  As a PHP developer
  I want to use readonly properties in the REPL
  So that I can create immutable object properties

  Scenario: Readonly property with constructor promotion
    Given I start the REPL
    When I enter the following code:
      """
      class User {
          public function __construct(
              public readonly string $name,
              public readonly int $age
          ) {}
      }
      """
    Then I should see output containing "class User defined"

  Scenario: Read readonly property value
    Given I start the REPL
    When I enter the following code:
      """
      class Product {
          public function __construct(
              public readonly string $sku
          ) {}
      }
      """
    And I enter "$product = new Product('ABC123')"
    And I enter "$product->sku"
    Then I should see output containing "String = \"ABC123\""

  Scenario: Readonly property cannot be modified after initialization
    Given I start the REPL
    When I enter the following code:
      """
      class Item {
          public function __construct(
              public readonly string $code
          ) {}
      }
      """
    And I enter "$item = new Item('ITEM-001')"
    When I enter "$item->code = 'ITEM-002'"
    Then I should see output containing "Error"

  Scenario: Readonly property with explicit declaration
    Given I start the REPL
    When I enter the following code:
      """
      class Article {
          public readonly string $title;

          public function __construct(string $title) {
              $this->title = $title;
          }
      }
      """
    Then I should see output containing "class Article defined"

  Scenario: Readonly property initialized in constructor
    Given I start the REPL
    When I enter the following code:
      """
      class Book {
          public readonly string $isbn;

          public function __construct(string $isbn) {
              $this->isbn = $isbn;
          }
      }
      """
    And I enter "$book = new Book('978-3-16-148410-0')"
    And I enter "$book->isbn"
    Then I should see output containing "String = \"978-3-16-148410-0\""

  Scenario: Multiple readonly properties
    Given I start the REPL
    When I enter the following code:
      """
      class Point {
          public function __construct(
              public readonly int $x,
              public readonly int $y,
              public readonly int $z
          ) {}
      }
      """
    And I enter "$point = new Point(10, 20, 30)"
    And I enter "$point->x"
    Then I should see output containing "Int = 10"
    When I enter "$point->y"
    Then I should see output containing "Int = 20"

  Scenario: Readonly property with type hint
    Given I start the REPL
    When I enter the following code:
      """
      class Container {
          public readonly array $items;

          public function __construct(array $items) {
              $this->items = $items;
          }
      }
      """
    And I enter "$container = new Container([1, 2, 3])"
    And I enter "$container->items"
    Then I should see output containing "Array"

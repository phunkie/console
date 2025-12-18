Feature: Readonly Amendments (PHP 8.3)
  As a PHP developer
  I want to use readonly property modifications during cloning
  So that I can create deep clones of readonly objects

  Scenario: Readonly property modification in __clone
    Given I start the REPL
    When I enter the following code:
      """
      class Person {
          public function __construct(
              public readonly string $name,
              public readonly int $age
          ) {}

          public function __clone() {
              $this->age = $this->age + 1;
          }
      }
      """
    And I enter "$p1 = new Person('Alice', 30)"
    And I enter "$p2 = clone $p1"
    And I enter "$p2->age"
    Then I should see output containing "Int = 31"

  Scenario: Deep cloning readonly nested objects
    Given I start the REPL
    When I enter the following code:
      """
      class Address {
          public function __construct(
              public readonly string $city
          ) {}
      }
      """
    And I enter the following code:
      """
      class User {
          public function __construct(
              public readonly string $name,
              public readonly Address $address
          ) {}

          public function __clone() {
              $this->address = clone $this->address;
          }
      }
      """
    And I enter "$addr = new Address('Paris')"
    And I enter "$u1 = new User('Bob', $addr)"
    And I enter "$u2 = clone $u1"
    And I enter "$u2->address->city"
    Then I should see output containing "String = \"Paris\""

  Scenario: Anonymous readonly class
    Given I start the REPL
    When I enter the following code:
      """
      $obj = new readonly class {
          public function __construct(
              public string $value = 'test'
          ) {}
      };
      """
    And I enter "$obj->value"
    Then I should see output containing "String = \"test\""

  Scenario: Anonymous readonly class with methods
    Given I start the REPL
    When I enter the following code:
      """
      $counter = new readonly class(0) {
          public function __construct(
              private int $count
          ) {}

          public function get(): int {
              return $this->count;
          }
      };
      """
    And I enter "$counter->get()"
    Then I should see output containing "Int = 0"

  Scenario: Multiple readonly property modifications in clone
    Given I start the REPL
    When I enter the following code:
      """
      class Config {
          public function __construct(
              public readonly string $env,
              public readonly int $timeout,
              public readonly bool $debug
          ) {}

          public function __clone() {
              $this->env = 'cloned';
              $this->timeout = 60;
              $this->debug = false;
          }
      }
      """
    And I enter "$c1 = new Config('prod', 30, true)"
    And I enter "$c2 = clone $c1"
    And I enter "$c2->env"
    Then I should see output containing "String = \"cloned\""
    When I enter "$c2->timeout"
    Then I should see output containing "Int = 60"
    When I enter "$c2->debug"
    Then I should see output containing "Bool = false"

  Scenario: Clone with conditional readonly modification
    Given I start the REPL
    When I enter the following code:
      """
      class Item {
          public function __construct(
              public readonly string $id,
              public readonly int $version
          ) {}

          public function __clone() {
              if ($this->version < 10) {
                  $this->version = $this->version + 1;
              }
          }
      }
      """
    And I enter "$item = new Item('abc', 5)"
    And I enter "$cloned = clone $item"
    And I enter "$cloned->version"
    Then I should see output containing "Int = 6"

Feature: Lazy Objects (PHP 8.4)
  As a PHP developer
  I want to create lazy objects using ReflectionClass
  So that I can defer expensive initialization

  Scenario: Create lazy object with newLazyGhost
    Given I start the REPL
    When I enter the following code:
      """
      class ExpensiveResource {
          private string $data = '';

          public function __construct() {
              $this->data = 'loaded';
          }

          public function getData(): string {
              return $this->data;
          }
      }
      """
    And I enter the following code:
      """
      $reflector = new ReflectionClass('ExpensiveResource');
      $lazy = $reflector->newLazyGhost(function ($object) {
          $object->__construct();
      });
      """
    And I enter "$lazy->getData()"
    Then I should see output containing "String = \"loaded\""

  Scenario: Lazy object initialization happens once
    Given I start the REPL
    When I enter the following code:
      """
      class Counter {
          private static int $initCount = 0;
          private int $value;

          public function __construct() {
              self::$initCount++;
              $this->value = self::$initCount;
          }

          public function getValue(): int {
              return $this->value;
          }

          public static function getInitCount(): int {
              return self::$initCount;
          }
      }
      """
    And I enter the following code:
      """
      $reflector = new ReflectionClass('Counter');
      $lazy = $reflector->newLazyGhost(function ($object) {
          $object->__construct();
      });
      """
    And I enter "$lazy->getValue()"
    And I enter "$lazy->getValue()"
    And I enter "Counter::getInitCount()"
    Then I should see output containing "Int = 1"

  Scenario: newLazyProxy for delegation pattern
    Given I start the REPL
    When I enter the following code:
      """
      class Service {
          public function process(): string {
              return 'processed';
          }
      }
      """
    And I enter the following code:
      """
      $reflector = new ReflectionClass('Service');
      $proxy = $reflector->newLazyProxy(function () {
          return new Service();
      });
      """
    And I enter "$proxy->process()"
    Then I should see output containing "String = \"processed\""

  Scenario: Check if object is lazy with isUninitializedLazyObject
    Given I start the REPL
    When I enter the following code:
      """
      class Simple {
          public string $value = 'default';
      }
      """
    And I enter the following code:
      """
      $reflector = new ReflectionClass('Simple');
      $lazy = $reflector->newLazyGhost(function ($object) {
          $object->__construct();
      });
      """
    And I enter "(new ReflectionClass($lazy))->isUninitializedLazyObject($lazy)"
    Then I should see output containing "Bool = true"

  Scenario: Lazy object becomes initialized after access
    Given I start the REPL
    When I enter the following code:
      """
      class Data {
          public string $name = 'test';
      }
      """
    And I enter the following code:
      """
      $reflector = new ReflectionClass('Data');
      $lazy = $reflector->newLazyGhost(function ($object) {
          $object->__construct();
      });
      """
    And I enter "$before = (new ReflectionClass($lazy))->isUninitializedLazyObject($lazy)"
    And I enter "$lazy->name"
    And I enter "$after = (new ReflectionClass($lazy))->isUninitializedLazyObject($lazy)"
    And I enter "$before && !$after"
    Then I should see output containing "Bool = true"

  Scenario: Initialize lazy object explicitly
    Given I start the REPL
    When I enter the following code:
      """
      class Item {
          private int $id = 0;

          public function __construct() {
              $this->id = 42;
          }

          public function getId(): int {
              return $this->id;
          }
      }
      """
    And I enter the following code:
      """
      $reflector = new ReflectionClass('Item');
      $lazy = $reflector->newLazyGhost(function ($object) {
          $object->__construct();
      });
      """
    And I enter "(new ReflectionClass($lazy))->initializeLazyObject($lazy)"
    And I enter "$lazy->getId()"
    Then I should see output containing "Int = 42"

  Scenario: Reset lazy object to uninitialized state
    Given I start the REPL
    When I enter the following code:
      """
      class Resettable {
          public int $counter = 0;

          public function increment(): void {
              $this->counter++;
          }
      }
      """
    And I enter the following code:
      """
      $reflector = new ReflectionClass('Resettable');
      $lazy = $reflector->newLazyGhost(function ($object) {
          $object->__construct();
      });
      """
    And I enter "$lazy->increment()"
    And I enter "(new ReflectionClass($lazy))->resetAsLazyGhost($lazy, function ($object) { $object->__construct(); })"
    And I enter "(new ReflectionClass($lazy))->isUninitializedLazyObject($lazy)"
    Then I should see output containing "Bool = true"

  Scenario: Lazy object with dependencies
    Given I start the REPL
    When I enter the following code:
      """
      class Database {
          public function query(): array {
              return ['result' => 'data'];
          }
      }
      """
    And I enter the following code:
      """
      class Repository {
          private Database $db;

          public function __construct(Database $db) {
              $this->db = $db;
          }

          public function find(): array {
              return $this->db->query();
          }
      }
      """
    And I enter the following code:
      """
      $db = new Database();
      $reflector = new ReflectionClass('Repository');
      $lazyRepo = $reflector->newLazyGhost(function ($object) use ($db) {
          $object->__construct($db);
      });
      """
    And I enter "$result = $lazyRepo->find()"
    And I enter "$result['result']"
    Then I should see output containing "String = \"data\""

Feature: Asymmetric Visibility (PHP 8.4)
  As a PHP developer
  I want to use asymmetric visibility modifiers on properties
  So that I can have different visibility for reading and writing

  Scenario: Public property with private set
    Given I start the REPL
    When I enter the following code:
      """
      class User {
          public private(set) string $name;

          public function __construct(string $name) {
              $this->name = $name;
          }

          public function rename(string $newName): void {
              $this->name = $newName;
          }
      }
      """
    And I enter "$user = new User('Alice')"
    And I enter "$user->name"
    Then I should see output containing "String = \"Alice\""
    When I enter "$user->rename('Bob')"
    And I enter "$user->name"
    Then I should see output containing "String = \"Bob\""

  Scenario: Public property with protected set
    Given I start the REPL
    When I enter the following code:
      """
      class Account {
          public protected(set) float $balance;

          public function __construct(float $balance) {
              $this->balance = $balance;
          }
      }
      """
    And I enter the following code:
      """
      class SavingsAccount extends Account {
          public function deposit(float $amount): void {
              $this->balance += $amount;
          }
      }
      """
    And I enter "$acc = new SavingsAccount(100.0)"
    And I enter "$acc->balance"
    Then I should see output containing "Float = 100"
    When I enter "$acc->deposit(50.0)"
    And I enter "$acc->balance"
    Then I should see output containing "Float = 150"

  Scenario: Protected property with private set
    Given I start the REPL
    When I enter the following code:
      """
      class BaseEntity {
          protected private(set) int $id;

          public function __construct(int $id) {
              $this->id = $id;
          }

          public function getId(): int {
              return $this->id;
          }
      }
      """
    And I enter the following code:
      """
      class Entity extends BaseEntity {
          public function checkId(): int {
              return $this->id;
          }
      }
      """
    And I enter "$entity = new Entity(42)"
    And I enter "$entity->getId()"
    Then I should see output containing "Int = 42"
    When I enter "$entity->checkId()"
    Then I should see output containing "Int = 42"

  Scenario: Multiple properties with different asymmetric visibility
    Given I start the REPL
    When I enter the following code:
      """
      class Product {
          public private(set) string $name;
          public protected(set) float $price;
          public private(set) int $stock;

          public function __construct(string $name, float $price, int $stock) {
              $this->name = $name;
              $this->price = $price;
              $this->stock = $stock;
          }

          public function updateStock(int $newStock): void {
              $this->stock = $newStock;
          }
      }
      """
    And I enter "$p = new Product('Widget', 19.99, 100)"
    And I enter "$p->name"
    Then I should see output containing "String = \"Widget\""
    When I enter "$p->price"
    Then I should see output containing "Float = 19.99"
    When I enter "$p->stock"
    Then I should see output containing "Int = 100"

  Scenario: Asymmetric visibility with property hooks
    Given I start the REPL
    When I enter the following code:
      """
      class Counter {
          public private(set) int $count {
              get => $this->count;
              set => $this->count = max(0, $value);
          }

          public function __construct() {
              $this->count = 0;
          }

          public function increment(): void {
              $this->count++;
          }
      }
      """
    And I enter "$counter = new Counter()"
    And I enter "$counter->count"
    Then I should see output containing "Int = 0"
    When I enter "$counter->increment()"
    And I enter "$counter->count"
    Then I should see output containing "Int = 1"

  Scenario: Readonly property with asymmetric visibility semantics
    Given I start the REPL
    When I enter the following code:
      """
      class Config {
          public private(set) readonly string $environment;

          public function __construct(string $env) {
              $this->environment = $env;
          }
      }
      """
    And I enter "$config = new Config('production')"
    And I enter "$config->environment"
    Then I should see output containing "String = \"production\""

  Scenario: Promoted property with asymmetric visibility
    Given I start the REPL
    When I enter the following code:
      """
      class Task {
          public function __construct(
              public private(set) string $title,
              public protected(set) string $status = 'pending'
          ) {}

          public function complete(): void {
              $this->status = 'completed';
          }
      }
      """
    And I enter "$task = new Task('Write tests')"
    And I enter "$task->title"
    Then I should see output containing "String = \"Write tests\""
    When I enter "$task->status"
    Then I should see output containing "String = \"pending\""
    When I enter "$task->complete()"
    And I enter "$task->status"
    Then I should see output containing "String = \"completed\""

  Scenario: Asymmetric visibility in class hierarchy
    Given I start the REPL
    When I enter the following code:
      """
      abstract class BaseDocument {
          public private(set) int $createdAt;

          public function __construct() {
              $this->createdAt = time();
          }
      }
      """
    And I enter the following code:
      """
      class Document extends BaseDocument {
          public function getCreatedAt(): int {
              return $this->createdAt;
          }
      }
      """
    And I enter "$doc = new Document()"
    And I enter "$doc->createdAt > 0"
    Then I should see output containing "Bool = true"

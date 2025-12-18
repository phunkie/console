@php84
Feature: Chaining Without Parentheses (PHP 8.4)
  As a PHP developer
  I want to chain method calls on new objects without extra parentheses
  So that I can write more concise code

  Scenario: Simple method chaining on new instance
    Given I start the REPL
    When I enter the following code:
      """
      class Calculator {
          private int $value = 0;

          public function add(int $n): self {
              $this->value += $n;
              return $this;
          }

          public function getValue(): int {
              return $this->value;
          }
      }
      """
    And I enter "new Calculator()->add(5)->getValue()"
    Then I should see output containing "Int = 5"

  Scenario: Property access on new instance
    Given I start the REPL
    When I enter the following code:
      """
      class Point {
          public function __construct(
              public int $x = 0,
              public int $y = 0
          ) {}
      }
      """
    And I enter "new Point(10, 20)->x"
    Then I should see output containing "Int = 10"

  Scenario: Multiple method calls in chain
    Given I start the REPL
    When I enter the following code:
      """
      class StringBuilder {
          private string $str = '';

          public function append(string $s): self {
              $this->str .= $s;
              return $this;
          }

          public function toString(): string {
              return $this->str;
          }
      }
      """
    And I enter "new StringBuilder()->append('Hello')->append(' ')->append('World')->toString()"
    Then I should see output containing "String = \"Hello World\""

  Scenario: Static method call on new instance
    Given I start the REPL
    When I enter the following code:
      """
      class Factory {
          public static function create(): self {
              return new self();
          }

          public function getId(): string {
              return 'instance';
          }
      }
      """
    And I enter "Factory::create()->getId()"
    Then I should see output containing "String = \"instance\""

  Scenario: Chaining with constructor arguments
    Given I start the REPL
    When I enter the following code:
      """
      class User {
          public function __construct(
              private string $name
          ) {}

          public function getName(): string {
              return $this->name;
          }

          public function greet(): string {
              return "Hello, {$this->name}!";
          }
      }
      """
    And I enter "new User('Alice')->greet()"
    Then I should see output containing "String = \"Hello, Alice!\""

  Scenario: Nested instantiation and chaining
    Given I start the REPL
    When I enter the following code:
      """
      class Inner {
          public function getValue(): int {
              return 42;
          }
      }
      """
    And I enter the following code:
      """
      class Outer {
          public function getInner(): Inner {
              return new Inner();
          }
      }
      """
    And I enter "new Outer()->getInner()->getValue()"
    Then I should see output containing "Int = 42"

  Scenario: Chaining with fluent interface
    Given I start the REPL
    When I enter the following code:
      """
      class Query {
          private array $conditions = [];

          public function where(string $field, mixed $value): self {
              $this->conditions[$field] = $value;
              return $this;
          }

          public function count(): int {
              return count($this->conditions);
          }
      }
      """
    And I enter "new Query()->where('status', 'active')->where('type', 'user')->count()"
    Then I should see output containing "Int = 2"

  Scenario: Chaining with nullsafe operator
    Given I start the REPL
    When I enter the following code:
      """
      class Maybe {
          public function __construct(
              private mixed $value = null
          ) {}

          public function get(): mixed {
              return $this->value;
          }
      }
      """
    And I enter "new Maybe('test')?->get()"
    Then I should see output containing "String = \"test\""

  Scenario: Property hooks with chaining
    Given I start the REPL
    When I enter the following code:
      """
      class Counter {
          public int $count {
              get => $this->count;
              set => $this->count = max(0, $value);
          }

          public function __construct() {
              $this->count = 0;
          }

          public function increment(): self {
              $this->count++;
              return $this;
          }
      }
      """
    And I enter "new Counter()->increment()->increment()->count"
    Then I should see output containing "Int = 2"

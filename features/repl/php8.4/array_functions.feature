@php84
Feature: New Array Functions (PHP 8.4)
  As a PHP developer
  I want to use new array functions in the REPL
  So that I can work with arrays more efficiently

  Scenario: array_find() with matching element
    Given I start the REPL
    When I enter "$numbers = [1, 2, 3, 4, 5]"
    And I enter "array_find($numbers, fn($n) => $n > 3)"
    Then I should see output containing "Int = 4"

  Scenario: array_find() with no match
    Given I start the REPL
    When I enter "$numbers = [1, 2, 3]"
    And I enter "array_find($numbers, fn($n) => $n > 10)"
    Then I should see output containing "Null = null"

  Scenario: array_find() with associative array
    Given I start the REPL
    When I enter the following code:
      """
      $users = [
          'alice' => ['age' => 25, 'city' => 'NYC'],
          'bob' => ['age' => 30, 'city' => 'LA'],
          'charlie' => ['age' => 35, 'city' => 'SF']
      ];
      """
    And I enter "$found = array_find($users, fn($u) => $u['age'] > 30)"
    And I enter "$found['age']"
    Then I should see output containing "Int = 35"

  Scenario: array_find_key() with matching element
    Given I start the REPL
    When I enter "$data = ['a' => 1, 'b' => 2, 'c' => 3]"
    And I enter "array_find_key($data, fn($v) => $v > 1)"
    Then I should see output containing "String = \"b\""

  Scenario: array_find_key() with no match
    Given I start the REPL
    When I enter "$data = ['a' => 1, 'b' => 2]"
    And I enter "array_find_key($data, fn($v) => $v > 10)"
    Then I should see output containing "Null = null"

  Scenario: array_find_key() with numeric keys
    Given I start the REPL
    When I enter "$numbers = [10, 20, 30, 40]"
    And I enter "array_find_key($numbers, fn($n) => $n === 30)"
    Then I should see output containing "Int = 2"

  Scenario: array_any() with matching element
    Given I start the REPL
    When I enter "$numbers = [1, 2, 3, 4]"
    And I enter "array_any($numbers, fn($n) => $n > 3)"
    Then I should see output containing "Bool = true"

  Scenario: array_any() with no matches
    Given I start the REPL
    When I enter "$numbers = [1, 2, 3]"
    And I enter "array_any($numbers, fn($n) => $n > 10)"
    Then I should see output containing "Bool = false"

  Scenario: array_any() with empty array
    Given I start the REPL
    When I enter "array_any([], fn($n) => true)"
    Then I should see output containing "Bool = false"

  Scenario: array_all() with all matching
    Given I start the REPL
    When I enter "$numbers = [2, 4, 6, 8]"
    And I enter "array_all($numbers, fn($n) => $n % 2 === 0)"
    Then I should see output containing "Bool = true"

  Scenario: array_all() with partial match
    Given I start the REPL
    When I enter "$numbers = [2, 3, 4]"
    And I enter "array_all($numbers, fn($n) => $n % 2 === 0)"
    Then I should see output containing "Bool = false"

  Scenario: array_all() with empty array
    Given I start the REPL
    When I enter "array_all([], fn($n) => false)"
    Then I should see output containing "Bool = true"

  Scenario: array_all() with associative array
    Given I start the REPL
    When I enter the following code:
      """
      $products = [
          'apple' => ['price' => 1.50, 'stock' => 10],
          'banana' => ['price' => 0.80, 'stock' => 15],
          'orange' => ['price' => 1.20, 'stock' => 8]
      ];
      """
    And I enter "array_all($products, fn($p) => $p['stock'] > 5)"
    Then I should see output containing "Bool = true"

  Scenario: Chaining array functions
    Given I start the REPL
    When I enter "$numbers = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]"
    And I enter "array_any($numbers, fn($n) => $n > 5 && $n % 2 === 0)"
    Then I should see output containing "Bool = true"

  Scenario: array_find() with object array
    Given I start the REPL
    When I enter the following code:
      """
      class Person {
          public function __construct(
              public string $name,
              public int $age
          ) {}
      }
      """
    And I enter "$people = [new Person('Alice', 25), new Person('Bob', 30)]"
    And I enter "$found = array_find($people, fn($p) => $p->age > 25)"
    And I enter "$found->name"
    Then I should see output containing "String = \"Bob\""

  Scenario: array_find_key() with complex predicate
    Given I start the REPL
    When I enter the following code:
      """
      $inventory = [
          'laptops' => ['count' => 5, 'value' => 5000],
          'phones' => ['count' => 10, 'value' => 3000],
          'tablets' => ['count' => 8, 'value' => 2000]
      ];
      """
    And I enter "array_find_key($inventory, fn($item) => $item['count'] > 7 && $item['value'] > 2500)"
    Then I should see output containing "String = \"phones\""

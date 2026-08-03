Feature: First-Class Callable Syntax
  As a PHP developer
  I want to use first-class callable syntax in the REPL
  So that I can create callables using the ... syntax

  Scenario: First-class callable from built-in function
    Given I start the REPL
    When I enter "$f = strlen(...)"
    And I enter "$f('hello')"
    Then I should see output containing "Int = 5"

  Scenario: First-class callable from user-defined function
    Given I start the REPL
    When I enter the following code:
      """
      function add($a, $b) {
          return $a + $b;
      }
      """
    And I enter "$addFunc = add(...)"
    And I enter "$addFunc(2, 3)"
    Then I should see output containing "Int = 5"

  Scenario: First-class callable from user-defined function passed to array_map
    Given I start the REPL
    When I enter the following code:
      """
      function double($n) {
          return $n * 2;
      }
      """
    And I enter "array_map(double(...), [1, 2, 3])"
    Then I should see output containing "Array = [2, 4, 6]"

  Scenario: First-class callable from static method
    Given I start the REPL
    When I enter the following code:
      """
      class Math {
          public static function multiply($x, $y) {
              return $x * $y;
          }
      }
      """
    And I enter "$mult = Math::multiply(...)"
    And I enter "$mult(4, 5)"
    Then I should see output containing "Int = 20"

  Scenario: First-class callable from instance method
    Given I start the REPL
    When I enter the following code:
      """
      class Counter {
          private $count = 0;
          public function increment() {
              return ++$this->count;
          }
      }
      """
    And I enter "$c = new Counter()"
    And I enter "$inc = $c->increment(...)"
    And I enter "$inc()"
    Then I should see output containing "Int = 1"
    When I enter "$inc()"
    Then I should see output containing "Int = 2"

  Scenario: First-class callable stored in array
    Given I start the REPL
    When I enter "$funcs = [strlen(...), strtoupper(...)]"
    And I enter "$funcs[0]('test')"
    Then I should see output containing "Int = 4"
    When I enter "$funcs[1]('hello')"
    Then I should see output containing "String = \"HELLO\""

  Scenario: First-class callable with array_map
    Given I start the REPL
    When I enter "$result = array_map(strtoupper(...), ['a', 'b', 'c'])"
    Then I should see output containing "Array = [\"A\", \"B\", \"C\"]"

  Scenario: First-class callable comparison
    Given I start the REPL
    When I enter "$f1 = strlen(...)"
    And I enter "$f2 = strlen(...)"
    And I enter "$f1 === $f2"
    Then I should see output containing "Boolean = false"

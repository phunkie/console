Feature: Trailing Comma Support
  As a PHP developer
  I want to use trailing commas in arrays and function calls
  So that I can write more maintainable code with PHP 8.0+ syntax

  Scenario: Array literal with trailing comma
    Given I start the REPL
    When I enter "[1, 2, 3,]"
    Then I should see output containing "$var0: Array = [1, 2, 3]"

  Scenario: Empty array with no trailing comma
    Given I start the REPL
    When I enter "[]"
    Then I should see output containing "$var0: Array = []"

  Scenario: Single element array with trailing comma
    Given I start the REPL
    When I enter "[42,]"
    Then I should see output containing "$var0: Array = [42]"

  Scenario: Multi-line array with trailing comma
    Given I start the REPL
    When I enter "[1, 2, 3, 4, 5,]"
    Then I should see output containing "$var0: Array = [1, 2, 3, 4, 5]"

  Scenario: Associative array with trailing comma
    Given I start the REPL
    When I enter '["a" => 1, "b" => 2, "c" => 3,]'
    Then I should see output containing '"a" => 1'
    And I should see output containing '"b" => 2'
    And I should see output containing '"c" => 3'

  Scenario: Mixed associative array with trailing comma
    Given I start the REPL
    When I enter '["name" => "John", "age" => 30,]'
    Then I should see output containing '"name" => "John"'
    And I should see output containing '"age" => 30'

  Scenario: Nested array with trailing commas
    Given I start the REPL
    When I enter "[[1, 2,], [3, 4,],]"
    Then I should see output containing "$var0: Array = [[1, 2], [3, 4]]"

  Scenario: Built-in function call with trailing comma
    Given I start the REPL
    When I enter "max(1, 2, 3,)"
    Then I should see output containing "$var0: Int = 3"

  Scenario: Built-in function with single argument and trailing comma
    Given I start the REPL
    When I enter "strlen('hello',)"
    Then I should see output containing "$var0: Int = 5"

  Scenario: User-defined function call with trailing comma
    Given I start the REPL
    When I enter "function add($a, $b, $c) { return $a + $b + $c; }"
    And I enter "add(1, 2, 3,)"
    Then I should see output containing "$var0: Int = 6"

  Scenario: User-defined function with single parameter and trailing comma
    Given I start the REPL
    When I enter "function double($x) { return $x * 2; }"
    And I enter "double(5,)"
    Then I should see output containing "$var0: Int = 10"

  Scenario: Method call with trailing comma
    Given I start the REPL
    When I enter "class Calculator { public function add($a, $b) { return $a + $b; } }"
    And I enter "$calc = new Calculator()"
    And I enter "$calc->add(10, 20,)"
    Then I should see output containing "$var0: Int = 30"

  Scenario: Static method call with trailing comma
    Given I start the REPL
    When I enter "class Math { public static function multiply($a, $b, $c) { return $a * $b * $c; } }"
    And I enter "Math::multiply(2, 3, 4,)"
    Then I should see output containing "$var0: Int = 24"

  Scenario: Function call with named arguments and trailing comma
    Given I start the REPL
    When I enter "function greet($name, $greeting) { return $greeting . ', ' . $name; }"
    And I enter "greet(name: 'Alice', greeting: 'Hello',)"
    Then I should see output containing '$var0: String = "Hello, Alice"'

  Scenario: Array with nested function calls with trailing commas
    Given I start the REPL
    When I enter "function square($x) { return $x * $x; }"
    And I enter "[square(2,), square(3,), square(4,),]"
    Then I should see output containing "$var0: Array = [4, 9, 16]"

  Scenario: Complex expression with multiple trailing commas
    Given I start the REPL
    When I enter "function sum(...$numbers) { return array_sum($numbers); }"
    And I enter "$arr = [1, 2, 3,]"
    And I enter "sum(...$arr,)"
    Then I should see output containing "$var0: Int = 6"

  Scenario: Chained method calls with trailing commas
    Given I start the REPL
    When I enter "class Builder { public function setValue($v) { $this->value = $v; return $this; } public function getValue() { return $this->value; } }"
    And I enter "$builder = new Builder()"
    And I enter "$builder->setValue(42,)->getValue()"
    Then I should see output containing "$var0: Int = 42"

  Scenario: Array with string values and trailing comma
    Given I start the REPL
    When I enter '["apple", "banana", "cherry",]'
    Then I should see output containing '"apple"'
    And I should see output containing '"banana"'
    And I should see output containing '"cherry"'

  Scenario: Constructor call with trailing comma
    Given I start the REPL
    When I enter "class Point { public function __construct(public $x, public $y) {} }"
    And I enter "new Point(10, 20,)"
    Then I should see output containing "$var0: Point"

  Scenario: Variable function call with trailing comma
    Given I start the REPL
    When I enter "function triple($n) { return $n * 3; }"
    And I enter "$fn = 'triple'"
    And I enter "$fn(7,)"
    Then I should see output containing "$var0: Int = 21"

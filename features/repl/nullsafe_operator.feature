Feature: Nullsafe Operator Support
  As a PHP developer
  I want to use the nullsafe operator (?->) in the REPL
  So that I can safely access properties and methods on potentially null objects

  Scenario: Nullsafe method call on null returns null
    Given I start the REPL
    When I enter "$x = null"
    And I enter "$x?->toString()"
    Then I should see output containing "$var0: Null = null"

  Scenario: Nullsafe method call on object executes method
    Given I start the REPL
    When I enter "class TestClass { public function getValue() { return 42; } }"
    And I enter "$obj = new TestClass()"
    And I enter "$obj?->getValue()"
    Then I should see output containing "$var0: Int = 42"

  Scenario: Nullsafe property fetch on null returns null
    Given I start the REPL
    When I enter "$x = null"
    And I enter "$x?->property"
    Then I should see output containing "$var0: Null = null"

  Scenario: Nullsafe property fetch on object returns property value
    Given I start the REPL
    When I enter "class Person { public $name = \"Alice\"; }"
    And I enter "$person = new Person()"
    And I enter "$person?->name"
    Then I should see output containing "$var0: String = \"Alice\""

  Scenario: Chained nullsafe operators with null
    Given I start the REPL
    When I enter "class Address { public $street = \"Main St\"; }"
    And I enter "class User { public $address = null; }"
    And I enter "$user = new User()"
    And I enter "$user?->address?->street"
    Then I should see output containing "$var0: Null = null"

  Scenario: Chained nullsafe operators with valid object
    Given I start the REPL
    When I enter "class Address { public $street = \"Main St\"; }"
    And I enter "class User { public function __construct() { $this->address = new Address(); } public $address; }"
    And I enter "$user = new User()"
    And I enter "$user?->address?->street"
    Then I should see output containing "$var0: String = \"Main St\""

  Scenario: Nullsafe method call with arguments
    Given I start the REPL
    When I enter "class Calculator { public function add($a, $b) { return $a + $b; } }"
    And I enter "$calc = new Calculator()"
    And I enter "$calc?->add(5, 3)"
    Then I should see output containing "$var0: Int = 8"

  Scenario: Nullsafe method call with arguments on null
    Given I start the REPL
    When I enter "$calc = null"
    And I enter "$calc?->add(5, 3)"
    Then I should see output containing "$var0: Null = null"

  Scenario: Mixed regular and nullsafe operators
    Given I start the REPL
    When I enter "class Result { public function __construct($v) { $this->value = $v; } public $value; }"
    And I enter "class Container { public function __construct($r) { $this->result = $r; } public $result; }"
    And I enter "$container = new Container(new Result(100))"
    And I enter "$container->result?->value"
    Then I should see output containing "$var0: Int = 100"

  Scenario: Nullsafe operator with Some monad
    Given I start the REPL
    When I enter "$x = Some(42)"
    And I enter "$x?->getOrElse(0)"
    Then I should see output containing "$var0: Int = 42"

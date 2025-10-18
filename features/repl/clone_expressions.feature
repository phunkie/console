Feature: Clone expressions
  As a REPL user
  In order to create shallow copies of objects
  I should be able to use the clone keyword

  Scenario: Basic clone of simple object
    Given I am running the repl
    When I type "$obj = new class { public $value = 42; };"
    And I press enter
    And I type "$copy = clone $obj;"
    And I press enter
    Then I should see "$copy: class@anonymous"

  Scenario: Clone preserves property values but creates new instance
    Given I am running the repl
    When I type "$original = new class { public $value = 42; };"
    And I press enter
    And I type "$copy = clone $original;"
    And I press enter
    And I type "$copy->value"
    And I press enter
    Then I should see "$var0: Int = 42"
    When I type "$original === $copy"
    And I press enter
    Then I should see "$var1: Bool = false"

  Scenario: Clone with __clone magic method
    Given I am running the repl
    When I type "$obj = new class { public $count = 0; public function __clone() { $this->count++; } };"
    And I press enter
    And I type "$obj->count"
    And I press enter
    Then I should see "$var0: Int = 0"
    When I type "$copy = clone $obj;"
    And I press enter
    And I type "$copy->count"
    And I press enter
    Then I should see "$var1: Int = 1"

  Scenario: Clone of object with properties
    Given I am running the repl
    When I type "class Person { public function __construct(public string $name, public int $age) {} }"
    And I press enter
    And I type "$person = new Person('Alice', 30);"
    And I press enter
    And I type "$clone = clone $person;"
    And I press enter
    And I type "$clone->name"
    And I press enter
    Then I should see "$var0: String = \"Alice\""
    When I type "$clone->age"
    And I press enter
    Then I should see "$var1: Int = 30"

  Scenario: Clone creates independent copy for scalar properties
    Given I am running the repl
    When I type "$obj = new class { public $value = 10; };"
    And I press enter
    And I type "$copy = clone $obj;"
    And I press enter
    And I type "$copy->value = 20;"
    And I press enter
    And I type "$obj->value"
    And I press enter
    Then I should see "$var0: Int = 10"
    When I type "$copy->value"
    And I press enter
    Then I should see "$var1: Int = 20"

  Scenario: Error when trying to clone non-object (integer)
    Given I am running the repl
    When I type "$num = 42;"
    And I press enter
    And I type "clone $num"
    And I press enter
    Then I should see "Error: Cannot clone non-object (integer)"

  Scenario: Error when trying to clone non-object (string)
    Given I am running the repl
    When I type "$str = \"hello\";"
    And I press enter
    And I type "clone $str"
    And I press enter
    Then I should see "Error: Cannot clone non-object (string)"

  Scenario: Error when trying to clone non-object (array)
    Given I am running the repl
    When I type "$arr = [1, 2, 3];"
    And I press enter
    And I type "clone $arr"
    And I press enter
    Then I should see "Error: Cannot clone non-object (array)"

  Scenario: Clone demonstrates shallow copy behavior
    Given I am running the repl
    When I type "$inner = new class { public $value = 'original'; };"
    And I press enter
    And I type "$outer = new class($inner) { public function __construct(public $nested) {} };"
    And I press enter
    And I type "$copy = clone $outer;"
    And I press enter
    And I type "$copy->nested->value = 'modified';"
    And I press enter
    And I type "$outer->nested->value"
    And I press enter
    Then I should see "$var0: String = \"modified\""

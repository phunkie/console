Feature: Anonymous classes
  As a REPL user
  In order to quickly test object-oriented code
  I should be able to create anonymous classes

  Scenario: Simple anonymous class
    Given I am running the repl
    When I type "$obj = new class { public function test() { return \"works\"; } };"
    And I press enter
    Then I should see "$obj: class@anonymous"

  Scenario: Calling method on anonymous class
    Given I am running the repl
    When I type "$obj = new class { public function test() { return \"works\"; } };"
    And I press enter
    And I type "$obj->test()"
    And I press enter
    Then I should see "$var0: String = \"works\""

  Scenario: Anonymous class with constructor
    Given I am running the repl
    When I type "$obj = new class(42) { public function __construct(public int $value) {} public function getValue() { return $this->value; } };"
    And I press enter
    And I type "$obj->getValue()"
    And I press enter
    Then I should see "$var0: Int = 42"

  Scenario: Anonymous class implementing interface
    Given I am running the repl
    When I type "interface Greetable { public function greet(): string; }"
    And I press enter
    And I type "$obj = new class implements Greetable { public function greet(): string { return \"Hello!\"; } };"
    And I press enter
    And I type "$obj->greet()"
    And I press enter
    Then I should see "$var0: String = \"Hello!\""

  Scenario: Anonymous class with properties and methods
    Given I am running the repl
    When I type "$counter = new class { private int $count = 0; public function increment() { $this->count++; return $this->count; } };"
    And I press enter
    And I type "$counter->increment()"
    And I press enter
    Then I should see "$var0: Int = 1"
    When I type "$counter->increment()"
    And I press enter
    Then I should see "$var1: Int = 2"

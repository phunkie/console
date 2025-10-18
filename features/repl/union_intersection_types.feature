Feature: Union and intersection types
  As a repl runner
  In order to support modern PHP type systems
  I should be able to define and use functions with union and intersection types

  Scenario: Function with union type parameter accepts int
    Given I am running the repl
    When I type "function foo(int|string $x): int { return 42; }"
    And I press enter
    And I type "foo(10)"
    And I press enter
    Then I should see "$var0: Int = 42"

  Scenario: Function with union type parameter accepts string
    Given I am running the repl
    When I type "function foo(int|string $x): int { return 42; }"
    And I press enter
    And I type "foo(\"hello\")"
    And I press enter
    Then I should see "$var0: Int = 42"

  Scenario: Function with union type parameter rejects wrong type
    Given I am running the repl
    When I type "function foo(int|string $x): int { return 42; }"
    And I press enter
    And I type "foo(true)"
    And I press enter
    Then I should see "TypeError"
    And I should see "must be of type int|string, bool given"

  Scenario: Function with union type return value
    Given I am running the repl
    When I type "function bar(bool $x): int|null { if ($x) { return 42; } return null; }"
    And I press enter
    And I type "bar(true)"
    And I press enter
    Then I should see "$var0: Int = 42"

  Scenario: Function with union type return value can be null
    Given I am running the repl
    When I type "function bar(bool $x): int|null { if ($x) { return 42; } return null; }"
    And I press enter
    And I type "bar(false)"
    And I press enter
    Then I should see "$var0: Null = null"

  Scenario: Function with intersection type parameter accepts object implementing both interfaces
    Given I am running the repl
    When I type "function baz(Countable&Traversable $x): int { return count($x); }"
    And I press enter
    And I type "baz(new ArrayObject([1, 2, 3]))"
    And I press enter
    Then I should see "$var0: Int = 3"

  Scenario: Function with intersection type parameter rejects object missing interface
    Given I am running the repl
    When I type "function baz(Countable&Traversable $x): int { return count($x); }"
    And I press enter
    And I type "class OnlyCountable implements Countable { public function count(): int { return 5; } }"
    And I press enter
    And I type "baz(new OnlyCountable())"
    And I press enter
    Then I should see "TypeError"
    And I should see "must be of type Countable&Traversable"

  Scenario: Function with complex union type
    Given I am running the repl
    When I type "function complex(int|string|bool $x): string { return \"ok\"; }"
    And I press enter
    And I type "complex(42)"
    And I press enter
    Then I should see "$var0: String = \"ok\""

  Scenario: Function with complex union type accepts multiple types
    Given I am running the repl
    When I type "function complex(int|string|bool $x): string { return \"ok\"; }"
    And I press enter
    And I type "complex(\"test\")"
    And I press enter
    Then I should see "$var0: String = \"ok\""

  Scenario: Function with complex union type accepts bool
    Given I am running the repl
    When I type "function complex(int|string|bool $x): string { return \"ok\"; }"
    And I press enter
    And I type "complex(false)"
    And I press enter
    Then I should see "$var0: String = \"ok\""

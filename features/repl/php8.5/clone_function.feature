Feature: Clone With Properties (PHP 8.5)
  As a PHP developer
  I want to clone an object and override properties in one step
  So that I can derive new values from immutable objects

  Scenario: Cloning without changes still works
    Given I start the REPL
    When I enter the following code:
      """
      class Point {
          public function __construct(public int $x = 1, public int $y = 2) {}
      }
      """
    And I enter "$a = new Point(5, 6)"
    And I enter "$b = clone $a"
    And I enter "$b->x"
    Then I should see output containing "Int = 5"

  Scenario: Cloning with a replaced property
    Given I start the REPL
    When I enter the following code:
      """
      class Point {
          public function __construct(public int $x = 1, public int $y = 2) {}
      }
      """
    And I enter "$a = new Point(5, 6)"
    And I enter "$b = clone($a, [\"x\" => 9])"
    And I enter "$b->x"
    Then I should see output containing "Int = 9"

  Scenario: Properties that are not replaced are carried over
    Given I start the REPL
    When I enter the following code:
      """
      class Point {
          public function __construct(public int $x = 1, public int $y = 2) {}
      }
      """
    And I enter "$a = new Point(5, 6)"
    And I enter "$b = clone($a, [\"x\" => 9])"
    And I enter "$b->y"
    Then I should see output containing "Int = 6"

  Scenario: The original object is left untouched
    Given I start the REPL
    When I enter the following code:
      """
      class Point {
          public function __construct(public int $x = 1, public int $y = 2) {}
      }
      """
    And I enter "$a = new Point(5, 6)"
    And I enter "$b = clone($a, [\"x\" => 9])"
    And I enter "$a->x"
    Then I should see output containing "Int = 5"

  Scenario: Cloning with named arguments
    Given I start the REPL
    When I enter the following code:
      """
      class Point {
          public function __construct(public int $x = 1, public int $y = 2) {}
      }
      """
    And I enter "$a = new Point(5, 6)"
    And I enter "$b = clone(object: $a, withProperties: [\"x\" => 9])"
    And I enter "$b->x"
    Then I should see output containing "Int = 9"

  Scenario: A readonly object derives a copy from inside its own scope
    Given I start the REPL
    When I enter the following code:
      """
      readonly class Money {
          public function __construct(public int $amount, public string $currency) {}

          public function withAmount(int $amount): static {
              return clone($this, ["amount" => $amount]);
          }
      }
      """
    And I enter "$price = new Money(100, \"GBP\")"
    And I enter "$discounted = $price->withAmount(80)"
    And I enter "$discounted->amount"
    Then I should see output containing "Int = 80"
    When I enter "$price->amount"
    Then I should see output containing "Int = 100"

  Scenario: Replacing a readonly property from outside the class is refused
    Given I start the REPL
    When I enter the following code:
      """
      readonly class Secret {
          public function __construct(public string $value) {}
      }
      """
    And I enter "$s = new Secret(\"hunter2\")"
    And I enter "clone($s, [\"value\" => \"leaked\"])"
    Then I should see an error containing "Cannot modify"

Feature: Override Attribute on Properties (PHP 8.5)
  As a PHP developer
  I want to mark a property as overriding a parent property
  So that a rename in the parent is caught at compile time

  Scenario: Property marked with Override
    Given I start the REPL
    When I enter the following code:
      """
      class Base {
          public int $size = 1;
      }
      """
    And I enter the following code:
      """
      class Derived extends Base {
          #[\Override] public int $size = 2;
      }
      """
    And I enter "$d = new Derived()"
    And I enter "$d->size"
    Then I should see output containing "Int = 2"

  Scenario: Override on a promoted property
    Given I start the REPL
    When I enter the following code:
      """
      class Shape {
          public string $label = "shape";
      }
      """
    And I enter the following code:
      """
      class Square extends Shape {
          public function __construct(#[\Override] public string $label = "square") {}
      }
      """
    And I enter "$s = new Square()"
    And I enter "$s->label"
    Then I should see output containing "String = \"square\""

  Scenario: Override still applies to methods
    Given I start the REPL
    When I enter the following code:
      """
      class Greeter {
          public function greet(): string { return "hello"; }
      }
      """
    And I enter the following code:
      """
      class LoudGreeter extends Greeter {
          #[\Override] public function greet(): string { return "HELLO"; }
      }
      """
    And I enter "(new LoudGreeter())->greet()"
    Then I should see output containing "String = \"HELLO\""

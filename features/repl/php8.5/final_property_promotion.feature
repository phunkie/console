Feature: Final Property Promotion (PHP 8.5)
  As a PHP developer
  I want to mark promoted constructor properties final
  So that subclasses cannot redeclare them

  Scenario: Final promoted property with explicit visibility
    Given I start the REPL
    When I enter the following code:
      """
      class Point {
          public function __construct(final public int $x = 1) {}
      }
      """
    And I enter "$p = new Point(9)"
    And I enter "$p->x"
    Then I should see output containing "Int = 9"

  Scenario: Final promoted property without explicit visibility defaults to public
    Given I start the REPL
    When I enter the following code:
      """
      class Tag {
          public function __construct(final string $name = "none") {}
      }
      """
    And I enter "$t = new Tag(\"release\")"
    And I enter "$t->name"
    Then I should see output containing "String = \"release\""

  Scenario: Final promoted property alongside a regular promoted property
    Given I start the REPL
    When I enter the following code:
      """
      class Range {
          public function __construct(
              final public int $from,
              public int $to,
          ) {}
      }
      """
    And I enter "$r = new Range(1, 10)"
    And I enter "$r->from"
    Then I should see output containing "Int = 1"

  Scenario: Final promoted property is readable through a method
    Given I start the REPL
    When I enter the following code:
      """
      class Temperature {
          public function __construct(final public float $celsius = 0.0) {}

          public function fahrenheit(): float {
              return $this->celsius * 9 / 5 + 32;
          }
      }
      """
    And I enter "$t = new Temperature(100.0)"
    And I enter "$t->fahrenheit()"
    Then I should see output containing "Float = 212"

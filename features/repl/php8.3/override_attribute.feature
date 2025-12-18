@php83
Feature: #[Override] Attribute (PHP 8.3)
  As a PHP developer
  I want to use the #[Override] attribute in the REPL
  So that I can ensure methods actually override parent methods

  Scenario: Override attribute on valid override
    Given I start the REPL
    When I enter the following code:
      """
      class ParentClass {
          public function test(): string {
              return "parent";
          }
      }
      """
    And I enter the following code:
      """
      class ChildClass extends ParentClass {
          #[Override]
          public function test(): string {
              return "child";
          }
      }
      """
    And I enter "$obj = new ChildClass()"
    And I enter "$obj->test()"
    Then I should see output containing "String = \"child\""

  Scenario: Override attribute with interface
    Given I start the REPL
    When I enter the following code:
      """
      interface Renderable {
          public function render(): string;
      }
      """
    And I enter the following code:
      """
      class Component implements Renderable {
          #[Override]
          public function render(): string {
              return "<div>Component</div>";
          }
      }
      """
    And I enter "$c = new Component()"
    And I enter "$c->render()"
    Then I should see output containing "String = \"<div>Component</div>\""

  # Note: #[Override] cannot be used with trait methods
  # Traits provide methods but are not considered parent methods
  # This would cause: "has #[\Override] attribute, but no matching parent method exists"

  Scenario: Multiple override attributes
    Given I start the REPL
    When I enter the following code:
      """
      class BaseClass {
          public function foo(): int { return 1; }
          public function bar(): int { return 2; }
      }
      """
    And I enter the following code:
      """
      class DerivedClass extends BaseClass {
          #[Override]
          public function foo(): int { return 10; }

          #[Override]
          public function bar(): int { return 20; }
      }
      """
    And I enter "$d = new DerivedClass()"
    And I enter "$d->foo()"
    Then I should see output containing "Int = 10"
    When I enter "$d->bar()"
    Then I should see output containing "Int = 20"

  Scenario: Override with abstract parent
    Given I start the REPL
    When I enter the following code:
      """
      abstract class AbstractController {
          abstract public function handle(): string;
      }
      """
    And I enter the following code:
      """
      class MyController extends AbstractController {
          #[Override]
          public function handle(): string {
              return "handled";
          }
      }
      """
    And I enter "$ctrl = new MyController()"
    And I enter "$ctrl->handle()"
    Then I should see output containing "String = \"handled\""

  Scenario: Override preserves visibility
    Given I start the REPL
    When I enter the following code:
      """
      class ParentClass {
          protected function getValue(): int {
              return 42;
          }
      }
      """
    And I enter the following code:
      """
      class ChildClass extends ParentClass {
          #[Override]
          protected function getValue(): int {
              return 100;
          }

          public function get(): int {
              return $this->getValue();
          }
      }
      """
    And I enter "$c = new ChildClass()"
    And I enter "$c->get()"
    Then I should see output containing "Int = 100"

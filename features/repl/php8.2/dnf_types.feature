Feature: DNF (Disjunctive Normal Form) Types (PHP 8.2)
  As a PHP developer
  I want to use DNF types in the REPL
  So that I can combine union and intersection types for precise type declarations

  Scenario: DNF type with union of intersection types
    Given I start the REPL
    When I enter the following code:
      """
      interface A {}
      """
    And I enter the following code:
      """
      interface B {}
      """
    And I enter the following code:
      """
      interface C {}
      """
    And I enter the following code:
      """
      function process((A&B)|C $input): string {
          return "Processed";
      }
      """
    Then I should see output containing "function process defined"

  Scenario: DNF type with intersection and string
    Given I start the REPL
    When I enter the following code:
      """
      interface Renderable {
          public function render(): string;
      }
      """
    And I enter the following code:
      """
      interface Cacheable {
          public function cache(): void;
      }
      """
    And I enter the following code:
      """
      function handle((Renderable&Cacheable)|string $item): void {
      }
      """
    Then I should see output containing "function handle defined"

  Scenario: DNF type with multiple intersection types
    Given I start the REPL
    When I enter the following code:
      """
      interface X {}
      """
    And I enter the following code:
      """
      interface Y {}
      """
    And I enter the following code:
      """
      interface Z {}
      """
    And I enter the following code:
      """
      function combine((X&Y)|(Y&Z)|string $data): mixed {
          return $data;
      }
      """
    Then I should see output containing "function combine defined"

  Scenario: DNF type on class property
    Given I start the REPL
    When I enter the following code:
      """
      interface Stringable {
          public function __toString(): string;
      }
      """
    And I enter the following code:
      """
      interface Jsonable {
          public function toJson(): string;
      }
      """
    And I enter the following code:
      """
      class Container {
          public function __construct(
              public (Stringable&Jsonable)|array $data
          ) {}
      }
      """
    Then I should see output containing "class Container defined"

  Scenario: DNF type with class intersection
    Given I start the REPL
    When I enter the following code:
      """
      class BaseClass {}
      """
    And I enter the following code:
      """
      interface Logger {}
      """
    And I enter the following code:
      """
      function log((BaseClass&Logger)|string $message): void {
      }
      """
    Then I should see output containing "function log defined"

  Scenario: DNF type in method return type
    Given I start the REPL
    When I enter the following code:
      """
      interface Foo {}
      """
    And I enter the following code:
      """
      interface Bar {}
      """
    And I enter the following code:
      """
      class Service {
          public function get(): (Foo&Bar)|null {
              return null;
          }
      }
      """
    And I enter "$service = new Service()"
    And I enter "$service->get()"
    Then I should see output containing "Null = null"

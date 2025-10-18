Feature: PHP 8 Attributes Support
  As a PHP developer
  I want to use PHP 8 attributes (annotations) in the REPL
  So that I can work with metadata and decorators on classes, methods, properties, and parameters

  Scenario: Define and use a basic attribute on a class
    Given I start the REPL
    When I enter the following code:
      """
      #[Attribute]
      class MyAttribute {
          public function __construct(public string $value = '') {}
      }
      """
    Then I should see output containing "class MyAttribute defined"
    When I enter the following code:
      """
      #[MyAttribute("test")]
      class TestClass {}
      """
    Then I should see output containing "class TestClass defined"

  Scenario: Attribute with table mapping
    Given I start the REPL
    When I enter the following code:
      """
      #[Attribute]
      class Table {
          public function __construct(public string $name) {}
      }
      """
    And I enter the following code:
      """
      #[Table("users")]
      class User {}
      """
    Then I should see output containing "class User defined"

  Scenario: Multiple stacked attributes on a class
    Given I start the REPL
    When I enter the following code:
      """
      #[Attribute]
      class Entity {}
      """
    And I enter the following code:
      """
      #[Attribute]
      class Table {
          public function __construct(public string $name) {}
      }
      """
    And I enter the following code:
      """
      #[Entity]
      #[Table("products")]
      class Product {}
      """
    Then I should see output containing "class Product defined"

  Scenario: Multiple comma-separated attributes
    Given I start the REPL
    When I enter the following code:
      """
      #[Attribute]
      class Entity {}
      """
    And I enter the following code:
      """
      #[Attribute]
      class Cacheable {}
      """
    And I enter the following code:
      """
      #[Entity, Cacheable]
      class Category {}
      """
    Then I should see output containing "class Category defined"

  Scenario: Attributes on class properties
    Given I start the REPL
    When I enter the following code:
      """
      #[Attribute]
      class Column {
          public function __construct(public string $name, public string $type = 'string') {}
      }
      """
    And I enter the following code:
      """
      class Article {
          #[Column("id", "integer")]
          public int $id;

          #[Column("title")]
          public string $title;
      }
      """
    Then I should see output containing "class Article defined"

  Scenario: Attributes on methods
    Given I start the REPL
    When I enter the following code:
      """
      #[Attribute]
      class Route {
          public function __construct(public string $path, public string $method = 'GET') {}
      }
      """
    And I enter the following code:
      """
      class Controller {
          #[Route("/home")]
          public function home() {
              return "Home";
          }
      }
      """
    Then I should see output containing "class Controller defined"

  Scenario: Attributes on method parameters
    Given I start the REPL
    When I enter the following code:
      """
      #[Attribute]
      class Inject {
          public function __construct(public string $service) {}
      }
      """
    And I enter the following code:
      """
      class Service {
          public function process(#[Inject("database")] $db) {
              return "Processing";
          }
      }
      """
    Then I should see output containing "class Service defined"

  Scenario: Access class attributes via Reflection
    Given I start the REPL
    When I enter the following code:
      """
      #[Attribute]
      class Table {
          public function __construct(public string $name) {}
      }
      """
    And I enter the following code:
      """
      #[Table("users")]
      class User {}
      """
    And I enter "$ref = new ReflectionClass('User')"
    And I enter "count($ref->getAttributes())"
    Then I should see output containing "Int = 1"

  Scenario: Access method attributes via Reflection
    Given I start the REPL
    When I enter the following code:
      """
      #[Attribute]
      class Route {
          public function __construct(public string $path) {}
      }
      """
    And I enter the following code:
      """
      class Controller {
          #[Route("/api/users")]
          public function index() {}
      }
      """
    And I enter "$ref = new ReflectionClass('Controller')"
    And I enter "$method = $ref->getMethod('index')"
    And I enter "count($method->getAttributes())"
    Then I should see output containing "Int = 1"

  Scenario: Get attribute instance and access properties
    Given I start the REPL
    When I enter the following code:
      """
      #[Attribute]
      class MaxLength {
          public function __construct(public int $length) {}
      }
      """
    And I enter the following code:
      """
      class Form {
          #[MaxLength(255)]
          public string $name;
      }
      """
    And I enter "$ref = new ReflectionClass('Form')"
    And I enter "$prop = $ref->getProperty('name')"
    And I enter "$attr = $prop->getAttributes()[0]"
    And I enter "$instance = $attr->newInstance()"
    And I enter "$instance->length"
    Then I should see output containing "Int = 255"

  Scenario: Multiple attributes on property with reflection
    Given I start the REPL
    When I enter the following code:
      """
      #[Attribute]
      class Column {
          public function __construct(public string $name) {}
      }
      """
    And I enter the following code:
      """
      #[Attribute]
      class PrimaryKey {}
      """
    And I enter the following code:
      """
      class Entity {
          #[Column("id"), PrimaryKey]
          public int $id;
      }
      """
    And I enter "$ref = new ReflectionClass('Entity')"
    And I enter "count($ref->getProperty('id')->getAttributes())"
    Then I should see output containing "Int = 2"

  Scenario: Attribute with named arguments
    Given I start the REPL
    When I enter the following code:
      """
      #[Attribute]
      class Validate {
          public function __construct(
              public int $min = 0,
              public int $max = 100
          ) {}
      }
      """
    And I enter the following code:
      """
      class Input {
          #[Validate(min: 1, max: 50)]
          public int $age;
      }
      """
    And I enter "$ref = new ReflectionClass('Input')"
    And I enter "$attr = $ref->getProperty('age')->getAttributes()[0]->newInstance()"
    And I enter "$attr->min"
    Then I should see output containing "Int = 1"

  Scenario: Multiple attributes on method
    Given I start the REPL
    When I enter the following code:
      """
      #[Attribute]
      class Route {
          public function __construct(public string $path) {}
      }
      """
    And I enter the following code:
      """
      #[Attribute]
      class RequireAuth {}
      """
    And I enter the following code:
      """
      class ApiController {
          #[Route("/api/secret")]
          #[RequireAuth]
          public function secretData() {
              return "Secret";
          }
      }
      """
    And I enter "$ref = new ReflectionClass('ApiController')"
    And I enter "count($ref->getMethod('secretData')->getAttributes())"
    Then I should see output containing "Int = 2"

  Scenario: Parameter attributes via Reflection
    Given I start the REPL
    When I enter the following code:
      """
      #[Attribute]
      class FromQuery {
          public function __construct(public string $name) {}
      }
      """
    And I enter the following code:
      """
      class Handler {
          public function handle(#[FromQuery("page")] int $page) {}
      }
      """
    And I enter "$ref = new ReflectionClass('Handler')"
    And I enter "$params = $ref->getMethod('handle')->getParameters()"
    And I enter "count($params[0]->getAttributes())"
    Then I should see output containing "Int = 1"

  Scenario: Attribute name from Reflection
    Given I start the REPL
    When I enter the following code:
      """
      #[Attribute]
      class MyCustomAttribute {}
      """
    And I enter the following code:
      """
      #[MyCustomAttribute]
      class Decorated {}
      """
    And I enter "$ref = new ReflectionClass('Decorated')"
    And I enter "$ref->getAttributes()[0]->getName()"
    Then I should see output containing "String = \"MyCustomAttribute\""

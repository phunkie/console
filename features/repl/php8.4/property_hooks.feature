Feature: Property Hooks (PHP 8.4)
  As a PHP developer
  I want to use property hooks in the REPL
  So that I can define custom behavior for property access

  Scenario: Property with get hook
    Given I start the REPL
    When I enter the following code:
      """
      class User {
          public string $name {
              get => strtoupper($this->name);
          }

          public function __construct(string $name) {
              $this->name = $name;
          }
      }
      """
    And I enter "$user = new User('alice')"
    And I enter "$user->name"
    Then I should see output containing "String = \"ALICE\""

  Scenario: Property with set hook
    Given I start the REPL
    When I enter the following code:
      """
      class Product {
          public float $price {
              set {
                  if ($value < 0) {
                      throw new InvalidArgumentException('Price cannot be negative');
                  }
                  $this->price = $value;
              }
          }
      }
      """
    And I enter "$p = new Product()"
    And I enter "$p->price = 10.50"
    And I enter "$p->price"
    Then I should see output containing "Float = 10.5"

  Scenario: Property with both get and set hooks
    Given I start the REPL
    When I enter the following code:
      """
      class Temperature {
          public float $celsius {
              get => $this->celsius;
              set => $this->celsius = $value;
          }

          public function __construct(float $temp) {
              $this->celsius = $temp;
          }
      }
      """
    And I enter "$t = new Temperature(25.0)"
    And I enter "$t->celsius"
    Then I should see output containing "Float = 25"

  Scenario: Virtual property (no backing field)
    Given I start the REPL
    When I enter the following code:
      """
      class Circle {
          public function __construct(
              public float $radius
          ) {}

          public float $diameter {
              get => $this->radius * 2;
              set => $this->radius = $value / 2;
          }
      }
      """
    And I enter "$c = new Circle(5.0)"
    And I enter "$c->diameter"
    Then I should see output containing "Float = 10"
    When I enter "$c->diameter = 20"
    And I enter "$c->radius"
    Then I should see output containing "Float = 10"

  Scenario: Property hook with validation
    Given I start the REPL
    When I enter the following code:
      """
      class Account {
          public int $balance {
              set {
                  if ($value < 0) {
                      $this->balance = 0;
                  } else {
                      $this->balance = $value;
                  }
              }
          }

          public function __construct() {
              $this->balance = 0;
          }
      }
      """
    And I enter "$acc = new Account()"
    And I enter "$acc->balance = 100"
    And I enter "$acc->balance"
    Then I should see output containing "Int = 100"

  Scenario: Property hook with transformation
    Given I start the REPL
    When I enter the following code:
      """
      class Email {
          public string $address {
              get => $this->address;
              set => $this->address = strtolower(trim($value));
          }

          public function __construct(string $email) {
              $this->address = $email;
          }
      }
      """
    And I enter "$e = new Email('  USER@EXAMPLE.COM  ')"
    And I enter "$e->address"
    Then I should see output containing "String = \"user@example.com\""

  Scenario: Readonly property with get hook
    Given I start the REPL
    When I enter the following code:
      """
      class Person {
          public function __construct(
              private string $firstName,
              private string $lastName
          ) {}

          public string $fullName {
              get => $this->firstName . ' ' . $this->lastName;
          }
      }
      """
    And I enter "$p = new Person('John', 'Doe')"
    And I enter "$p->fullName"
    Then I should see output containing "String = \"John Doe\""

  Scenario: Property hook with reference to other properties
    Given I start the REPL
    When I enter the following code:
      """
      class Rectangle {
          public function __construct(
              public float $width,
              public float $height
          ) {}

          public float $area {
              get => $this->width * $this->height;
          }
      }
      """
    And I enter "$r = new Rectangle(5.0, 3.0)"
    And I enter "$r->area"
    Then I should see output containing "Float = 15"

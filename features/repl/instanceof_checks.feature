Feature: Instanceof Operator Support
  As a PHP developer
  I want to use the instanceof operator in the REPL
  So that I can check if an object is an instance of a class or implements an interface

  Scenario: instanceof with true result for class instance
    Given I start the REPL
    When I enter "class Dog {}"
    And I enter "$dog = new Dog()"
    And I enter "$dog instanceof Dog"
    Then I should see output containing "$var0: Bool = true"

  Scenario: instanceof with false result for different class
    Given I start the REPL
    When I enter "class Dog {}"
    And I enter "class Cat {}"
    And I enter "$dog = new Dog()"
    And I enter "$dog instanceof Cat"
    Then I should see output containing "$var0: Bool = false"

  Scenario: instanceof with interface
    Given I start the REPL
    When I enter "interface Flyable {}"
    And I enter "class Bird implements Flyable {}"
    And I enter "$bird = new Bird()"
    And I enter "$bird instanceof Flyable"
    Then I should see output containing "$var0: Bool = true"

  Scenario: instanceof with parent class
    Given I start the REPL
    When I enter "class Animal {}"
    And I enter "class Dog extends Animal {}"
    And I enter "$dog = new Dog()"
    And I enter "$dog instanceof Animal"
    Then I should see output containing "$var0: Bool = true"

  Scenario: instanceof with variable class name
    Given I start the REPL
    When I enter "class Dog {}"
    And I enter "$dog = new Dog()"
    And I enter "$className = 'Dog'"
    And I enter "$dog instanceof $className"
    Then I should see output containing "$var0: Bool = true"

  Scenario: instanceof with null value returns false
    Given I start the REPL
    When I enter "class Dog {}"
    And I enter "$dog = null"
    And I enter "$dog instanceof Dog"
    Then I should see output containing "$var0: Bool = false"

  Scenario: instanceof with standard PHP class
    Given I start the REPL
    When I enter "$date = new DateTime()"
    And I enter "$date instanceof DateTime"
    Then I should see output containing "$var0: Bool = true"

  Scenario: instanceof with Phunkie Some monad
    Given I start the REPL
    When I enter "$x = Some(42)"
    And I enter "$x instanceof Phunkie\Types\Some"
    Then I should see output containing "$var0: Bool = true"

  Scenario: instanceof checks are chainable in expressions
    Given I start the REPL
    When I enter "class Dog {}"
    And I enter "class Cat {}"
    And I enter "$dog = new Dog()"
    And I enter "$dog instanceof Dog && !($dog instanceof Cat)"
    Then I should see output containing "$var0: Bool = true"

  Scenario: instanceof with scalar value returns false
    Given I start the REPL
    When I enter "class Dog {}"
    And I enter "$value = 42"
    And I enter "$value instanceof Dog"
    Then I should see output containing "$var0: Bool = false"

  Scenario: instanceof with anonymous class
    Given I start the REPL
    When I enter "interface Runnable { public function run(); }"
    And I enter "$obj = new class implements Runnable { public function run() { return 'running'; } }"
    And I enter "$obj instanceof Runnable"
    Then I should see output containing "$var0: Bool = true"

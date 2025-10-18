Feature: Evaluating Basic PHP Expressions
  As a PHP developer
  I want to evaluate basic PHP expressions
  So that I can perform calculations and operations interactively

  Scenario: Arithmetic addition
    Given I start the REPL
    When I enter "1 + 2"
    Then I should see output containing "$var0: Int = 3"

  Scenario: String concatenation
    Given I start the REPL
    When I enter '"Hello " . "World"'
    Then I should see output containing '$var0: String = "Hello World"'

  Scenario: Boolean AND operation
    Given I start the REPL
    When I enter "true && false"
    Then I should see output containing "$var0: Bool = false"

  Scenario: Boolean NOT operation
    Given I start the REPL
    When I enter "!true"
    Then I should see output containing "$var0: Bool = false"

  Scenario: Simple variable assignment
    Given I start the REPL
    When I enter "$x = 10"
    Then I should see output containing "$x: Int = 10"

  Scenario: Variable assignment with expression
    Given I start the REPL
    When I enter "$x = 10"
    And I enter "$y = $x * 2"
    Then I should see output containing "$y: Int = 20"

  Scenario: Division with integer result
    Given I start the REPL
    When I enter "$y = 20"
    And I enter "$y / 4"
    Then I should see output containing "$var0: Int = 5"

  Scenario: Division resulting in float
    Given I start the REPL
    When I enter "5 / 2"
    Then I should see output containing "$var0: Float = 2.5"

  Scenario: PHP constant expression
    Given I start the REPL
    When I enter "PHP_VERSION"
    Then I should see output containing "$var0: String ="

  Scenario: Arithmetic subtraction
    Given I start the REPL
    When I enter "10 - 3"
    Then I should see output containing "$var0: Int = 7"

  Scenario: Arithmetic multiplication
    Given I start the REPL
    When I enter "4 * 5"
    Then I should see output containing "$var0: Int = 20"

  Scenario: Arithmetic modulo
    Given I start the REPL
    When I enter "10 % 3"
    Then I should see output containing "$var0: Int = 1"

  Scenario: Boolean OR operation
    Given I start the REPL
    When I enter "true || false"
    Then I should see output containing "$var0: Bool = true"

  Scenario: Comparison greater than
    Given I start the REPL
    When I enter "5 > 3"
    Then I should see output containing "$var0: Bool = true"

  Scenario: Comparison equal
    Given I start the REPL
    When I enter "5 == 5"
    Then I should see output containing "$var0: Bool = true"

  Scenario: Unary minus
    Given I start the REPL
    When I enter "-42"
    Then I should see output containing "$var0: Int = -42"

  Scenario: Complex arithmetic expression
    Given I start the REPL
    When I enter "2 + 3 * 4"
    Then I should see output containing "$var0: Int = 14"

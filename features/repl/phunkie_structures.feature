Feature: Evaluating Phunkie Data Structures
  As a developer learning functional programming
  I want to create and manipulate Phunkie data structures
  So that I can experiment with functional patterns

  Scenario: Creating Some values with show()
    Given I start the REPL
    When I enter "Some(42)"
    Then I should see output containing "$var0: Option<Int> = Some(42)"

  Scenario: Creating None values with parentheses
    Given I start the REPL
    When I enter "None()"
    Then I should see output containing "$var0: None = None"

  Scenario: Creating None values as constant (without parentheses)
    Given I start the REPL
    When I enter "None"
    Then I should see output containing "$var0: None = None"

  Scenario: Creating ImmList with show()
    Given I start the REPL
    When I enter "ImmList(1, 2, 3)"
    Then I should see output containing "$var0: List<Int> = List(1, 2, 3)"

  Scenario: Creating Success with show()
    Given I start the REPL
    When I enter "Success(100)"
    Then I should see output containing "$var0: Validation<E, Int> = Success(100)"

  Scenario: Creating Failure with show()
    Given I start the REPL
    When I enter "Failure('error')"
    Then I should see output containing "Validation<String, A> = Failure"

  Scenario: Creating Unit with show()
    Given I start the REPL
    When I enter "Unit()"
    Then I should see output containing "$var0: Unit = ()"

  Scenario: Calling methods on None constant
    Given I start the REPL
    When I enter "None"
    And I enter "$var0->isEmpty()"
    Then I should see output containing "$var1: Bool = true"

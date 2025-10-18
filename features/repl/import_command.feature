Feature: Import Command
  As a developer using Phunkie Console
  I want to import functions from the Phunkie standard library
  So that I can use functional programming utilities in the REPL

  Scenario: Import a single function from immlist module
    Given I start the REPL
    When I enter ":import immlist/head"
    Then I should see output containing "imported function \Phunkie\Functions\immlist\head()"
    When I enter "head(ImmList(1,2,3))"
    Then I should see output containing "Int = 1"

  Scenario: Import all functions from immlist module
    Given I start the REPL
    When I enter ":import immlist/*"
    Then I should see output containing "imported function \Phunkie\Functions\immlist\head()"
    And I should see output containing "imported function \Phunkie\Functions\immlist\tail()"
    And I should see output containing "imported function \Phunkie\Functions\immlist\last()"

  Scenario: Use imported tail function
    Given I start the REPL
    When I enter ":import immlist/tail"
    Then I should see output containing "imported function \Phunkie\Functions\immlist\tail()"
    When I enter "tail(ImmList(1,2,3))"
    And I enter "$var0->head()"
    Then I should see output containing "Int = 2"

  Scenario: Use imported last function
    Given I start the REPL
    When I enter ":import immlist/last"
    Then I should see output containing "imported function \Phunkie\Functions\immlist\last()"
    When I enter "last(ImmList(1,2,3))"
    Then I should see output containing "Int = 3"

  Scenario: Use imported reverse function
    Given I start the REPL
    When I enter ":import immlist/reverse"
    Then I should see output containing "imported function \Phunkie\Functions\immlist\reverse()"
    When I enter "reverse(ImmList(1,2,3))"
    And I enter "$var0->head()"
    Then I should see output containing "Int = 3"

  Scenario: Use imported length function
    Given I start the REPL
    When I enter ":import immlist/length"
    Then I should see output containing "imported function \Phunkie\Functions\immlist\length()"
    When I enter "length(ImmList(1,2,3,4,5))"
    Then I should see output containing "Int = 5"

  Scenario: Use imported take function
    Given I start the REPL
    When I enter ":import immlist/take"
    Then I should see output containing "imported function \Phunkie\Functions\immlist\take()"
    When I enter ":import immlist/length"
    And I enter "take(2)(ImmList(1,2,3,4,5))"
    And I enter "length($var0)"
    Then I should see output containing "Int = 2"

  Scenario: Use imported drop function
    Given I start the REPL
    When I enter ":import immlist/drop"
    Then I should see output containing "imported function \Phunkie\Functions\immlist\drop()"
    When I enter "drop(2)(ImmList(1,2,3,4,5))"
    And I enter "$var0->head()"
    Then I should see output containing "Int = 3"

  Scenario: Import from option module
    Given I start the REPL
    When I enter ":import option/isDefined"
    Then I should see output containing "imported function \Phunkie\Functions\option\isDefined()"
    When I enter "isDefined(Some(42))"
    Then I should see output containing "Bool = true"

  Scenario: Import from option module - isNone
    Given I start the REPL
    When I enter ":import option/isNone"
    Then I should see output containing "imported function \Phunkie\Functions\option\isNone()"
    When I enter "isNone(None())"
    Then I should see output containing "Bool = true"

  Scenario: Error when importing non-existent module
    Given I start the REPL
    When I enter ":import nonexistent/func"
    Then I should see output containing "Error: Module 'nonexistent' not found"

  Scenario: Error when importing non-existent function
    Given I start the REPL
    When I enter ":import immlist/nonexistent"
    Then I should see output containing "Error: Function 'nonexistent' not found in module 'immlist'"

  Scenario: Error with invalid import format
    Given I start the REPL
    When I enter ":import invalid"
    Then I should see output containing "Error: Invalid import format"

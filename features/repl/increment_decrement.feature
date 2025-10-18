Feature: Increment and Decrement Operators (++, --)
  As a PHP developer
  I want to use ++ and -- operators in the REPL
  So that I can increment and decrement variable values

  Scenario: Post-increment operator
    Given I start the REPL
    When I enter "$x = 5"
    And I enter "$x++"
    Then I should see output containing "Int = 5"
    When I enter "$x"
    Then I should see output containing "Int = 6"

  Scenario: Pre-increment operator
    Given I start the REPL
    When I enter "$x = 5"
    And I enter "++$x"
    Then I should see output containing "Int = 6"
    When I enter "$x"
    Then I should see output containing "Int = 6"

  Scenario: Post-decrement operator
    Given I start the REPL
    When I enter "$x = 10"
    And I enter "$x--"
    Then I should see output containing "Int = 10"
    When I enter "$x"
    Then I should see output containing "Int = 9"

  Scenario: Pre-decrement operator
    Given I start the REPL
    When I enter "$x = 10"
    And I enter "--$x"
    Then I should see output containing "Int = 9"
    When I enter "$x"
    Then I should see output containing "Int = 9"

  # Note: Inc/dec in complex expressions has a known limitation
  # The operators work correctly in standalone form and direct assignments,
  # but variable updates don't propagate through nested expression evaluation
  # due to the immutable session architecture. This would require accumulating
  # additional assignments through the entire evaluation tree.

  Scenario: Increment in direct assignment works
    Given I start the REPL
    When I enter "$x = 5"
    And I enter "$x++"
    And I enter "$x"
    Then I should see output containing "Int = 6"

  Scenario: Pre-increment in direct assignment works
    Given I start the REPL
    When I enter "$x = 5"
    And I enter "++$x"
    And I enter "$x"
    Then I should see output containing "Int = 6"

  Scenario: Multiple increments
    Given I start the REPL
    When I enter "$x = 0"
    And I enter "$x++"
    And I enter "$x++"
    And I enter "$x++"
    And I enter "$x"
    Then I should see output containing "Int = 3"

  Scenario: Increment with float
    Given I start the REPL
    When I enter "$x = 5.5"
    And I enter "$x++"
    Then I should see output containing "Float = 5.5"
    When I enter "$x"
    Then I should see output containing "Float = 6.5"

  # Note: for loops are not yet supported in the REPL

  Scenario: Decrement to zero
    Given I start the REPL
    When I enter "$x = 1"
    And I enter "--$x"
    Then I should see output containing "Int = 0"
    When I enter "--$x"
    Then I should see output containing "Int = -1"

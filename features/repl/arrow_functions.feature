Feature: Arrow functions
  As a repl runner
  In order to better develop functional programs
  I should be able to use arrow functions

  Scenario: Simple arrow function
    Given I am running the repl
    When I type "fn($x) => $x + 1"
    And I press enter
    Then I should see "$var0: Callable = <function>"

  Scenario: Arrow function with two parameters
    Given I am running the repl
    When I type "fn($x, $y) => $x + $y"
    And I press enter
    Then I should see "$var0: Callable = <function>"

  Scenario: Calling arrow function stored in variable
    Given I am running the repl
    When I type "fn($x) => $x + 1"
    And I press enter
    And I type "$var0(5)"
    And I press enter
    Then I should see "$var1: Int = 6"

  Scenario: Arrow function with string concatenation
    Given I am running the repl
    When I type "fn($name) => \"Hello, \" . $name"
    And I press enter
    And I type "$var0(\"World\")"
    And I press enter
    Then I should see "$var1: String = \"Hello, World\""

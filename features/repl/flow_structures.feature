Feature: Flow structures
  As a repl runner
  In order to better develop functional programs
  I should be able to use flow control structures

  Scenario: If-else statement returning true branch
    Given I am running the repl
    When I type "if (true) { 1 } else { 2 }"
    And I press enter
    Then I should see "$var0: Int = 1"

  Scenario: If-else statement returning false branch
    Given I am running the repl
    When I type "if (false) { 1 } else { 2 }"
    And I press enter
    Then I should see "$var0: Int = 2"

  Scenario: If statement without else
    Given I am running the repl
    When I type "if (true) { 42 }"
    And I press enter
    Then I should see "$var0: Int = 42"

  Scenario: Match expression with matched value
    Given I am running the repl
    When I type "$x = 1"
    And I press enter
    And I type "match ($x) { 1 => \"one\", 2 => \"two\", default => \"other\" }"
    And I press enter
    Then I should see "$var0: String = \"one\""

  Scenario: Match expression with default case
    Given I am running the repl
    When I type "$x = 5"
    And I press enter
    And I type "match ($x) { 1 => \"one\", 2 => \"two\", default => \"other\" }"
    And I press enter
    Then I should see "$var0: String = \"other\""

  Scenario: Match expression with second case
    Given I am running the repl
    When I type "$x = 2"
    And I press enter
    And I type "match ($x) { 1 => \"one\", 2 => \"two\", default => \"other\" }"
    And I press enter
    Then I should see "$var0: String = \"two\""

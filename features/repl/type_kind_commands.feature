Feature: Type and Kind Commands
  As a developer using Phunkie Console
  I want to query the type and kind of expressions
  So that I can understand the type system better

  Scenario: Show type of an integer
    Given I start the REPL
    When I enter ":type Some(42)"
    Then I should see output containing "Option<Int>"

  Scenario: Show kind of Option
    Given I start the REPL
    When I enter ":kind Some(42)"
    Then I should see output containing "* -> *"

  Scenario: Show type of a string
    Given I start the REPL
    When I enter ":type Some(\"hello\")"
    Then I should see output containing "Option<String>"

  Scenario: Show type of ImmList
    Given I start the REPL
    When I enter ":type ImmList(1,2,3)"
    Then I should see output containing "List<Int>"

  Scenario: Show kind of ImmList
    Given I start the REPL
    When I enter ":kind ImmList(1,2,3)"
    Then I should see output containing "* -> *"

  Scenario: Show type of None
    Given I start the REPL
    When I enter ":type None()"
    Then I should see output containing "None"

  Scenario: Show kind of None
    Given I start the REPL
    When I enter ":kind None()"
    Then I should see output containing "* -> *"

  Scenario: Show type of a Pair
    Given I start the REPL
    When I enter ":type Pair(1, \"hello\")"
    Then I should see output containing "(Int, String)"

  Scenario: Show kind of a Pair
    Given I start the REPL
    When I enter ":kind Pair(1, \"hello\")"
    Then I should see output containing "* -> * -> *"

  Scenario: Show type of an integer literal
    Given I start the REPL
    When I enter ":type 42"
    Then I should see output containing "Int"

  Scenario: Show kind of an integer literal
    Given I start the REPL
    When I enter ":kind 42"
    Then I should see output containing "*"

  Scenario: Show type of a string literal
    Given I start the REPL
    When I enter ":type \"hello\""
    Then I should see output containing "String"

  Scenario: Show kind of a string literal
    Given I start the REPL
    When I enter ":kind \"hello\""
    Then I should see output containing "*"

  Scenario: Show type of a boolean
    Given I start the REPL
    When I enter ":type true"
    Then I should see output containing "Boolean"

  Scenario: Show kind of a boolean
    Given I start the REPL
    When I enter ":kind true"
    Then I should see output containing "*"

  Scenario: Show type with variable assignment
    Given I start the REPL
    When I enter "$x = Some(42)"
    And I enter ":type $x"
    Then I should see output containing "Option<Int>"

  Scenario: Show kind with variable assignment
    Given I start the REPL
    When I enter "$x = Some(42)"
    And I enter ":kind $x"
    Then I should see output containing "* -> *"

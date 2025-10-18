Feature: Evaluating Immutable Collections with Array Literals
  As a functional programmer
  I want to create ImmSet and ImmMap using array literals
  So that I can work with immutable collections

  # Note: ImmSet cannot show type due to missing ImmSet::kind constant in phunkie library
  # This is a known issue in phunkie/phunkie and cannot be fixed from console
  Scenario: Creating ImmSet with show()
    Given I start the REPL
    When I enter "ImmSet(1, 2, 3)"
    Then I should see output containing "Set(1, 2, 3)"

  Scenario: Creating ImmMap with array literal
    Given I start the REPL
    When I enter 'ImmMap(["a"=>1, "b"=>2])'
    Then I should see output containing 'Map("a" -> 1, "b" -> 2)'

  Scenario: Creating empty ImmSet
    Given I start the REPL
    When I enter "ImmSet()"
    Then I should see output containing "Set()"

  Scenario: Creating ImmMap with string keys
    Given I start the REPL
    When I enter 'ImmMap(["x"=>10, "y"=>20])'
    Then I should see output containing 'Map("x" -> 10, "y" -> 20)'

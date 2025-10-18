Feature: Reference Assignments (&$var) - Known Limitation
  As a PHP developer
  I want to use reference assignments in the REPL
  So that I can create variable aliases that share the same value

  # NOTE: Reference assignments are not currently supported in the REPL
  # due to architectural limitations. The REPL uses an immutable session
  # architecture where variables are stored in an ImmMap, which doesn't
  # support the aliasing behavior required for PHP references.
  #
  # Implementing true references would require:
  # 1. Wrapping all values in reference containers
  # 2. Major refactoring of variable storage and access
  # 3. Breaking changes to the immutable ReplSession design
  #
  # For now, reference syntax results in "Unsupported expression type" errors.
  # This is documented as a known limitation to be addressed in future versions.

  Scenario: Basic reference assignment - not supported
    Given I start the REPL
    When I enter "$a = 10"
    And I enter "$b = &$a"
    Then I should see output containing "Error"

  Scenario: Reference assignment changes both variables
    Given I start the REPL
    When I enter "$x = 5"
    And I enter "$y = &$x"
    And I enter "$y = 15"
    And I enter "$x"
    Then I should see output containing "Int = 15"

  Scenario: Reference to array element - not supported
    Given I start the REPL
    When I enter "$arr = [1, 2, 3]"
    And I enter "$ref = &$arr[1]"
    Then I should see output containing "Error"

  Scenario: Reference in function parameter - not supported
    Given I start the REPL
    When I enter the following code:
      """
      function increment(&$value) {
          $value++;
      }
      """
    And I enter "$num = 10"
    And I enter "increment($num)"
    And I enter "$num"
    Then I should see output containing "Int = 10"

  Scenario: Passing reference to function
    Given I start the REPL
    When I enter the following code:
      """
      function double(&$x) {
          $x = $x * 2;
      }
      """
    And I enter "$val = 5"
    And I enter "double($val)"
    And I enter "$val"
    Then I should see output containing "Int = 10"

  Scenario: Multiple references to same variable
    Given I start the REPL
    When I enter "$original = 100"
    And I enter "$ref1 = &$original"
    And I enter "$ref2 = &$original"
    And I enter "$ref1 = 200"
    And I enter "$original"
    Then I should see output containing "Int = 200"
    When I enter "$ref2"
    Then I should see output containing "Int = 200"

  Scenario: Breaking reference with new assignment
    Given I start the REPL
    When I enter "$a = 10"
    And I enter "$b = &$a"
    And I enter "$b = 20"
    And I enter "$a"
    Then I should see output containing "Int = 20"
    When I enter "$b = 30"
    And I enter "$a"
    Then I should see output containing "Int = 30"

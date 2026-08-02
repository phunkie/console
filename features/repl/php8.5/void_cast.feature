Feature: Void Cast (PHP 8.5)
  As a PHP developer
  I want to use the (void) cast in the REPL
  So that I can deliberately discard a return value

  Scenario: Void cast produces no result
    Given I start the REPL
    When I enter "(void) strlen(\"phunkie\")"
    Then I should not see "Int = 7"

  Scenario: Void cast still evaluates its operand
    Given I start the REPL
    When I enter "$counter = new stdClass()"
    And I enter "$counter->n = 0"
    And I enter "$bump = function () use ($counter) { $counter->n = $counter->n + 1; return $counter->n; }"
    And I enter "(void) $bump()"
    And I enter "$counter->n"
    Then I should see output containing "Int = 1"

  Scenario: Void cast does not create a numbered variable
    Given I start the REPL
    When I enter "(void) strlen(\"a\")"
    And I enter "42"
    Then I should see output containing "$var0: Int = 42"

  Scenario: Void cast suppresses a NoDiscard warning
    Given I start the REPL
    When I enter the following code:
      """
      #[\NoDiscard("the result matters")]
      function importantValue(): int {
          return 7;
      }
      """
    And I enter "(void) importantValue()"
    Then I should not see "should either be used"

  Scenario: Discarding a NoDiscard return without a void cast warns
    Given I start the REPL
    When I enter the following code:
      """
      #[\NoDiscard]
      function alsoImportant(): int {
          return 7;
      }
      """
    And I enter "$ignored = alsoImportant()"
    Then I should see output containing "Int = 7"

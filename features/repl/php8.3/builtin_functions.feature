Feature: PHP 8.3 Built-in Functions
  As a PHP developer
  I want to use new PHP 8.3 built-in functions in the REPL
  So that I can leverage new PHP functionality

  Scenario: json_validate() with valid JSON
    Given I start the REPL
    When I enter "json_validate('{\"name\": \"test\", \"value\": 123}')"
    Then I should see output containing "Bool = true"

  Scenario: json_validate() with invalid JSON
    Given I start the REPL
    When I enter "json_validate('{invalid json}')"
    Then I should see output containing "Bool = false"

  Scenario: json_validate() with empty string
    Given I start the REPL
    When I enter "json_validate('')"
    Then I should see output containing "Bool = false"

  Scenario: json_validate() with valid array JSON
    Given I start the REPL
    When I enter "json_validate('[1, 2, 3, 4]')"
    Then I should see output containing "Bool = true"

  Scenario: mb_str_pad() basic padding
    Given I start the REPL
    When I enter "mb_str_pad('test', 10)"
    Then I should see output containing "String = \"test      \""

  Scenario: mb_str_pad() with custom pad string
    Given I start the REPL
    When I enter "mb_str_pad('test', 10, '-')"
    Then I should see output containing "String = \"test------\""

  Scenario: mb_str_pad() with left padding
    Given I start the REPL
    When I enter "mb_str_pad('test', 10, ' ', STR_PAD_LEFT)"
    Then I should see output containing "String = \"      test\""

  Scenario: Randomizer::getBytesFromString()
    Given I start the REPL
    When I enter "$r = new \Random\Randomizer()"
    And I enter "strlen($r->getBytesFromString('abcdef', 10))"
    Then I should see output containing "Int = 10"

  Scenario: Randomizer::getFloat()
    Given I start the REPL
    When I enter "$r = new \Random\Randomizer()"
    And I enter "$f = $r->getFloat(0, 1)"
    And I enter "$f >= 0 && $f < 1"
    Then I should see output containing "Bool = true"

  Scenario: Randomizer::nextFloat()
    Given I start the REPL
    When I enter "$r = new \Random\Randomizer()"
    And I enter "$f = $r->nextFloat()"
    And I enter "$f >= 0 && $f < 1"
    Then I should see output containing "Bool = true"

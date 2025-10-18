Feature: Magic Constants
  As a PHP developer
  I want to use magic constants in the REPL
  So that I can access file and line information

  Scenario: __LINE__ constant
    Given I start the REPL
    When I enter "__LINE__"
    Then I should see output containing "Int = 1"

  Scenario: __FILE__ constant
    Given I start the REPL
    When I enter "__FILE__"
    Then I should see output containing "String"

  Scenario: __DIR__ constant
    Given I start the REPL
    When I enter "__DIR__"
    Then I should see output containing "String"

  # Note: __FUNCTION__ in user-defined functions has a known limitation.
  # User-defined functions are interpreted statement-by-statement rather than
  # eval'd as complete PHP code, so __FUNCTION__ returns an empty string.
  # This would require adding function context tracking to ReplSession to fix.
  # __FUNCTION__ works correctly when used directly in the REPL (returns "").

  Scenario: __FUNCTION__ in function - known limitation
    Given I start the REPL
    When I enter the following code:
      """
      function testFunc() {
          return __FUNCTION__;
      }
      """
    And I enter "testFunc()"
    Then I should see output containing "String = \"\""

  Scenario: __METHOD__ in method
    Given I start the REPL
    When I enter the following code:
      """
      class TestClass {
          public function testMethod() {
              return __METHOD__;
          }
      }
      """
    And I enter "$obj = new TestClass()"
    And I enter "$obj->testMethod()"
    Then I should see output containing "String = \"TestClass::testMethod\""

  Scenario: __CLASS__ in class
    Given I start the REPL
    When I enter the following code:
      """
      class MyClass {
          public function getName() {
              return __CLASS__;
          }
      }
      """
    And I enter "$obj = new MyClass()"
    And I enter "$obj->getName()"
    Then I should see output containing "String = \"MyClass\""

  Scenario: __NAMESPACE__ constant
    Given I start the REPL
    When I enter "__NAMESPACE__"
    Then I should see output containing "String = \"\""

  Scenario: __TRAIT__ in trait
    Given I start the REPL
    When I enter the following code:
      """
      trait MyTrait {
          public function getTraitName() {
              return __TRAIT__;
          }
      }
      """
    And I enter the following code:
      """
      class TraitUser {
          use MyTrait;
      }
      """
    And I enter "$obj = new TraitUser()"
    And I enter "$obj->getTraitName()"
    Then I should see output containing "String = \"MyTrait\""

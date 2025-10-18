Feature: Loading .phunkie files
  As a developer using the REPL
  I want to load function and class definitions from files
  So that I can reuse code and work with larger scripts

  Scenario: Loading a .phunkie file with functions
    Given I have a file "test.phunkie" with content:
      """
      function testFunc() {
        return 42;
      }

      function greet($name) {
        return "Hello, $name!";
      }
      """
    And I start the REPL
    When I enter ":load test.phunkie"
    Then I should see output containing "// file test.phunkie loaded"
    When I enter "testFunc()"
    Then I should see output containing "42"
    When I enter "greet(\"World\")"
    Then I should see output containing "Hello, World!"

  Scenario: Loading a .php file with class definitions
    Given I have a file "test.php" with content:
      """
      <?php
      class TestClass {
        public function getValue() {
          return 100;
        }
      }

      function helper() {
        return "helper result";
      }
      """
    And I start the REPL
    When I enter ":load test.php"
    Then I should see output containing "// file test.php loaded"
    When I enter "$obj = new TestClass()"
    Then I should see output containing "TestClass"
    When I enter "$obj->getValue()"
    Then I should see output containing "100"
    When I enter "helper()"
    Then I should see output containing "helper result"

  Scenario: Loading a non-existent file
    Given I start the REPL
    When I enter ":load nonexistent.phunkie"
    Then I should see output containing "File not found"

  Scenario: Loading a file with invalid extension
    Given I have a file "test.txt" with content:
      """
      42
      """
    And I start the REPL
    When I enter ":load test.txt"
    Then I should see output containing "must have .phunkie or .php extension"

  Scenario: Loading a file with comments
    Given I have a file "test.phunkie" with content:
      """
      // This is a comment
      function add($a, $b) {
        return $a + $b;
      }
      // Another comment
      function multiply($a, $b) {
        return $a * $b;
      }
      """
    And I start the REPL
    When I enter ":load test.phunkie"
    Then I should see output containing "// file test.phunkie loaded"
    When I enter "add(5, 3)"
    Then I should see output containing "8"
    When I enter "multiply(4, 7)"
    Then I should see output containing "28"

  Scenario: Loading a file with output suppression
    Given I have a file "test.php" with content:
      """
      <?php
      echo "This should not appear\n";
      print "Neither should this\n";

      function silentFunc() {
        return "Result";
      }
      """
    And I start the REPL
    When I enter ":load test.php"
    Then I should see output containing "// file test.php loaded"
    And I should not see "This should not appear"
    And I should not see "Neither should this"
    When I enter "silentFunc()"
    Then I should see output containing "Result"

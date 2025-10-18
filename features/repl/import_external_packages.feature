Feature: Import from External Packages
  As a developer using Phunkie Console
  I want to import functions from external Phunkie packages like effect and streams
  So that I can use their functionality in the REPL

  Scenario: Import a function from phunkie/effect
    Given I start the REPL
    When I enter ":import effect::console/printTable"
    Then I should see output containing "imported function \Phunkie\Effect\Functions\console\printTable()"

  Scenario: Import printError from phunkie/effect
    Given I start the REPL
    When I enter ":import effect::console/printError"
    Then I should see output containing "imported function \Phunkie\Effect\Functions\console\printError()"
    When I enter "printError(\"test error\")->unsafeRun()"
    Then I should see output containing "Error: test error"

  Scenario: Import printSuccess from phunkie/effect
    Given I start the REPL
    When I enter ":import effect::console/printSuccess"
    Then I should see output containing "imported function \Phunkie\Effect\Functions\console\printSuccess()"
    When I enter "printSuccess(\"test success\")->unsafeRun()"
    Then I should see output containing "Success: test success"

  Scenario: Import printWarning from phunkie/effect
    Given I start the REPL
    When I enter ":import effect::console/printWarning"
    Then I should see output containing "imported function \Phunkie\Effect\Functions\console\printWarning()"
    When I enter "printWarning(\"test warning\")->unsafeRun()"
    Then I should see output containing "Warning: test warning"

  Scenario: Import printInfo from phunkie/effect
    Given I start the REPL
    When I enter ":import effect::console/printInfo"
    Then I should see output containing "imported function \Phunkie\Effect\Functions\console\printInfo()"
    When I enter "printInfo(\"test info\")->unsafeRun()"
    Then I should see output containing "Info: test info"

  Scenario: Import printDebug from phunkie/effect
    Given I start the REPL
    When I enter ":import effect::console/printDebug"
    Then I should see output containing "imported function \Phunkie\Effect\Functions\console\printDebug()"
    When I enter "printDebug(\"test debug\")->unsafeRun()"
    Then I should see output containing "Debug: test debug"

  Scenario: Import all functions from effect console module
    Given I start the REPL
    When I enter ":import effect::console/*"
    Then I should see output containing "imported function \Phunkie\Effect\Functions\console\printLn()"
    And I should see output containing "imported function \Phunkie\Effect\Functions\console\printLines()"
    And I should see output containing "imported function \Phunkie\Effect\Functions\console\readLine()"
    And I should see output containing "imported function \Phunkie\Effect\Functions\console\printError()"
    And I should see output containing "imported function \Phunkie\Effect\Functions\console\printWarning()"
    And I should see output containing "imported function \Phunkie\Effect\Functions\console\printSuccess()"
    And I should see output containing "imported function \Phunkie\Effect\Functions\console\printInfo()"
    And I should see output containing "imported function \Phunkie\Effect\Functions\console\printDebug()"
    And I should see output containing "imported function \Phunkie\Effect\Functions\console\printTable()"
    And I should see output containing "imported function \Phunkie\Effect\Functions\console\printProgress()"
    And I should see output containing "imported function \Phunkie\Effect\Functions\console\printSpinner()"

  Scenario: Error when importing from non-existent package
    Given I start the REPL
    When I enter ":import nonexistent::module/func"
    Then I should see output containing "Error: Unknown package 'nonexistent'"

  Scenario: Error when importing non-existent module from effect
    Given I start the REPL
    When I enter ":import effect::nonexistent/func"
    Then I should see output containing "Error: Module 'nonexistent' not found in package 'phunkie/effect'"

  Scenario: Mix core and effect imports
    Given I start the REPL
    When I enter ":import immlist/head"
    Then I should see output containing "imported function \Phunkie\Functions\immlist\head()"
    When I enter ":import effect::console/printSuccess"
    Then I should see output containing "imported function \Phunkie\Effect\Functions\console\printSuccess()"
    When I enter "head(ImmList(1,2,3))"
    Then I should see output containing "Int = 1"
    When I enter "printSuccess(\"worked!\")->unsafeRun()"
    Then I should see output containing "Success: worked!"

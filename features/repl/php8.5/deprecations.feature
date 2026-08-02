Feature: Deprecation Notices (PHP 8.5)
  As a PHP developer
  I want the REPL to tell me when I use something deprecated
  So that I learn about it without the evaluation being treated as a failure

  Scenario: A deprecated method reports an advisory and still returns its result
    Given I start the REPL
    When I enter "$p = new ReflectionProperty(\"ReflectionProperty\", \"name\")"
    And I enter "$p->setAccessible(true)"
    Then I should see output containing "Deprecated:"
    And I should see output containing "setAccessible"

  Scenario: A deprecated trait is still usable
    Given I start the REPL
    When I enter the following code:
      """
      #[\Deprecated]
      trait Legacy {
          public function hello(): string { return "hello"; }
      }
      """
    And I enter "class Consumer { use Legacy; }"
    Then I should see output containing "Deprecated:"
    When I enter "(new Consumer())->hello()"
    Then I should see output containing "String = \"hello\""

  Scenario: A deprecated class constant reports an advisory and still resolves
    Given I start the REPL
    When I enter the following code:
      """
      class Config {
          #[\Deprecated(message: "use TIMEOUT instead")]
          const TIMEOUT_SECONDS = 30;
      }
      """
    And I enter "Config::TIMEOUT_SECONDS"
    Then I should see output containing "Deprecated:"
    And I should see output containing "Int = 30"

  Scenario: A deprecation does not turn a good result into an error
    Given I start the REPL
    When I enter "$p = new ReflectionProperty(\"ReflectionProperty\", \"name\")"
    And I enter "$p->setAccessible(true)"
    Then I should not see "Evaluation error"

  Scenario: Code with no deprecation reports none
    Given I start the REPL
    When I enter "strlen(\"phunkie\")"
    Then I should see output containing "Int = 7"
    And I should not see "Deprecated:"

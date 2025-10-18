Feature: Heredoc and Nowdoc String Support
  As a PHP developer
  I want to use heredoc and nowdoc syntax in the REPL
  So that I can work with multi-line strings effectively

  Scenario: Basic nowdoc string (no interpolation)
    Given I start the REPL
    When I enter the following code:
      """
      <<<'EOT'
      Hello World
      EOT
      """
    Then I should see output containing '$var0: String = "Hello World"'

  Scenario: Basic heredoc string (no variables)
    Given I start the REPL
    When I enter the following code:
      """
      <<<EOT
      Hello World
      EOT
      """
    Then I should see output containing '$var0: String = "Hello World"'

  Scenario: Heredoc with variable interpolation
    Given I start the REPL
    When I enter "$name = 'Alice'"
    And I enter the following code:
      """
      <<<EOT
      Hello $name
      EOT
      """
    Then I should see output containing '$var0: String = "Hello Alice"'

  Scenario: Nowdoc preserves variable syntax literally
    Given I start the REPL
    When I enter "$name = 'Alice'"
    And I enter the following code:
      """
      <<<'EOT'
      Hello $name
      EOT
      """
    Then I should see output containing '$var0: String = "Hello $name"'

  Scenario: Multi-line heredoc
    Given I start the REPL
    When I enter the following code:
      """
      <<<EOT
      Line 1
      Line 2
      Line 3
      EOT
      """
    Then I should see output containing "Line 1"
    And I should see output containing "Line 2"
    And I should see output containing "Line 3"

  Scenario: Multi-line nowdoc
    Given I start the REPL
    When I enter the following code:
      """
      <<<'EOT'
      Line 1
      Line 2
      Line 3
      EOT
      """
    Then I should see output containing "Line 1"
    And I should see output containing "Line 2"
    And I should see output containing "Line 3"

  Scenario: Heredoc with special characters
    Given I start the REPL
    When I enter the following code:
      """
      <<<EOT
      Special chars: !@#$%^&*()
      Quotes: "double" 'single'
      EOT
      """
    Then I should see output containing "Special chars: !@#$%^&*()"

  Scenario: Heredoc with multiple variable interpolations
    Given I start the REPL
    When I enter "$first = 'John'"
    And I enter "$last = 'Doe'"
    And I enter the following code:
      """
      <<<EOT
      Name: $first $last
      EOT
      """
    Then I should see output containing '$var0: String = "Name: John Doe"'

  Scenario: Indented heredoc (PHP 7.3+)
    Given I start the REPL
    When I enter the following code:
      """
          <<<EOT
          Indented text
          EOT
      """
    Then I should see output containing '$var0: String = "Indented text"'

  Scenario: Heredoc assigned to variable
    Given I start the REPL
    When I enter the following code:
      """
      $message = <<<EOT
      This is a message
      EOT
      """
    Then I should see output containing '$message: String = "This is a message"'

  Scenario: Nowdoc assigned to variable
    Given I start the REPL
    When I enter the following code:
      """
      $message = <<<'EOT'
      This is a message
      EOT
      """
    Then I should see output containing '$message: String = "This is a message"'

  Scenario: Heredoc with numeric variable
    Given I start the REPL
    When I enter "$count = 42"
    And I enter the following code:
      """
      <<<EOT
      Count: $count
      EOT
      """
    Then I should see output containing '$var0: String = "Count: 42"'

  Scenario: Empty heredoc
    Given I start the REPL
    When I enter the following code:
      """
      <<<EOT
      EOT
      """
    Then I should see output containing '$var0: String = ""'

  Scenario: Empty nowdoc
    Given I start the REPL
    When I enter the following code:
      """
      <<<'EOT'
      EOT
      """
    Then I should see output containing '$var0: String = ""'

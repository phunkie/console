Feature: Named arguments
  As a repl runner
  In order to better develop programs with named arguments
  I should be able to call functions with named parameters

  Scenario: Built-in function call with named arguments in order
    Given I am running the repl
    When I type "str_replace(search: 'foo', replace: 'bar', subject: 'foo bar foo')"
    And I press enter
    Then I should see "$var0: String = \"bar bar bar\""

  Scenario: Built-in function call with named arguments out of order
    Given I am running the repl
    When I type "substr(string: 'Hello World', offset: 6, length: 5)"
    And I press enter
    Then I should see "$var0: String = \"World\""

  Scenario: Built-in function call mixing positional and named arguments
    Given I am running the repl
    When I type "str_replace('foo', replace: 'bar', subject: 'foo bar foo')"
    And I press enter
    Then I should see "$var0: String = \"bar bar bar\""

  Scenario: Built-in function with named arguments and default parameters
    Given I am running the repl
    When I type "substr(string: 'Hello World', offset: 0)"
    And I press enter
    Then I should see "$var0: String = \"Hello World\""

  Scenario: User-defined function with named arguments in order
    Given I am running the repl
    When I type "function greet($name, $greeting) { return $greeting . ', ' . $name; }"
    And I press enter
    And I type "greet(name: 'John', greeting: 'Hello')"
    And I press enter
    Then I should see "$var0: String = \"Hello, John\""

  Scenario: User-defined function with named arguments out of order
    Given I am running the repl
    When I type "function greet($name, $greeting) { return $greeting . ', ' . $name; }"
    And I press enter
    And I type "greet(greeting: 'Hi', name: 'Alice')"
    And I press enter
    Then I should see "$var0: String = \"Hi, Alice\""

  Scenario: User-defined function mixing positional and named arguments
    Given I am running the repl
    When I type "function greet($name, $greeting, $punctuation) { return $greeting . ', ' . $name . $punctuation; }"
    And I press enter
    And I type "greet('Bob', greeting: 'Hey', punctuation: '!')"
    And I press enter
    Then I should see "$var0: String = \"Hey, Bob!\""

  @fixme
  Scenario: User-defined function with named arguments and default parameters
    Given I am running the repl
    When I type "function greet($name, $greeting = 'Hello') { return $greeting . ', ' . $name; }"
    And I press enter
    And I type "greet(name: 'Charlie')"
    And I press enter
    Then I should see "$var0: String = \"Hello, Charlie\""

  Scenario: User-defined function with named arguments skipping positional with default
    Given I am running the repl
    When I type "function describe($item, $color = 'blue', $size = 'medium') { return $color . ' ' . $size . ' ' . $item; }"
    And I press enter
    And I type "describe(item: 'car', size: 'large')"
    And I press enter
    Then I should see "$var0: String = \"blue large car\""

  Scenario: User-defined function with all named arguments
    Given I am running the repl
    When I type "function add($a, $b, $c) { return $a + $b + $c; }"
    And I press enter
    And I type "add(c: 3, a: 1, b: 2)"
    And I press enter
    Then I should see "$var0: Int = 6"

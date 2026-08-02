Feature: URI Extension (PHP 8.5)
  As a PHP developer
  I want to parse URIs with the built-in URI classes
  So that I do not have to reach for parse_url()

  Scenario: Reading the host of an RFC 3986 URI
    Given I start the REPL
    When I enter "$uri = new Uri\Rfc3986\Uri(\"https://php.net/releases/8.5/en.php?a=1#top\")"
    And I enter "$uri->getHost()"
    Then I should see output containing "String = \"php.net\""

  Scenario: Reading the scheme, path, query and fragment
    Given I start the REPL
    When I enter "$uri = new Uri\Rfc3986\Uri(\"https://php.net/releases/8.5/en.php?a=1#top\")"
    And I enter "$uri->getScheme()"
    Then I should see output containing "String = \"https\""
    When I enter "$uri->getPath()"
    Then I should see output containing "String = \"/releases/8.5/en.php\""
    When I enter "$uri->getQuery()"
    Then I should see output containing "String = \"a=1\""
    When I enter "$uri->getFragment()"
    Then I should see output containing "String = \"top\""

  Scenario: Deriving a new URI with a different port
    Given I start the REPL
    When I enter "$uri = new Uri\Rfc3986\Uri(\"https://example.com/\")"
    And I enter "$uri->withPort(8080)->toString()"
    Then I should see output containing "String = \"https://example.com:8080/\""

  Scenario: parse() returns null instead of throwing on a malformed URI
    Given I start the REPL
    When I enter "Uri\Rfc3986\Uri::parse(\"::::\")"
    Then I should see output containing "Null = null"

  Scenario: Resolving a relative reference
    Given I start the REPL
    When I enter "$base = new Uri\Rfc3986\Uri(\"https://example.com/a/b\")"
    And I enter "$base->resolve(\"../c\")->toString()"
    Then I should see output containing "String = \"https://example.com/c\""

  Scenario: WhatWg URL lowercases the host
    Given I start the REPL
    When I enter "$url = new Uri\WhatWg\Url(\"https://EXAMPLE.com/\")"
    And I enter "$url->getAsciiHost()"
    Then I should see output containing "String = \"example.com\""

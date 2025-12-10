Feature: Run IOApp
  As a developer
  I want to run a Phunkie IOApp using the console binary
  So that I can execute side effects

  Scenario: Run a custom IOApp
    Given I have a file "tests/Acceptance/Fixtures/MyApp.php" with content:
      """
      <?php
      namespace Tests\Acceptance\Fixtures;

      use Phunkie\Effect\IO\IOApp;
      use Phunkie\Effect\IO\IO;
      use function Phunkie\Effect\Functions\console\printLn;

      class MyApp extends IOApp
      {
          public function run(?array $args = []): IO
          {
              return printLn("Hello World from IOApp!");
          }
      }
      """
    When I run "php bin/phunkie Tests\\Acceptance\\Fixtures\\MyApp"
    Then I should see "Hello World from IOApp!"
    And I should not see "function run"
    And I should not see "Welcome to phunkie console"

  Scenario: Run a custom IOApp with arguments
    Given I have a file "tests/Acceptance/Fixtures/ArgsApp.php" with content:
      """
      <?php
      namespace Tests\Acceptance\Fixtures;

      use Phunkie\Effect\IO\IOApp;
      use Phunkie\Effect\IO\IO;
      use Phunkie\Types\ImmList;
      use function Phunkie\Effect\Functions\console\printLn;

      class ArgsApp extends IOApp
      {
          public function run(?array $args = []): IO
          {
              return printLn("Args: " . implode(", ", $args));
          }
      }
      """
    When I run "php bin/phunkie Tests\\Acceptance\\Fixtures\\ArgsApp foo bar"
    Then I should see "Args: bin/phunkie, Tests\Acceptance\Fixtures\ArgsApp, foo, bar"

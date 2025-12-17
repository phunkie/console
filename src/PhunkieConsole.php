<?php

/*
 * This file is part of Phunkie, library with functional structures for PHP.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phunkie\Console;

use Phunkie\Console\Types\ReplSession;
use Phunkie\Effect\IO\IO;
use Phunkie\Effect\IO\IOApp;
use Phunkie\Validation\Validation;
use Phunkie\Effect\IO\IOApp\ParsedOptions;
use ReflectionClass;

use function Phunkie\Console\Repl\replLoop;
use function Phunkie\Console\Functions\{setColors, printBanner, loadHistory, saveHistory};
use function Phunkie\Effect\Functions\ioapp\arguments;
use function Phunkie\Effect\Functions\ioapp\option;

use const Phunkie\Effect\Functions\ioapp\NoInput;

class PhunkieConsole extends IOApp
{
    protected function define(): Validation
    {
        return arguments(
            option('c', 'color', 'Enable color output', NoInput)
        );
    }

    public function run(?array $args = []): IO
    {
        return $this->parse($args)->flatMap(fn ($options) => $this->process($options));
    }

    private function process(ParsedOptions $options): IO
    {
        if (count($options->args) > 0) {
            return $this->runScript($options->args);
        }
        return $this->runConsole($options);
    }

    private function runScript(array $args): IO
    {
        $file = realpath($args[0]);
        return new IO(function () use ($file, $args) {
            if (!$file || !file_exists($file)) {
                fwrite(STDERR, "File not found: {$args[0]}\n");
                return 1;
            }

            $result = require $file;

            if ($result instanceof IOApp) {
                return $result->run($args)->unsafeRun();
            }

            if ($result instanceof IO) {
                return $result->unsafeRun();
            }

            // If require didn't return an IOApp, check for declared classes
            $declaredClasses = get_declared_classes();
            foreach ($declaredClasses as $class) {
                $reflection = new ReflectionClass($class);
                if ($reflection->isSubclassOf(IOApp::class) &&
                    !$reflection->isAbstract() &&
                    $reflection->getFileName() === $file) {

                    $app = new $class();
                    return $app->run($args)->unsafeRun();
                }
            }

            return 0;
        });

    }

    private function runConsole(ParsedOptions $options): IO
    {
        $colorEnabled = $options->has('color');

        // Create initial session
        $initialSession = ReplSession::empty();
        $pair = setColors($colorEnabled)->run($initialSession);
        $session = $pair->_1;

        // Load command history from previous sessions
        // Print banner and start REPL loop
        return loadHistory()
            ->flatMap(fn () => printBanner($colorEnabled))
            ->flatMap(fn () => replLoop($session));
    }
}

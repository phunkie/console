<?php

/*
 * This file is part of Phunkie, library with functional structures for PHP.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Phunkie\Console\Functions;

use PHPUnit\Framework\TestCase;
use Phunkie\Console\Types\ReplSession;
use function Phunkie\Console\Functions\{evaluateExpression, cleanErrorMessage};

class EvaluationTest extends TestCase
{
    public function testEvaluateExpressionEvaluatesSimpleArithmetic(): void
    {
        $session = ReplSession::empty();
        $result = evaluateExpression('1 + 1', $session);

        $this->assertInstanceOf(\Phunkie\Validation\Success::class, $result);
        $evalResult = $result->getOrElse(null);
        $this->assertEquals(2, $evalResult->value);
    }

    public function testEvaluateExpressionEvaluatesVariableAssignment(): void
    {
        $session = ReplSession::empty();
        $result = evaluateExpression('$x = 42', $session);

        $this->assertInstanceOf(\Phunkie\Validation\Success::class, $result);
        $evalResult = $result->getOrElse(null);
        $this->assertEquals(42, $evalResult->value);
        $this->assertEquals('$x', $evalResult->assignedVariable);
    }

    public function testEvaluateExpressionEvaluatesPhunkieTypes(): void
    {
        $session = ReplSession::empty();
        $result = evaluateExpression('Some(42)', $session);

        $this->assertInstanceOf(\Phunkie\Validation\Success::class, $result);
        $evalResult = $result->getOrElse(null);
        $this->assertInstanceOf(\Phunkie\Types\Some::class, $evalResult->value);
    }

    public function testEvaluateExpressionEvaluatesImmList(): void
    {
        $session = ReplSession::empty();
        $result = evaluateExpression('ImmList(1, 2, 3)', $session);

        $this->assertInstanceOf(\Phunkie\Validation\Success::class, $result);
        $evalResult = $result->getOrElse(null);
        $this->assertInstanceOf(\Phunkie\Types\ImmList::class, $evalResult->value);
    }

    public function testEvaluateExpressionHandlesSyntaxError(): void
    {
        $session = ReplSession::empty();
        $result = evaluateExpression('1 +', $session);

        $this->assertInstanceOf(\Phunkie\Validation\Failure::class, $result);
    }

    public function testEvaluateExpressionHandlesRuntimeError(): void
    {
        $session = ReplSession::empty();
        $result = evaluateExpression('1 / 0', $session);

        // Division by zero should either succeed with INF or fail
        // depending on PHP version and error handling
        $this->assertInstanceOf(\Phunkie\Validation\Validation::class, $result);
    }

    public function testEvaluateExpressionHandlesUndefinedVariable(): void
    {
        $session = ReplSession::empty();
        $result = evaluateExpression('$undefined', $session);

        $this->assertInstanceOf(\Phunkie\Validation\Failure::class, $result);
    }

    public function testEvaluateExpressionEvaluatesFunctionDefinition(): void
    {
        $session = ReplSession::empty();
        $result = evaluateExpression('function add($a, $b) { return $a + $b; }', $session);

        $this->assertInstanceOf(\Phunkie\Validation\Success::class, $result);
    }

    public function testEvaluateExpressionEvaluatesClassDefinition(): void
    {
        $session = ReplSession::empty();
        $result = evaluateExpression('class TestClass { public $value = 42; }', $session);

        $this->assertInstanceOf(\Phunkie\Validation\Success::class, $result);
    }

    public function testEvaluateExpressionHandlesExpressionWithVariables(): void
    {
        // Create session with a variable
        $session = new ReplSession(
            ImmList(),
            ImmMap(['$x' => 10]),
            false,
            0,
            '',
            null,
            ImmMap()
        );

        $result = evaluateExpression('$x + 5', $session);

        $this->assertInstanceOf(\Phunkie\Validation\Success::class, $result);
        $evalResult = $result->getOrElse(null);
        $this->assertEquals(15, $evalResult->value);
    }

    public function testCleanErrorMessageIsAvailable(): void
    {
        $message = 'TypeError: Too few arguments to function bind(), 0 passed in /Users/md/code/phunkie/console/src/Repl/ReplLoop.php(470) : eval()\'d code on line 4 and exactly 1 expected';
        $cleaned = cleanErrorMessage($message);

        $this->assertIsString($cleaned);
        $this->assertStringNotContainsString('ReplLoop.php', $cleaned);
        $this->assertStringNotContainsString('eval()', $cleaned);
        $this->assertStringNotContainsString('and exactly 1 expected', $cleaned);
    }
}

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
use function Phunkie\Console\Functions\{
    getSession,
    modifySession,
    addToHistory,
    setVariable,
    getVariable,
    nextVariable,
    setColors,
    isColorEnabled,
    resetSession
};

class SessionTest extends TestCase
{
    public function testGetSessionReturnsCurrentSession(): void
    {
        $session = ReplSession::empty();
        $state = getSession();

        $result = $state->run($session);

        $this->assertInstanceOf(\Phunkie\Types\Pair::class, $result);
        $this->assertSame($session, $result->_1);
        $this->assertSame($session, $result->_2);
    }

    public function testModifySessionAppliesTransformation(): void
    {
        $session = ReplSession::empty();

        $state = modifySession(fn($s) => new ReplSession(
            $s->history,
            $s->variables,
            true, // Enable colors
            $s->variableCounter,
            $s->incompleteInput,
            $s->currentNamespace,
            $s->useStatements
        ));

        $result = $state->run($session);

        $this->assertInstanceOf(\Phunkie\Types\Pair::class, $result);
        $this->assertTrue($result->_1->colorEnabled);
        $this->assertNull($result->_2);
    }

    public function testAddToHistoryAppendsExpression(): void
    {
        $session = ReplSession::empty();

        $state = addToHistory('1 + 1');
        $result = $state->run($session);

        $newSession = $result->_1;
        $this->assertEquals(1, $newSession->history->length);
        $this->assertEquals('1 + 1', $newSession->history->head);
    }

    public function testAddToHistoryAppendsMultipleExpressions(): void
    {
        $session = ReplSession::empty();

        $state = addToHistory('1 + 1')
            ->flatMap(fn() => addToHistory('2 + 2'))
            ->flatMap(fn() => addToHistory('3 + 3'));

        $result = $state->run($session);
        $newSession = $result->_1;

        $this->assertEquals(3, $newSession->history->length);
        // History is appended, so newest is at the end
        $this->assertEquals('1 + 1', $newSession->history->nth(0)->get());
        $this->assertEquals('2 + 2', $newSession->history->nth(1)->get());
        $this->assertEquals('3 + 3', $newSession->history->nth(2)->get());
    }

    public function testSetVariableStoresValue(): void
    {
        $session = ReplSession::empty();

        $state = setVariable('$x', 42);
        $result = $state->run($session);

        $newSession = $result->_1;
        $value = $newSession->variables->get('$x');

        $this->assertTrue($value->isDefined());
        $this->assertEquals(42, $value->get());
    }

    public function testGetVariableRetrievesStoredValue(): void
    {
        $session = ReplSession::empty();

        $state = setVariable('$x', 42)
            ->flatMap(fn() => getVariable('$x'));

        $result = $state->run($session);
        $option = $result->_2;

        $this->assertTrue($option->isDefined());
        $this->assertEquals(42, $option->get());
    }

    public function testGetVariableReturnsNoneForUndefinedVariable(): void
    {
        $session = ReplSession::empty();

        $state = getVariable('$nonexistent');
        $result = $state->run($session);

        $option = $result->_2;
        $this->assertFalse($option->isDefined());
    }

    public function testNextVariableGeneratesSequentialNames(): void
    {
        $session = ReplSession::empty();

        $state = nextVariable()
            ->flatMap(fn($var1) => nextVariable()
                ->map(fn($var2) => [$var1, $var2]));

        $result = $state->run($session);
        [$var1, $var2] = $result->_2;

        $this->assertEquals('$var0', $var1);
        $this->assertEquals('$var1', $var2);
        $this->assertEquals(2, $result->_1->variableCounter);
    }

    public function testSetColorsEnablesColors(): void
    {
        $session = ReplSession::empty();

        $state = setColors(true);
        $result = $state->run($session);

        $this->assertTrue($result->_1->colorEnabled);
    }

    public function testSetColorsDisablesColors(): void
    {
        $session = ReplSession::empty();

        $state = setColors(false);
        $result = $state->run($session);

        $this->assertFalse($result->_1->colorEnabled);
    }

    public function testIsColorEnabledReturnsColorState(): void
    {
        $sessionWithColors = new ReplSession(
            ImmList(),
            ImmMap(),
            true, // colorEnabled
            0,
            '',
            null,
            ImmMap()
        );

        $state = isColorEnabled();
        $result = $state->run($sessionWithColors);

        $this->assertTrue($result->_2);
    }

    public function testResetSessionClearsVariablesAndHistory(): void
    {
        // Create a session with some data
        $session = ReplSession::empty();

        $populatedState = setVariable('$x', 42)
            ->flatMap(fn() => addToHistory('1 + 1'))
            ->flatMap(fn() => nextVariable());

        $populatedSession = $populatedState->run($session)->_1;

        // Verify it has data
        $this->assertEquals(1, $populatedSession->history->length);
        $this->assertGreaterThan(0, $populatedSession->variableCounter);

        // Reset the session
        $resetState = resetSession();
        $result = $resetState->run($populatedSession);

        $resetSession = $result->_1;

        // Verify it's cleared
        $this->assertEquals(0, $resetSession->history->length);
        $this->assertEquals(0, $resetSession->variableCounter);
        $this->assertEquals(0, count($resetSession->variables->keys()));
    }
}

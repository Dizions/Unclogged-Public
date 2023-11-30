<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Setup;

use Dizions\Unclogged\TestCase;

/**
 * @covers Dizions\Unclogged\Setup\Environment
 */
final class EnvironmentTest extends TestCase
{
    public function testCanBeConstructedWithEmptySearchPath(): void
    {
        $this->assertInstanceOf(Environment::class, new Environment());
        $this->assertInstanceOf(Environment::class, new Environment([]));
    }

    public function testCanBeConstructedWithCustomSearchPath(): void
    {
        $this->assertInstanceOf(Environment::class, new Environment([$this->setupTestEnvironmentDirectory()]));
    }

    public function testMultipleFilesCanBeRead(): void
    {
        $env = new Environment([$this->setupTestEnvironmentDirectory()]);
        $this->assertSame('a', $env->get('A'));
        $this->assertSame('b', $env->get('B'));
    }

    public function testNonDotEnvFilesAreIgnored(): void
    {
        $env = new Environment([$this->setupTestEnvironmentDirectory()]);
        $this->assertNull($env->get('C'));
        $this->assertNull($env->get('D'));
    }

    public function testLaterFilesOverrideEarlierOnes(): void
    {
        $env = new Environment([$this->setupTestEnvironmentDirectory()]);
        $this->assertSame('overridden', $env->get('X'));
    }

    public function testJsonIsDecoded(): void
    {
        $env = new Environment([$this->setupTestEnvironmentDirectory()]);
        $this->assertSame([1, 'a'], $env->get('JSON'));
    }

    public function testCanGetVariableFromEnvironment(): void
    {
        putenv('FOO=foo');
        $env = new Environment([]);
        $this->assertSame('foo', $env->get('FOO'));
        $this->assertNull($env->get('BAR'));
    }

    public function testFilesOverrideEnvironment(): void
    {
        putenv('A=foo');
        $env = new Environment([$this->setupTestEnvironmentDirectory()]);
        $this->assertSame('a', $env->get('A'));
    }

    public function testCanSetVariable(): void
    {
        putenv('FOO=foo');
        $env = new Environment([]);
        $this->assertSame('foo', $env->get('FOO'));
        $this->assertNull($env->get('BAR'));
        $env->set('FOO', 'foo2');
        $env->set('BAR', 'bar');
        $this->assertSame('foo2', $env->get('FOO'));
        $this->assertSame('bar', $env->get('BAR'));
    }

    public function testCanAddVariableToNewEnvironmentWithoutMutatingOriginal(): void
    {
        $env = new Environment([]);
        $env->clear('FOO');
        $new = $env->withVariable('FOO', 'foo');
        $this->assertNull($env->get('FOO'));
        $this->assertSame('foo', $new->get('FOO'));

        $env = new Environment([]);
        $env->clear('FOO');
        $new = $env->with('FOO', 'foo');
        $this->assertNull($env->get('FOO'));
        $this->assertSame('foo', $new->get('FOO'));
    }

    public function testCanClearVariable(): void
    {
        putenv('FOO=foo');
        $env = new Environment([]);
        $this->assertSame('foo', $env->get('FOO'));
        $env->clear('FOO');
        $this->assertNull($env->get('FOO'));
    }

    public function testCanRemoveVariableFromNewEnvironmentWithoutMutatingOriginal(): void
    {
        putenv('FOO=foo');
        $env = new Environment([]);
        $new = $env->withoutVariable('FOO', 'foo');
        $this->assertSame('foo', $env->get('FOO'));
        $this->assertNull($new->get('FOO'));
    }

    public function testMergingEnvironmentsKeepsValuesFromSecondWhenThereAreConflicts(): void
    {
        $first = new Environment([]);
        $first->set(__METHOD__ . 'A', 'a');
        $first->set(__METHOD__ . 'C', 'a');
        $second = new Environment([]);
        $second->set(__METHOD__ . 'B', 'b');
        $second->set(__METHOD__ . 'C', 'b');
        $merged = $first->merge($second);
        $this->assertSame('a', $merged->get(__METHOD__ . 'A'));
        $this->assertSame('b', $merged->get(__METHOD__ . 'B'));
        $this->assertSame('b', $merged->get(__METHOD__ . 'C'));
    }

    public function testMergingEnvironmentsDoesntMutateOriginals(): void
    {
        $first = new Environment([]);
        $first->set(__METHOD__ . 'A', 'a');
        $second = new Environment([]);
        $second->set(__METHOD__ . 'B', 'b');
        $first->merge($second);
        $this->assertNull($first->get(__METHOD__ . 'B'));
        $this->assertNull($second->get(__METHOD__ . 'A'));
    }

    private function setupTestEnvironmentDirectory(): string
    {
        $dir = dirname(__DIR__, 2) . '/tmp';
        file_put_contents("$dir/a.env", "A=a\nX=x\nJSON='" . json_encode([1, 'a']) . "'");
        file_put_contents("$dir/b.env", "B=b\nX=overridden");
        file_put_contents("$dir/c.ignored", "C=c\nX=c");
        file_put_contents("$dir/d", "D=d\nX=d");
        return $dir;
    }
}

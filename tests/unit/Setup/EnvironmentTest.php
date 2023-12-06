<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Setup;

use Dizions\Unclogged\Input\InvalidParameterException;
use Dizions\Unclogged\TestCase;

/**
 * @covers Dizions\Unclogged\Setup\Environment
 */
final class EnvironmentTest extends TestCase
{
    public function testCanBeConstructedWithEmptySearchPath(): void
    {
        $this->assertInstanceOf(Environment::class, Environment::fromGlobal());
        $this->assertInstanceOf(Environment::class, Environment::fromGlobal()->load([]));
    }

    public function testCanBeConstructedWithCustomSearchPath(): void
    {
        $this->assertInstanceOf(
            Environment::class,
            Environment::fromGlobal()->load([$this->setupTestEnvironmentDirectory()])
        );
    }

    public function testMultipleFilesCanBeRead(): void
    {
        $env = Environment::fromGlobal()->load([$this->setupTestEnvironmentDirectory()]);
        $this->assertSame('a', $env->get('A'));
        $this->assertSame('b', $env->get('B'));
    }

    public function testNonDotEnvFilesAreIgnored(): void
    {
        $env = Environment::fromGlobal()->load([$this->setupTestEnvironmentDirectory()]);
        $this->assertNull($env->get('C'));
        $this->assertNull($env->get('D'));
    }

    public function testLaterFilesOverrideEarlierOnes(): void
    {
        $env = Environment::fromGlobal()->load([$this->setupTestEnvironmentDirectory()]);
        $this->assertSame('overridden', $env->get('X'));
    }

    public function testJsonIsDecoded(): void
    {
        $env = Environment::fromGlobal()->load([$this->setupTestEnvironmentDirectory()]);
        $this->assertSame([1, 'a'], $env->get('JSON'));
    }

    public function testFloatsAreNotDecoded(): void
    {
        $env = Environment::fromGlobal()->load([$this->setupTestEnvironmentDirectory()]);
        $this->assertIsFloat($env->get('FLOAT'));
    }

    public function testIntegersAreNotDecoded(): void
    {
        $env = Environment::fromGlobal()->load([$this->setupTestEnvironmentDirectory()]);
        $this->assertIsInt($env->get('INT'));
    }

    public function testCanGetVariableFromEnvironment(): void
    {
        $_ENV['FOO'] = 'foo';
        $env = Environment::fromGlobal();
        $this->assertSame('foo', $env->get('FOO'));
        $this->assertNull($env->get('BAR'));
        $env = Environment::fromGlobal(['FOO' => 'bar']);
        $this->assertSame('bar', $env->get('FOO'));
        $this->assertNull($env->get('BAR'));
    }

    public function testFilesOverrideEnvironment(): void
    {
        $env = Environment::fromGlobal(['A' => 'foo'])
            ->load([$this->setupTestEnvironmentDirectory()]);
        $this->assertSame('a', $env->get('A'));
    }

    public function testCanSetVariable(): void
    {
        $env = Environment::fromGlobal(['FOO' => 'foo']);
        $this->assertSame('foo', $env->get('FOO'));
        $this->assertNull($env->get('BAR'));
        $env->set('FOO', 'foo2');
        $env->set('BAR', 'bar');
        $this->assertSame('foo2', $env->get('FOO'));
        $this->assertSame('bar', $env->get('BAR'));
    }

    public function testCanAddVariableToNewEnvironmentWithoutMutatingOriginal(): void
    {
        $env = Environment::fromGlobal();
        $env->clear('FOO');
        $new = $env->withVariable('FOO', 'foo');
        $this->assertNull($env->get('FOO'));
        $this->assertSame('foo', $new->get('FOO'));

        $env = Environment::fromGlobal();
        $env->clear('FOO');
        $new = $env->with('FOO', 'foo');
        $this->assertNull($env->get('FOO'));
        $this->assertSame('foo', $new->get('FOO'));
    }

    public function testCanClearVariable(): void
    {
        $env = Environment::fromGlobal(['FOO' => 'foo']);
        $this->assertSame('foo', $env->get('FOO'));
        $env->clear('FOO');
        $this->assertNull($env->get('FOO'));
    }

    public function testCanRemoveVariableFromNewEnvironmentWithoutMutatingOriginal(): void
    {
        $env = Environment::fromGlobal(['FOO' => 'foo']);
        $new = $env->withoutVariable('FOO', 'foo');
        $this->assertSame('foo', $env->get('FOO'));
        $this->assertNull($new->get('FOO'));
    }

    public function testMergingEnvironmentsKeepsValuesFromSecondWhenThereAreConflicts(): void
    {
        $first = Environment::fromGlobal();
        $first->set(__METHOD__ . 'A', 'a');
        $first->set(__METHOD__ . 'C', 'a');
        $second = Environment::fromGlobal();
        $second->set(__METHOD__ . 'B', 'b');
        $second->set(__METHOD__ . 'C', 'b');
        $merged = $first->merge($second);
        $this->assertSame('a', $merged->get(__METHOD__ . 'A'));
        $this->assertSame('b', $merged->get(__METHOD__ . 'B'));
        $this->assertSame('b', $merged->get(__METHOD__ . 'C'));
    }

    public function testMergingEnvironmentsDoesntMutateOriginals(): void
    {
        $first = Environment::fromGlobal();
        $first->set(__METHOD__ . 'A', 'a');
        $second = Environment::fromGlobal();
        $second->set(__METHOD__ . 'B', 'b');
        $first->merge($second);
        $this->assertNull($first->get(__METHOD__ . 'B'));
        $this->assertNull($second->get(__METHOD__ . 'A'));
    }

    public function testDefaultsCanBeSetWithoutOverridingExistingVariables(): void
    {
        $env = Environment::fromGlobal(['FOO' => 'foo']);
        $env->setDefaults(['FOO' => 'bar', 'BAR' => 'bar']);
        $this->assertSame('foo', $env->get('FOO'));
        $this->assertSame('bar', $env->get('BAR'));
    }

    public function testVariablesCanBeValidated(): void
    {
        $env = Environment::fromGlobal(['A' => '1']);
        $env->set('B', '2');
        $this->assertSame(1, $env->getValidator()->int('A')->options([1, 3])->get());
        $this->expectException(InvalidParameterException::class);
        $env->getValidator()->int('B')->options([1, 3])->get();
    }

    private function setupTestEnvironmentDirectory(): string
    {
        $dir = dirname(__DIR__, 2) . '/tmp';
        file_put_contents("$dir/a.env", "A=a\nX=x\nJSON='" . json_encode([1, 'a']) . "'");
        file_put_contents("$dir/b.env", "B=b\nX=overridden\nFLOAT=3.12\nINT=7");
        file_put_contents("$dir/c.ignored", "C=c\nX=c");
        file_put_contents("$dir/d", "D=d\nX=d");
        return $dir;
    }
}

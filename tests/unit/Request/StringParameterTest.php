<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Request;

use Dizions\Unclogged\TestCase;

/**
 * @covers Dizions\Unclogged\Request\StringParameter
 */
final class StringParameterTest extends TestCase
{
    public function testValuesAreCastToString(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->any())->method('getAllParams')->will($this->returnValue(['a' => 1, 'b' => 2]));
        $parameter = new StringParameter('a', $request);
        $this->assertSame('1', $parameter->get());
    }
}
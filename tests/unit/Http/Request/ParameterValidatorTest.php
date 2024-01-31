<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Http\Request;

use Dizions\Unclogged\Input\MissingParameterException;
use Dizions\Unclogged\TestCase;

/**
 * @covers Dizions\Unclogged\Http\Request\ParameterValidator
 */
final class ParameterValidatorTest extends TestCase
{
    public function testParameterCanBeRetrievedFromQueryStringAlone(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->any())->method('getAllParams')->will($this->returnValue(['a' => 1, 'b' => 2]));
        $request->expects($this->any())->method('getBodyParams')->will($this->returnValue(['a' => 1]));
        $request->expects($this->any())->method('getQueryParams')->will($this->returnValue(['b' => 2]));
        $validator = new ParameterValidator($request);
        $this->assertSame(1, $validator->fromBody()->getInt('a'));
        $this->expectException(MissingParameterException::class);
        $validator->fromBody()->getInt('b');
    }

    public function testParameterCanBeRetrievedFromBodyAlone(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->any())->method('getAllParams')->will($this->returnValue(['a' => 1, 'b' => 2]));
        $request->expects($this->any())->method('getBodyParams')->will($this->returnValue(['a' => 1]));
        $request->expects($this->any())->method('getQueryParams')->will($this->returnValue(['b' => 2]));
        $validator = new ParameterValidator($request);
        $this->assertSame(2, $validator->fromQueryString()->getInt('b'));
        $this->expectException(MissingParameterException::class);
        $validator->fromQueryString()->getInt('a');
    }
}

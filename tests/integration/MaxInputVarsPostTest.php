<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Http\Request;

use Dizions\Unclogged\TestCase;

/**
 * Technically we get full coverage without this, but this confirms that we can detect when the
 * request exceeds max_input_vars when it's truly being enforced, not just faked via a custom
 * ini_get() in RequestTest.
 */
class MaxInputVarsPostTest extends TestCase
{
    /** @coversNothing */
    public function testRequestExceedingMaxInputVarsIsDetected(): void
    {
        $postData = [];
        for ($i = 0; $i <= 15; $i++) {
            $postData["key$i"] = "val$i";
        }
        $dir = __DIR__;
        $server = $this->startServer($dir, ['max_input_vars' => '10', 'display_startup_errors' => 'Off']);
        $body = http_build_query($postData);

        try {
            $len = strlen($body);
            $response = file_get_contents(
                "{$server->getUrl()}/MaxInputVarsPostTestServer.php",
                false,
                stream_context_create([
                    'http' => [
                        'method' => 'POST',
                        'header' => "Content-type: application/x-www-form-urlencoded\r\nContent-Length: $len\r\n",
                        'content' => $body,
                        'timeout' => 2,
                    ],
                ])
            );

            $this->assertSame('Request body exceeds maximum number of input variables (10)', $response);
        } finally {
            $server->stop();
        }
    }
}

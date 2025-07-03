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
        for ($i = 0; $i <= 10; $i++) {
            $postData["key$i"] = "val$i";
        }
        $dir = __DIR__;
        $server = $this->startServer($dir, ['max_input_vars' => '10', 'display_startup_errors' => 'Off']);
        $urlEncodedBody = http_build_query($postData);

        $multipartBody = '';
        $boundary = md5(uniqid());
        foreach ($postData as $key => $value) {
            $multipartBody .= "--$boundary\r\n";
            $multipartBody .= "Content-Disposition: form-data; name=\"$key\"\r\n\r\n$value\r\n";
        }
        $multipartBody .= "--$boundary--\r\n";

        try {
            $len = strlen($urlEncodedBody);
            $response = file_get_contents(
                "{$server->getUrl()}/MaxInputVarsPostTestServer.php",
                false,
                stream_context_create([
                    'http' => [
                        'method' => 'POST',
                        'header' => "Content-type: application/x-www-form-urlencoded\r\nContent-Length: $len\r\n",
                        'content' => $urlEncodedBody,
                        'timeout' => 2,
                    ],
                ])
            );
            $this->assertSame('Request body exceeds maximum number of input variables (10)', $response);

            $len = strlen($multipartBody);
            $response = file_get_contents(
                "{$server->getUrl()}/MaxInputVarsPostTestServer.php",
                false,
                stream_context_create([
                    'http' => [
                        'method' => 'POST',
                        'header' => "Content-type: multipart/form-data; boundary=$boundary\r\nContent-Length: $len\r\n",
                        'content' => $multipartBody,
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

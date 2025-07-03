<?php

declare(strict_types=1);

namespace Dizions\Unclogged;

use RuntimeException;

class PhpServer
{
    private int $port = 0;

    /** @var resource | null */
    private $process = null;
    /** @var resource | null */
    private $stderr = null;
    /** @var resource | null */
    private $stdout = null;

    private function __construct()
    {
    }

    /**
     * @param string $documentRoot
     * @param array<string, string> | string | null $ini Either a full path to a .ini file, or a
     *                                                   key-value array of PHP ini settings.
     * @return PhpServer
     */
    public static function start(string $documentRoot, array | string | null $ini = null): self
    {
        $instance = new self();
        $instance->port = random_int(8000, 8999);

        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $env = getenv();
        if (is_string($ini)) {
            $env['PHPRC'] = $ini;
        }
        $cmd = ['php'];
        if (is_array($ini)) {
            foreach ($ini as $key => $value) {
                $cmd[] = '-d';
                $cmd[] = "$key=$value";
            }
        }
        $cmd = array_merge($cmd, ['-S', "localhost:$instance->port", '-t', $documentRoot]);
        $process = proc_open($cmd, $descriptorSpec, $pipes, null, $env);
        fclose($pipes[0]);

        if ($process === false) {
            throw new RuntimeException('Failed to start server');
        }
        $instance->process = $process;
        $instance->stdout = $pipes[1];
        $instance->stderr = $pipes[2];

        $instance->waitForServerOutput();
        return $instance;
    }

    public function getStderr(): string
    {
        return $this->getStreamContentsWithTimeout($this->stderr);
    }

    public function getStdout(): string
    {
        return $this->getStreamContentsWithTimeout($this->stdout);
    }

    public function getUrl(): string
    {
        return "http://localhost:{$this->port}";
    }

    public function stop(): void
    {
        if (empty($this->process)) {
            return;
        }
        proc_terminate($this->process);
        proc_close($this->process);
    }

    /**
     * @param resource | null $stream
     */
    private function getStreamContentsWithTimeout($stream, float $timeoutSeconds = 0.0): string
    {
        $startTime = microtime(true);
        if (!$this->streamHasUnreadData($stream, $timeoutSeconds)) {
            return '';
        }
        $length = 8192;
        $contents = '';
        $timeoutRemaining = $timeoutSeconds;
        while ($this->streamHasUnreadData($stream, $timeoutRemaining)) {
            $data = fread($stream, $length);
            if ($data === false || $data === '') {
                break;
            }
            $contents .= $data;
            $elapsedTime = microtime(true) - $startTime;
            $timeoutRemaining = max(0, $timeoutSeconds - $elapsedTime);
        }
        return $contents;
    }

    /**
     * @param resource | null $stream
     * @param float $timeoutSeconds
     */
    private function streamHasUnreadData($stream, float $timeoutSeconds = 0): bool
    {
        if ($stream === null) {
            return false;
        }
        $seconds = floor($timeoutSeconds);
        $microseconds = floor(1000000 * ($timeoutSeconds - $seconds));
        $read = [$stream];
        $write = null;
        $except = null;
        return (bool)stream_select($read, $write, $except, (int)$seconds, (int)$microseconds);
    }

    private function waitForServerOutput(): void
    {
        while (true) {
            $read = [$this->stdout, $this->stderr];
            $write = null;
            $except = null;

            $bytes = stream_select($read, $write, $except, 0, 100);
            if ($bytes) {
                return;
            }
            if ($bytes === false) {
                throw new RuntimeException('Error while waiting for server to start');
            }
        }
    }
}

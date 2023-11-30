<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Setup;

use DirectoryIterator;
use Dizions\Unclogged\Filesystem\FilesystemHelpers;
use Dizions\Unclogged\Input\ParameterValidator;
use Dotenv\Dotenv;
use JsonException;
use SplFileInfo;

/**
 * Get variables from the environment, or from *.env files found in any of the given directories.
 *
 * Variables containing valid JSON will be decoded; others will be used as-is.
 *
 * Environment directories are read in the order given, and within a directory, files are read in
 * alphabetical order. If the variable exists in multiple .env files, the latest one loaded will be
 * used.
 *
 * If no environment file contains a variable, it will be retrieved from the real environment.
 *
 * If the variable was not found anywhere, get() will return null.
 *
 * @package Dizions\Unclogged\Setup
 */
class Environment
{
    private array $variables = [];

    /**
     * @param ?array $env If not provided, the $_ENV superglobal will be used
     * @return static
     */
    public static function fromGlobal(?array $env = null): self
    {
        return new static($env);
    }

    public function __construct(?array $env = null)
    {
        $env ??= $_ENV;
        $this->variables = array_map(fn ($v) => $this->decode($v), $env);
    }

    /**
     * @param string $key
     * @return mixed JSON-decoded where relevant, null if the variable doesn't exist
     */
    public function get(string $key)
    {
        return $this->variables[$key] ?? null;
    }

    public function getValidator(): ParameterValidator
    {
        return (new ParameterValidator())->setData($this->variables, 'environment');
    }

    /** @param iterable $environmentFilePaths Load all .env files in the given paths, in order */
    public function load(iterable $environmentFilePaths): self
    {
        foreach ($environmentFilePaths as $envDir) {
            if (FilesystemHelpers::isDir($envDir)) {
                $this->variables = array_merge($this->variables, $this->getVariablesFromDirectory($envDir));
            }
        }
        $this->variables = array_map(fn ($v) => $this->decode($v), $this->variables);
        return $this;
    }

    /** @return static */
    public function set(string $key, $value): self
    {
        $this->variables[$key] = $value;
        return $this;
    }

    /**
     * Create a new Environment with $key set to $value
     *
     * @param string $key
     * @param mixed $value
     * @return static
     */
    public function with(string $key, $value): self
    {
        $new = clone $this;
        return $new->set($key, $value);
    }

    /** @see with() */
    public function withVariable(string $key, $value): self
    {
        return $this->with($key, $value);
    }

    /** @return static */
    public function clear(string $key): self
    {
        $this->variables[$key] = null;
        return $this;
    }

    /**
     * Create a new Environment with $key cleared
     *
     * @param string $key
     * @return static
     */
    public function without(string $key): self
    {
        $new = clone $this;
        return $new->clear($key);
    }

    /** @see without() */
    public function withoutVariable(string $key): self
    {
        return $this->without($key);
    }

    /**
     * Create a new Environment containing the key/value pairs in both $this and $second. If $this
     * and $second have different values for a key, the value from $second will be used.
     *
     * @param Environment $second
     * @return static
     */
    public function merge(self $second): self
    {
        $new = clone $this;
        $new->variables = array_merge($new->variables, $second->variables);
        return $new;
    }

    private function decode(string $in)
    {
        try {
            return json_decode($in, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            return $in;
        }
    }

    private function getVariablesFromDirectory(string $dir): array
    {
        $variables = [];
        $files = $this->getCandidateFilesFromDirectory($dir);
        usort($files, function ($a, $b) {
            return $a->getFileName() <=> $b->getFileName();
        });
        // Do these one-by-one to ensure that later files override earlier ones
        foreach ($files as $envFile) {
            $variables = array_merge(
                $variables,
                Dotenv::createArrayBacked($dir, $envFile->getFileName())->load()
            );
        }
        return $variables;
    }

    /** @return SplFileInfo[] */
    private function getCandidateFilesFromDirectory(string $dir): array
    {
        $files = [];
        $directoryIterator = new DirectoryIterator($dir);
        foreach ($directoryIterator as $candidate) {
            if (!$this->isEnvFile($candidate)) {
                continue;
            }
            $files[] = $candidate->getFileInfo();
        }
        return $files;
    }

    private function isEnvFile(DirectoryIterator $candidate): bool
    {
        return !$candidate->isDot() && $candidate->isFile() && $candidate->getExtension() === 'env';
    }
}

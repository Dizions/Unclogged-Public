<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Http;

use Stringable;

class Url
{
    private string $base;
    private ?string $fragment;
    private array $emptyParams = [];
    private array $urlParams = [];

    public function __construct(string $url)
    {
        $parts = parse_url($url);
        $paramString = $parts['query'] ?? '';
        $this->fragment = $parts['fragment'] ?? null;
        unset($parts['query'], $parts['fragment']);
        $this->base = $this->buildBase($parts);
        $this->urlParams = self::parseStr($paramString);
        foreach ($this->urlParams as $key => $value) {
            if ($value === null) {
                $this->emptyParams[$key] = $key;
            }
        }
    }

    public function __toString(): string
    {
        $string = $this->base;
        if (!empty($this->urlParams)) {
            $string = "$string?{$this->getQuery()}";
        }
        if ($this->fragment !== null) {
            $string = "$string#$this->fragment";
        }
        return $string;
    }

    public function getBaseUrl(): string
    {
        return $this->base;
    }

    public function getFragment(): ?string
    {
        return $this->fragment;
    }

    public function getHost(): ?string
    {
        $parts = parse_url($this->base);
        return $parts['host'] ?? null;
    }

    public function getParameter(string $name): ?string
    {
        return $this->urlParams[$name] ?? null;
    }

    public function getParameters(): array
    {
        return $this->urlParams;
    }

    public function getPassword(): ?string
    {
        $parts = parse_url($this->base);
        return $parts['pass'] ?? null;
    }

    public function getPath(): ?string
    {
        $parts = parse_url($this->base);
        return $parts['path'] ?? null;
    }

    public function getPort(): ?int
    {
        $parts = parse_url($this->base);
        return isset($parts['port']) ? (int)$parts['port'] : null;
    }

    public function getQuery(): ?string
    {
        if (empty($this->urlParams)) {
            return null;
        }
        $paramString = http_build_query($this->urlParams);
        $emptyParams = implode('&', $this->emptyParams);
        return implode('&', array_filter([$paramString, $emptyParams]));
    }

    public function getScheme(): ?string
    {
        $parts = parse_url($this->base);
        return $parts['scheme'] ?? null;
    }

    public function getUser(): ?string
    {
        $parts = parse_url($this->base);
        return $parts['user'] ?? null;
    }

    public function hasParameter(string $name): bool
    {
        return array_key_exists($name, $this->urlParams);
    }

    /**
     * Create a new URL with the specified base URL and the same parameters as this one
     * @param string $newBaseUrl
     * @return Url
     */
    public function withBaseUrl(string $newBaseUrl): self
    {
        $url = clone $this;
        $url->base = $newBaseUrl;
        return $url;
    }

    public function withFragment(string $fragment): self
    {
        $url = clone $this;
        $url->fragment = $fragment;
        return $url;
    }

    public function withHost(string $host): self
    {
        $url = clone $this;
        $parts = parse_url($url->base);
        $parts['host'] = $host;
        $url->base = $this->buildBase($parts);
        return $url;
    }

    public function withParameter(
        string $name,
        string | Stringable | int | float | null $value = null
    ): self {
        $url = clone $this;
        $url->urlParams[$name] = $value;
        if ($value === null) {
            $url->emptyParams[$name] = $name;
        }
        return $url;
    }

    public function withParameterIfUnset(
        string $name,
        string | Stringable | int | float | null $value = null
    ): self {
        if (!$this->hasParameter($name)) {
            return $this->withParameter($name, $value);
        }
        return $this;
    }

    /** Set the given parameters. Will not remove existing parameters that are not in the array. */
    public function withParameters(array $params): self
    {
        $url = $this;
        foreach ($params as $key => $value) {
            $url = $url->withParameter($key, $value);
        }
        return $url;
    }

    /**
     * Set the given parameters. Will not override any existing parameters, or remove existing
     * parameters that are not in the array.
     */
    public function withParametersIfUnset(array $params): self
    {
        $url = $this;
        foreach ($params as $key => $value) {
            $url = $url->withParameterIfUnset($key, $value);
        }
        return $url;
    }

    public function withPath(string $path): self
    {
        $url = clone $this;
        $parts = parse_url($url->base);
        $parts['path'] = $path;
        $url->base = $this->buildBase($parts);
        return $url;
    }

    public function withPort(?int $port): self
    {
        $url = clone $this;
        $parts = parse_url($url->base);
        $parts['port'] = $port;
        $url->base = $this->buildBase($parts);
        return $url;
    }

    public function withScheme(string $scheme): self
    {
        $url = clone $this;
        $parts = parse_url($url->base);
        $parts['scheme'] = $scheme;
        $url->base = $this->buildBase($parts);
        return $url;
    }

    public function withUser(string $user, ?string $password = null): self
    {
        $url = clone $this;
        $parts = parse_url($url->base);
        $parts['user'] = $user;
        if ($password !== null) {
            $parts['pass'] = $password;
        }
        $url->base = $this->buildBase($parts);
        return $url;
    }

    public function withoutFragment(): self
    {
        $url = clone $this;
        $url->fragment = null;
        return $url;
    }

    public function withoutParameter(string $name): self
    {
        $url = clone $this;
        unset($url->urlParams[$name]);
        unset($url->emptyParams[$name]);
        return $url;
    }

    public function withoutParameters(): self
    {
        $url = clone $this;
        $url->urlParams = [];
        $url->emptyParams = [];
        return $url;
    }

    public static function parseStr(string $query): array
    {
        $params = [];
        parse_str($query, $params);

        foreach (explode('&', $query) as $param) {
            if ($param === '') {
                continue;
            }
            $paramParts = explode('=', $param, 2);
            if (count($paramParts) === 1) {
                $params[$paramParts[0]] = null;
            }
        }

        return $params;
    }

    /** @param array<string, string|null> $parts */
    private function buildBase(array $parts): string
    {
        $url = $this->buildSchemePart($parts);
        $url .= $this->buildAuthPart($parts);
        $url .= $parts['host'] ?? '';
        $url .= $this->buildPortPart($parts);
        $url .= $parts['path'] ?? '';
        return $url;
    }

    private function buildAuthPart(array $parts): string
    {
        $auth = '';
        if (isset($parts['user'])) {
            $auth .= $parts['user'];
            if (isset($parts['pass'])) {
                $auth .= ':' . $parts['pass'];
            }
            $auth .= '@';
        }
        return $auth;
    }

    private function buildPortPart(array $parts): string
    {
        return isset($parts['port']) ? ':' . $parts['port'] : '';
    }

    private function buildSchemePart(array $parts): string
    {
        return isset($parts['scheme']) ? $parts['scheme'] . '://' : '';
    }
}

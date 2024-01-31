<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Http;

class Url
{
    private string $base;
    private array $urlParams = [];

    public function __construct(string $url)
    {
        $urlComponents = explode('?', $url);
        $this->base = $urlComponents[0];
        $paramString = $urlComponents[1] ?? '';
        $this->urlParams = [];
        parse_str($paramString, $this->urlParams);
    }

    public function __toString(): string
    {
        if (empty($this->urlParams)) {
            return $this->base;
        }
        $newParamString = http_build_query($this->urlParams);
        return "$this->base?$newParamString";
    }

    public function getParameter(string $name): ?string
    {
        return $this->urlParams[$name] ?? null;
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

    public function withParameter(string $name, string $value): self
    {
        $url = clone $this;
        $url->urlParams[$name] = $value;
        return $url;
    }

    public function withParameterIfUnset(string $name, string $value): self
    {
        if (!isset($this->urlParams[$name])) {
            return $this->withParameter($name, $value);
        }
        return $this;
    }

    public function withParameters(array $params): self
    {
        $url = clone $this;
        $url->urlParams = array_merge($url->urlParams, $params);
        return $url;
    }

    public function withParametersIfUnset(array $params): self
    {
        $url = clone $this;
        $url->urlParams = array_merge($params, $url->urlParams);
        return $url;
    }

    public function withoutParameter(string $name): self
    {
        $url = clone $this;
        unset($url->urlParams[$name]);
        return $url;
    }

    public function withoutParameters(): self
    {
        $url = clone $this;
        $url->urlParams = [];
        return $url;
    }
}

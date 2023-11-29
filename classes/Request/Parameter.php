<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Request;

use Dizions\Unclogged\Errors\HttpBadRequestException;

abstract class Parameter
{
    private const FROM_ANYWHERE = 0;
    private const FROM_QUERY_STRING = 1;
    private const FROM_BODY = 2;

    private string $name;
    private Request $request;
    private int $source = self::FROM_ANYWHERE;

    // Used to distinguished between "default is null" and "no default"
    private bool $isRequired = true;
    private $default;
    private array $options;
    /** @var callable[] */
    private array $validators = [];

    public function __construct(string $name, Request $request)
    {
        $this->name = $name;
        $this->request = $request;
    }

    /**
     * Add a callback which will be passed the provided value (if there is one) as its single
     * parameter. If it returns, it must return a bool. If there are multiple validators defined,
     * they must all return true for the value to be considered valid.
     *
     * @param callable $validator
     * @return static
     */
    public function addValidator(callable $validator): self
    {
        $this->validators[] = $validator;
        return $this;
    }

    /** @return static */
    public function default($default): self
    {
        $this->isRequired = false;
        $this->default = $default;
        return $this;
    }

    /** @return static */
    public function fromBody(): self
    {
        $this->source = self::FROM_BODY;
        return $this;
    }

    /** @return static */
    public function fromQueryString(): self
    {
        $this->source = self::FROM_QUERY_STRING;
        return $this;
    }

    /**
     * @return mixed
     * @throws HttpBadRequestException
     * @throws UnknownContentTypeException
     * @throws MissingParameterException
     * @throws InvalidParameterException
     */
    public function get()
    {
        $params = $this->getRequestParams();
        $name = $this->getName();
        if (!array_key_exists($name, $params)) {
            if ($this->isRequired) {
                throw new MissingParameterException("Required parameter {$name} not found in request");
            }
            return $this->default;
        }
        $value = $params[$name];
        $this->checkOptionIsValid($value);
        return $value;
    }

    /**
     * Set an array of permitted values for this parameter
     * @param array $options
     * @return static
     */
    public function options(array $options): self
    {
        $this->options = $options;
        return $this;
    }

    /**
     * @throws HttpBadRequestException
     * @throws UnknownContentTypeException
     * @throws MissingParameterException
     * @throws InvalidParameterException
     */
    public function __toString(): string
    {
        return (string)$this->get();
    }

    protected function getName(): string
    {
        return $this->name;
    }

    protected function getRequest(): Request
    {
        return $this->request;
    }

    /** @throws InvalidParameterException */
    private function checkOptionIsValid($value): void
    {
        if (isset($this->options) && !in_array($value, $this->options)) {
            $optionsCsv = implode(', ', array_map(fn ($x) => (string)$x, $this->options));
            throw new InvalidParameterException("Parameter {$this->getName()} must be one of: $optionsCsv");
        }
        $this->runValidators($value);
    }

    /**
     * @throws HttpBadRequestException
     * @throws UnknownContentTypeException
     */
    private function getRequestParams(): array
    {
        switch ($this->source) {
            case self::FROM_BODY:
                return $this->getRequest()->getBodyParams();
            case self::FROM_QUERY_STRING:
                return $this->getRequest()->getQueryParams();
            case self::FROM_ANYWHERE:
                return $this->getRequest()->getAllParams();
        }
    }

    /** @throws InvalidParameterException */
    private function runValidators($value): void
    {
        foreach ($this->validators as $callback) {
            if (!$callback($value)) {
                throw new InvalidParameterException("Invalid value for parameter {$this->getName()}");
            }
        }
    }
}

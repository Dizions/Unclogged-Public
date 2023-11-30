<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Request;

use Dizions\Unclogged\Errors\HttpBadRequestException;

abstract class Parameter
{
    private string $name;
    private Request $request;
    private array $data = [];
    private string $source = '';

    // Used to distinguished between "default is null" and "no default"
    private bool $isRequired = true;
    private $default;
    private array $options;
    /** @var callable[] */
    private array $validators;

    public function __construct(string $name, Request $request)
    {
        $this->name = $name;
        $this->validators = $this->getDefaultValidators();
        $this->setRequest($request);
        $this->setData($request->getAllParams(), 'request');
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

    /**
     * @throws HttpBadRequestException
     * @throws UnknownContentTypeException
     * return $this
     */
    public function fromBody(): self
    {
        return $this->setData($this->getRequest()->getBodyParams(), 'request body (POST)');
    }

    /** @return this */
    public function fromQueryString(): self
    {
        return $this->setData($this->getRequest()->getQueryParams(), 'query string (GET)');
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
        $params = $this->getData();
        $name = $this->getName();
        if (!array_key_exists($name, $params)) {
            if ($this->isRequired) {
                $sourceDescription = empty($this->source) ? '' : " in {$this->source}";
                throw new MissingParameterException(
                    "Required parameter {$name} not found{$sourceDescription}"
                );
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
     * @param array $data
     * @param string $sourceDescription Used for generating more explicit error messages
     * @return $this
     */
    public function setData(array $data, string $sourceDescription = ''): self
    {
        $this->data = $data;
        $this->source = $sourceDescription;
        return $this;
    }

    /** @return static */
    public function setRequest(Request $request): self
    {
        $this->request = $request;
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

    protected function getDefaultValidators(): array
    {
        return [];
    }

    protected function getName(): string
    {
        return $this->name;
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

    private function getData(): array
    {
        return $this->data;
    }

    private function getRequest(): Request
    {
        return $this->request;
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

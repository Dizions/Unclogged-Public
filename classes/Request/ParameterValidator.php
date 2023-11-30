<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Request;

use Dizions\Unclogged\Input\ParameterValidator as InputParameterValidator;

class ParameterValidator extends InputParameterValidator
{
    private Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->setData($request->getAllParams(), 'request');
    }

    /**
     * @throws HttpBadRequestException
     * @throws UnknownContentTypeException
     * return $this
     */
    public function fromBody(): self
    {
        return $this->setData($this->request->getBodyParams(), 'request body (POST)');
    }

    /** @return this */
    public function fromQueryString(): self
    {
        return $this->setData($this->request->getQueryParams(), 'query string (GET)');
    }
}

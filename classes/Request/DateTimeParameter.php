<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Request;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Dizions\Unclogged\Errors\HttpBadRequestException;

class DateTimeParameter extends Parameter
{
    public const DEFAULT_DATE_FORMAT = 'Y-m-d';
    public const DEFAULT_TIME_FORMAT = 'H:i:s';
    public const DEFAULT_DATETIME_FORMAT = 'Y-m-d H:i:s';

    private bool $allowEmpty = false;

    public function __construct(string $name, Request $request)
    {
        parent::__construct($name, $request);
        $this->addValidator(
            fn ($x) => $this->isValidEmptyValue($x) || $this->isDateTimeInterface($x) || $this->isParseable($x)
        );
    }

    public function allowEmpty(): self
    {
        $this->allowEmpty = true;
        return $this;
    }

    /**
     * @return DateTimeInterface in UTC
     * @throws HttpBadRequestException
     * @throws UnknownContentTypeException
     * @throws MissingParameterException
     * @throws InvalidParameterException
     */
    public function get(): ?DateTimeInterface
    {
        $value = parent::get();
        if ($this->allowEmpty && $this->isEmpty($value)) {
            return null;
        }
        if ($this->isDateTimeInterface($value)) {
            $value = $value->format(DateTimeInterface::ATOM);
        }
        return (new DateTimeImmutable($value))->setTimezone(new DateTimeZone('UTC'));
    }

    /**
     * @param string $format If the value is non-empty, use this format to convert to a string
     * @return string in UTC
     * @throws HttpBadRequestException
     * @throws UnknownContentTypeException
     * @throws MissingParameterException
     * @throws InvalidParameterException
     */
    public function getString(string $format = self::DEFAULT_DATETIME_FORMAT): string
    {
        $value = parent::get();
        if ($this->allowEmpty && $this->isEmpty($value)) {
            return (string)$value;
        }
        if ($this->isDateTimeInterface($value)) {
            $value = $value->format(DateTimeInterface::ATOM);
        }
        return (new DateTimeImmutable($value))->setTimezone(new DateTimeZone('UTC'))->format($format);
    }

    protected function isEmpty($in): bool
    {
        if ($this->isDateTimeInterface($in)) {
            return false;
        }
        if ($in !== null && !is_scalar($in)) {
            throw new InvalidParameterException("Unexpected data type for parameter {$this->getName()}");
        }
        $emptyDates = [
            '',
            '0000-00-00 00:00:00',
            '0000-00-00',
            '00-00-0000 00:00:00',
            '00-00-0000',
        ];
        return in_array((string)$in, $emptyDates);
    }

    private function isValidEmptyValue($value): bool
    {
        return $this->allowEmpty && $this->isEmpty($value);
    }

    private function isDateTimeInterface($value): bool
    {
        return is_object($value) && $value instanceof DateTimeInterface;
    }

    private function isParseable($value): bool
    {
        if (is_scalar($value)) {
            $parsed = date_parse((string)$value);
            return $parsed['warning_count'] == 0 && $parsed['error_count'] == 0;
        }
        return false;
    }
}

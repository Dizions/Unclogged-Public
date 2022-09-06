<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Strings;

class BasicInterpolator implements InterpolatorInterface
{
    /**
     * Fill in brace-delimited placeholders.
     * @param string $message
     * @param array $context
     * @return string
     */
    public function interpolate(string $message, array $context): string
    {
        // Build a replacement array from context, with braces around the keys so we can just pass
        // it straight to strtr(), and with the values converted to strings.
        $replace = [];
        foreach ($context as $key => $val) {
            $replace['{' . $key . '}'] = self::convertToString($val);
        }

        // Interpolate replacement values into the message and return
        return strtr($message, $replace);
    }

    /**
     * @param mixed $in
     * @return string
     */
    protected static function convertToString($in): string
    {
        if (is_scalar($in) || is_object($in) && method_exists($in, '__toString')) {
            return (string)$in;
        } else {
            return json_encode($in, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR, 512);
        }
    }
}

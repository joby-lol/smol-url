<?php

/**
 * smolURL
 * https://github.com/joby-lol/smol-url
 * (c) 2025 Joby Elliott code@joby.lol
 * MIT License https://opensource.org/licenses/MIT
 */

namespace Joby\Smol\URL;

use Stringable;

/**
 * Class for storing a URL host. This is the part of the URL after the // symbol.
 */
readonly class Host implements Stringable
{
    public string $value;

    /**
     * @param string $value      the raw value stored in the host, not URL encoded. Must be a valid IP or hostname if
     *                           a non-empty value is provided.
     */
    public function __construct(string $value)
    {
        $value = strtolower($value);
        if (
            !filter_var($value, FILTER_VALIDATE_IP)
            && !filter_var($value, FILTER_VALIDATE_DOMAIN)
        ) throw new URLException('Invalid host');
        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

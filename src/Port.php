<?php
/*
* smolURL https://github.com/joby-lol/smol-url
* (c) 2025 Joby Elliott code@joby.lol
* MIT License https://opensource.org/licenses/MIT
*/

namespace Joby\Smol\URL;

use Stringable;

/**
 * Class for storing a URL port. This is the part of the URL after the : symbol after the host.
 */
readonly class Port implements Stringable
{
    /**
     * @var int<0,65535> $value the raw value stored in the port, with null indicating the default port.
     */
    public int $value;

    /**
     * @param int<0,65535> $value the raw value stored in the port, with null indicating the default port. Must be between 0 and 65535, inclusive.
     *
     * @throws URLException if the port is invalid.
     */
    public function __construct(int $value)
    {
        // @phpstan-ignore-next-line we do want to check this at runtime
        if ($value < 0 || $value > 65535) throw new URLException('Invalid port');
        $this->value = $value;
    }

    public function __toString(): string
    {
        return (string)$this->value;
    }
}

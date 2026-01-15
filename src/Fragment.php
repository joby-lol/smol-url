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
 * Class for storing a URL fragment. This is the part of the URL after the # symbol. Instantiating with no argument, null, or an empty string are equivalent.
 */
class Fragment implements Stringable
{

    /**
     * Constructing with no argument, null, or an empty string are equivalent, and all yield null in the value property
     * and render as no fragment in a full URL.
     *
     * @param string $value the raw value stored in the fragment, not URL encoded.
     */
    public function __construct(
        public readonly string $value,
    ) {
    }

    public function __toString(): string
    {
        return rawurlencode($this->value);
    }
}

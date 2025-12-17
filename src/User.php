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
 * Class for storing a URL user and optional password. This is the part of the URL before the @ symbol.
 */
readonly class User implements Stringable
{
    /**
     * @var string $username the raw value stored in the user, not URL encoded.
     */
    public string $username;
    /**
     * @var string|null $password the raw value stored in the password, not URL encoded.
     */
    public string|null $password;

    /**
     * Constructing with no argument, null, or an empty string are equivalent, and all yield null in the value property
     * and render as no user in a full URL.
     *
     * @param string      $username the raw value stored in the user, not URL encoded.
     * @param string|null $password the raw value stored in the password, not URL encoded.
     *
     * @throws URLException if a password is provided without a username.
     */
    public function __construct(
        string $username,
        string|null $password = null,
    ) {
        $this->username = $username;
        $this->password = $password ?: null;
    }

    public function __toString(): string
    {
        if ($this->password) return rawurlencode($this->username) . ':' . rawurlencode($this->password);
        else return rawurlencode($this->username);
    }
}

<?php

/**
 * smolURL
 * https://github.com/joby-lol/smol-url
 * (c) 2025 Joby Elliott code@joby.lol
 * MIT License https://opensource.org/licenses/MIT
 */

namespace Joby\Smol\URL;

/**
 * Generic interface for producing URLs. May use a more specific type if that is useful/necessary.
 * 
 * @template T of URL
 */
interface UrlFactoryInterface
{
    /**
     * Generate a URL from an existing URL. This method is intended for converting a URL of unknown type into a more specific type if necessary for your particular application.
     * 
     * @return T
     */
    public function fromUrl(URL $url): URL;

    /**
     * Generate a URL from a string.
     * 
     * @return T
     */
    public function fromString(string $input): URL;
}

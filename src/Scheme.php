<?php
/*
* smolURL https://github.com/joby-lol/smol-url
* (c) 2025 Joby Elliott code@joby.lol
* MIT License https://opensource.org/licenses/MIT
*/

namespace Joby\Smol\URL;

/**
 * Enum for URL schemes. Intentionally limited to HTTP and HTTPS, because we generally don't want any parsers that might be using this to parse arbitrary schemes, and we don't want to allow arbitrary schemes to be used in URLs either.
 */
enum Scheme: string
{
    case HTTP = 'http';
    case HTTPS = 'https';
}

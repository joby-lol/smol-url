<?php

/**
 * smolURL
 * https://github.com/joby-lol/smol-url
 * (c) 2025 Joby Elliott code@joby.lol
 * MIT License https://opensource.org/licenses/MIT
 */

namespace Joby\Smol\URL;/**
  * Specific class indicating that there was an error getting a URL query value. Generally this should lead to the client getting a 400 error, as it indicates an invalid request that the server cannot parse.
  */
class QueryException extends URLException { }

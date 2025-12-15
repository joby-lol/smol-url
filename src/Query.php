<?php
/*
* smolHTTP
* https://github.com/joby-lol/smol-http
* (c) 2025 Joby Elliott code@joby.lol
* MIT License https://opensource.org/licenses/MIT
*/

namespace Joby\Smol\URL;

use Stringable;

/**
 * Class for storing a URL query. This is the part of the URL after the ? symbol. This class limits the types of values
 * that can be stored in the query to strings, integers, floats, and booleans (represented as integers). No arrays or
 * objects are allowed, and Stringable objects will be converted to strings. Key names must also be strings.
 * 
 * @phpstan-consistent-constructor
 */
readonly class Query implements Stringable
{
    /**
     * @var array<string,string>
     */
    public array $args;

    /**
     * @param array<string,string|Stringable|int|float|bool|null> $args
     */
    public function __construct(array $args = [])
    {
        $built_args = [];
        foreach ($args as $key => $value) {
            if (!is_string($key)) throw new UrlException('Query keys must be strings'); // @phpstan-ignore-line we do want to check this at runtime
            if (is_array($value)) throw new UrlException('Invalid query value type: array'); // @phpstan-ignore-line we do want to check this at runtime
            if (is_null($value)) continue;
            if (is_bool($value)) {
                $built_args[$key] = $value ? '1' : '0';
                continue;
            }
            if ($value instanceof Stringable) {
                $built_args[$key] = (string)$value;
                continue;
            }
            if (is_string($value)) {
                $built_args[$key] = $value;
                continue;
            }
            if (!is_scalar($value)) { // @phpstan-ignore-line we do want to check this at runtime
                throw new UrlException('Invalid query value type: ' . gettype($value));
            }
            $built_args[$key] = (string)$value;
        }
        ksort($built_args);
        $this->args = $built_args;
    }

    /**
     * Create a copy of this query with the given argument added or replaced.
     */
    public function withArg(string $key, string|Stringable|int|float|bool|null $value): static
    {
        $args = $this->args;
        $args[$key] = $value;
        return new static($args);
    }

    /**
     * Create a copy of this query with the given argument removed, if it exists.
     *
     * @param string $key
     */
    public function withoutArg(string $key): static
    {
        $args = $this->args;
        unset($args[$key]);
        return new static($args);
    }

    /**
     * Create a copy of this query with the given arguments added or replaced.
     *
     * @param array<string,string|Stringable|int|float|bool|null> $args
     */
    public function withArgs(array $args): static
    {
        $new_args = $this->args;
        foreach ($args as $key => $value) {
            $new_args[$key] = $value;
        }
        return new static($new_args);
    }

    /**
     * Create a copy of this query with the given arguments removed, if they exist.
     *
     * @param array<string> $keys
     */
    public function withoutArgs(array $keys): static
    {
        $new_args = $this->args;
        foreach ($keys as $key) {
            unset($new_args[$key]);
        }
        return new static($new_args);
    }

    public function __toString(): string
    {
        return http_build_query($this->args);
    }

    public function get(string $key, string|null $default = null): string|null
    {
        return $this->args[$key] ?? $default;
    }

    public function require(string $key): string
    {
        return $this->args[$key] ?? throw new UrlException('Missing required query string: ' . htmlspecialchars($key));
    }

    public function getInt(string $key, int|null $default = null): int|null
    {
        $value = $this->args[$key] ?? $default;
        if (is_null($value)) return null;
        $value = (int)$value;
        if ($value != $this->args[$key]) throw new UrlException('Invalid query integer: ' . htmlspecialchars($key));
        return $value;
    }

    public function requireInt(string $key): int
    {
        $value = $this->getInt($key);
        if (is_null($value)) throw new UrlException('Missing required query integer: ' . htmlspecialchars($key));
        return $value;
    }

    public function getBool(string $key, bool|null $default = null): bool|null
    {
        $value = $this->args[$key] ?? $default;
        if (is_null($value)) return null;
        if ($value === '0') return false;
        elseif ($value === '1') return true;
        else throw new UrlException('Invalid query boolean: ' . htmlspecialchars($key));
    }

    public function requireBool(string $key): bool
    {
        $value = $this->getBool($key);
        if (is_null($value)) throw new UrlException('Missing required query boolean: ' . htmlspecialchars($key));
        return $value;
    }

    public function getFloat(string $key, float|null $default = null): float|null
    {
        $value = $this->args[$key] ?? $default;
        if (is_null($value)) return null;
        $value = (float)$value;
        if ($value != $this->args[$key]) throw new UrlException('Invalid query float: ' . htmlspecialchars($key));
        return $value;
    }

    public function requireFloat(string $key): float
    {
        $value = $this->getFloat($key);
        if (is_null($value)) throw new UrlException('Missing required query float: ' . htmlspecialchars($key));
        return $value;
    }

    public function has(string $key): bool
    {
        return isset($this->args[$key]);
    }
}

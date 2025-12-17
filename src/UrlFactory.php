<?php

/**
 * smolURL
 * https://github.com/joby-lol/smol-url
 * (c) 2025 Joby Elliott code@joby.lol
 * MIT License https://opensource.org/licenses/MIT
 */

namespace Joby\Smol\URL;

/**
 * @implements UrlFactoryInterface<URL>
 */
class UrlFactory implements UrlFactoryInterface
{
    /**
     * @inheritDoc
     */
    public function fromUrl(URL $url): URL
    {
        return clone $url;
    }

    /**
     * @inheritDoc
     */
    public function fromString(string $input): URL
    {
        $parsed = parse_url($input);
        if (!$parsed) throw new URLException('Invalid URL: ' . htmlspecialchars($input));
        // pull out scheme
        if (!isset($parsed['scheme'])) {
            $scheme = null;
        } else {
            $scheme = Scheme::tryFrom($parsed['scheme']);
            if (!$scheme) throw new URLException('Invalid URL scheme: ' . htmlspecialchars($parsed['scheme']));
        }
        // pull out user
        if (!isset($parsed['user'])) {
            $user = null;
        } else {
            $user = new User(
                $parsed['user'],
                $parsed['pass'] ?? null,
            );
        }
        // pull out host
        if (!isset($parsed['host'])) {
            $host = null;
        } else {
            $host = new Host($parsed['host']);
        }
        // pull out port
        if (!isset($parsed['port'])) {
            $port = null;
        } else {
            $port = new Port($parsed['port']);
        }
        // pull out path
        if (isset($parsed['path'])) {
            $path = Path::fromString($parsed['path']);
        } else {
            // unspecified path is not absolute
            $path = new Path(absolute: false);
        }
        // pull out query
        if (!isset($parsed['query'])) {
            $query = null;
        } else {
            parse_str($parsed['query'], $query);
            $query = new Query($query); // @phpstan-ignore-line there's validation in Query constructor
        }
        // pull out fragment
        if (!isset($parsed['fragment'])) {
            $fragment = null;
        } else {
            $fragment = new Fragment($parsed['fragment']);
        }
        // build the URL
        return new URL(
            $path,
            $query,
            $fragment,
            $scheme,
            $user,
            $host,
            $port,
        );
    }
}

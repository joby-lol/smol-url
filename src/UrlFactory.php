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
readonly class UrlFactory implements UrlFactoryInterface
{
    protected URL $base_url;

    /**
     * @inheritDoc
     */
    public function __construct(URL|null $base_url = null)
    {
        $this->base_url = $base_url ?? $this->generateBaseURL();
    }

    /**
     * @inheritDoc
     */
    public function baseUrl(): URL
    {
        return $this->base_url;
    }

    /**
     * @inheritDoc
     */
    public function fromUrl(URL $url): URL
    {
        return new URL(
            $url->path,
            $url->query,
            $url->fragment,
            $url->scheme ?? $this->base_url->scheme,
            $url->user ?? $this->base_url->user,
            $url->host ?? $this->base_url->host,
            $url->port ?? $this->base_url->port,
        );
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
            $scheme ?? $this->base_url->scheme,
            $user ?? $this->base_url->user,
            $host ?? $this->base_url->host,
            $port ?? $this->base_url->port,
        );
    }

    /**
     * @inheritDoc
     */
    public function fromGlobals(): URL
    {
        /** @var string $request_uri */
        $request_uri = $_SERVER['REQUEST_URI'] ?? '/';
        if (strpos($request_uri, '?') !== false) {
            $request_uri = substr($request_uri, 0, strpos($request_uri, '?'));
        }
        return new ($this->base_url::class)(
            Path::fromString($request_uri),
            // @phpstan-ignore-next-line we're trusting the Query constructor to validate here
            new Query($_GET),
            null,
            $this->base_url->scheme,
            $this->base_url->user,
            $this->base_url->host,
            $this->base_url->port,
        );
    }

    protected function generateBaseURL(): URL
    {
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? null;
        return new URL(
            new Path(),
            null,
            null,
            $this->generateBaseURLScheme(),
            null,
            // @phpstan-ignore-next-line we're trusting the Host constructor to validate this
            $host ? new Host($host) : null,
            // @phpstan-ignore-next-line we're trusting the Port constructor to validate this
            $_SERVER['SERVER_PORT'] ? new Port($_SERVER['SERVER_PORT']) : null,
        );
    }

    protected function generateBaseURLScheme(): Scheme
    {
        if (@$_SERVER['HTTPS'] === 'on') {
            return Scheme::HTTPS;
        }
        return Scheme::HTTP;
    }
}

<?php

/**
 * smolURL
 * https://github.com/joby-lol/smol-url
 * (c) 2025 Joby Elliott code@joby.lol
 * MIT License https://opensource.org/licenses/MIT
 */

namespace Joby\Smol\URL;

use BackedEnum;
use Stringable;

/**
 * Class for storing and manipulating URLs, composed of various URL components, all of which are also represented as immutable classes.
 * 
 * @phpstan-consistent-constructor
 */
readonly class URL implements Stringable
{
    public function __construct(
        public Path $path = new Path(),
        public Query|null $query = null,
        public Fragment|null $fragment = null,
        public BackedEnum|null $scheme = null,
        public User|null $user = null,
        public Host|null $host = null,
        public Port|null $port = null,
    ) {}

    /**
     * Build the authority component of the URL as a string.
     *
     * If no authority information is present, this method returns an empty string.
     *
     * The authority syntax of the URL is:
     *
     * ```
     * [user-info@]host[:port]
     * ```
     *
     * If the port component is not set or is the standard port for the current scheme, it is not included.
     */
    public function authorityString(): string
    {
        if (!$this->host) return "";
        $user = (string)$this->user;
        if ($user) $authority = $user . '@' . $this->host;
        else $authority = $this->host;
        if ($port = $this->portString()) $authority .= ':' . $port;
        return $authority;
    }

    /**
     * Return a new instance with the specified link string applied, as if it were an HTML link from this URL. Should support both relative and absolute paths, both full and partial queries, fragments, and any combination of them.
     *
     * This is intended for parsing links from HTML relative to a base URL.
     */
    public function withLinkStringApplied(string $link): static
    {
        if (!$link) return $this;
        // first explode by # to separate fragment if it exists
        list($text, $fragment) = explode('#', $link, 2);
        // then explode by ? to separate full query if it exists
        list($text, $full_query_string) = explode('?', $text, 2);
        // then explode by & to separate partial query if it exists
        list($path, $partial_query_string) = explode('&', $text, 2);
        // then apply everything to build a new URL
        $output = $this;
        // start by building fragment object if necessary
        $fragment = $fragment ? new Fragment($fragment) : null;
        $output = $output->withFragment($fragment);
        // then build query object if necessary
        if ($full_query_string) {
            parse_str($full_query_string, $query);
            // @phpstan-ignore-next-line we're trusting the Query constructor to validate the key types
            $output = $output->withQuery(new Query($query));
        } elseif ($partial_query_string) {
            parse_str($partial_query_string, $query);
            // @phpstan-ignore-next-line we're trusting the Query constructor to validate the key types
            $output = $output->withQuery($this->query ? $this->query->withArgs($query) : new Query($query));
        } else {
            $output = $output->withQuery(null);
        }
        // then build path object if necessary
        if ($path) {
            if (str_starts_with($path, '/')) {
                $output = $output->withPath(Path::fromString($path));
            } else {
                $output = $output->withPath(Path::fromString(
                    $this->path->dirname() . $path
                ));
            }
        }
        // return built result
        return $output;
    }

    /**
     * Return a new instance with the specified scheme. Should return the same object if there is no change.
     */
    public function withScheme(Scheme|null $scheme): static
    {
        if ($scheme === $this->scheme) return $this;
        else return new static(
            $this->path,
            $this->query,
            $this->fragment,
            $scheme,
            $this->user,
            $this->host,
            $this->port,
        );
    }

    /**
     * Return a new instance with the specified User. Should return the same object if there is no change.
     */
    public function withUser(User|null $user): static
    {
        if ($user === $this->user) return $this;
        else return new static(
            $this->path,
            $this->query,
            $this->fragment,
            $this->scheme,
            $user,
            $this->host,
            $this->port,
        );
    }

    /**
     * Return a new instance with the specified Host. Should return the same object if there is no change.
     */
    public function withHost(Host|null $host): static
    {
        if ($host === $this->host) return $this;
        else return new static(
            $this->path,
            $this->query,
            $this->fragment,
            $this->scheme,
            $this->user,
            $host,
            $this->port,
        );
    }

    /**
     * Return a new instance with the specified Port. Should return the same object if there is no change.
     */
    public function withPort(Port|null $port): static
    {
        if ($port === $this->port) return $this;
        else return new static(
            $this->path,
            $this->query,
            $this->fragment,
            $this->scheme,
            $this->user,
            $this->host,
            $port,
        );
    }

    /**
     * Return a new instance with the specified Fragment. Should return the same object if there is no change.
     */
    public function withFragment(Fragment|null $fragment): static
    {
        if ($fragment === $this->fragment) return $this;
        else return new static(
            $this->path,
            $this->query,
            $fragment,
            $this->scheme,
            $this->user,
            $this->host,
            $this->port,
        );
    }

    /**
     * Return a new instance with the specified Query. Should return the same object if there is no change.
     */
    public function withQuery(Query|null $query): static
    {
        if ($query === $this->query) return $this;
        else return new static(
            $this->path,
            $query,
            $this->fragment,
            $this->scheme,
            $this->user,
            $this->host,
            $this->port,
        );
    }

    /**
     * Return a new instance with the specified Path. Should return the same object if there is no change.
     */
    public function withPath(Path $path): static
    {
        if ($path === $this->path) return $this;
        else return new static(
            $path,
            $this->query,
            $this->fragment,
            $this->scheme,
            $this->user,
            $this->host,
            $this->port,
        );
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        if (!$this->path->absolute) {
            // relative paths always render without scheme/authority
            $url = (string)$this->path;
            if (!$url) $url = './';
        } else {
            // absolute paths render with scheme/authority, or a leading slash if no authority is present
            $authority = $this->authorityString();
            if ($authority) {
                if ($this->scheme) $url = $this->scheme->value . '://' . $authority . '/' . $this->path;
                else $url = '//' . $authority . '/' . $this->path;
            } else {
                $url = '/' . $this->path;
            }
        }
        // append query
        if ($q = (string)$this->query) $url .= '?' . $q;
        // append fragment
        if ($f = (string)$this->fragment) $url .= '#' . $f;
        // return result
        return $url;
    }

    /**
     * Retrieve the port component of the URL, as it should be in the authority string.
     *
     * If a port is present, and it is non-standard for the current scheme, this method MUST return it as an integer. If the port is the standard port used with the current scheme, this method returns null. This includes the default HTTP and HTTPS ports when no scheme is present.
     *
     * If no port is present, and no scheme is present, this method returns null.
     *
     * If no port is present, but a scheme is present, this method returns null.
     *
     * To access the port as a Port object instead, use the `port` attribute.
     */
    protected function portString(): ?int
    {
        $port = $this->port?->value;
        if (!$port) return null;
        if ($port === 80 && ($this->schemeString() == 'http' || is_null($this->scheme))) return null;
        if ($port === 443 && ($this->schemeString() == 'https' || is_null($this->scheme))) return null;
        return $port;
    }

    protected function schemeString(): ?string
    {
        if ($this->scheme === null) return null;
        return (string) $this->scheme->value;
    }
}

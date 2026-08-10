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
class URL implements Stringable
{

    public function __construct(
        public readonly Path $path = new Path(),
        public readonly Query|null $query = null,
        public readonly Fragment|null $fragment = null,
        public readonly BackedEnum|null $scheme = null,
        public readonly User|null $user = null,
        public readonly Host|null $host = null,
        public readonly Port|null $port = null,
    ) {}

    /**
     * Return a new instance with the specified query argument added or updated.
     */
    public function withArg(string|Stringable $key, string|Stringable|int|float|bool|null $value): static
    {
        $new_query = $this->query ? $this->query->withArg($key, $value) : new Query([(string) $key => $value]);
        return $this->withQuery($new_query);
    }

    /**
     * Return a new instance with the specified query argument removed.
     */
    public function withoutArg(string|Stringable $key): static
    {
        if (!$this->query)
            return $this;
        $new_query = $this->query->withoutArg($key);
        return $this->withQuery($new_query);
    }

    /**
     * Return a new instance with the specified query arguments added or updated.
     * 
     * @param array<string,string|Stringable|int|float|bool|null> $args
     */
    public function withArgs(array $args): static
    {
        $new_query = $this->query ? $this->query->withArgs($args) : new Query($args);
        return $this->withQuery($new_query);
    }

    /**
     * Return a new instance with the specified query arguments removed.
     * 
     * @param array<string|Stringable> $keys
     */
    public function withoutArgs(array $keys): static
    {
        if (!$this->query)
            return $this;
        $new_query = $this->query->withoutArgs($keys);
        return $this->withQuery($new_query);
    }

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
        if (!$this->host)
            return "";
        $user = (string) $this->user;
        if ($user)
            $authority = $user . '@' . $this->host;
        else
            $authority = $this->host;
        if ($port = $this->portString())
            $authority .= ':' . $port;
        return $authority;
    }

    /**
     * Return a new instance with the specified link string applied, as if it were an HTML link from this URL. Should support both relative and absolute paths, both full and partial queries, fragments, and any combination of them.
     *
     * This is intended for parsing links from HTML relative to a base URL.
     */
    public function withLinkStringApplied(string $link): static
    {
        if (!$link)
            return $this;
        // start building new URL
        $output = $this;
        // first explode by # to separate fragment if it exists
        @list($text, $fragment) = explode('#', $link, 2);
        // start by building fragment object if necessary
        $fragment = $fragment ? new Fragment($fragment) : null;
        $output = $output->withFragment($fragment);
        // if only a fragment was provided then we're done
        if (!$text)
            return $output;
        // then explode by ? to separate full query if it exists
        @list($text, $full_query_string) = explode('?', $text, 2);
        // then explode by & to separate partial query if it exists
        @list($path, $partial_query_string) = explode('&', $text, 2);
        // then build query object if necessary
        if ($full_query_string) {
            parse_str($full_query_string, $query);
            // @phpstan-ignore-next-line we're trusting the Query constructor to validate the key types
            $output = $output->withQuery(new Query($query));
        }
        elseif ($partial_query_string) {
            parse_str($partial_query_string, $query);
            // @phpstan-ignore-next-line we're trusting the Query constructor to validate the key types
            $output = $output->withQuery($this->query ? $this->query->withArgs($query) : new Query($query));
        }
        else {
            $output = $output->withQuery(null);
        }
        // then build path object if necessary
        if ($path) {
            if (str_starts_with($path, '/')) {
                $output = $output->withPath(Path::fromString($path));
            }
            else {
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
    public function withScheme(Scheme|Stringable|string|null $scheme): static
    {
        if (is_string($scheme))
            $scheme = $scheme ? Scheme::from(strtolower($scheme)) : null;
        if ($scheme instanceof Stringable)
            $scheme = Scheme::from(strtolower((string) $scheme));
        if ($scheme === $this->scheme)
            return $this;
        else
            return new static(
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
        if ($user === $this->user)
            return $this;
        else
            return new static(
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
    public function withHost(Host|Stringable|string|null $host): static
    {
        if (is_string($host))
            $host = $host ? new Host((string) $host) : null;
        if ($host instanceof Stringable && !($host instanceof Host))
            $host = new Host((string) $host);
        if ($host == $this->host)
            return $this;
        else
            return new static(
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
    public function withPort(Port|int|null $port): static
    {
        if (is_int($port))
            $port = new Port($port); // @phpstan-ignore-line we're trusting the Port constructor to validate the port number
        if ($port == $this->port)
            return $this;
        else
            return new static(
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
    public function withFragment(Fragment|string|Stringable|null $fragment): static
    {
        if (is_string($fragment))
            $fragment = $fragment ? new Fragment((string) $fragment) : null;
        if ($fragment instanceof Stringable && !($fragment instanceof Fragment))
            $fragment = new Fragment((string) $fragment);
        if ($fragment == $this->fragment)
            return $this;
        else
            return new static(
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
     * 
     * @param Query|array<string,string|Stringable|int|float|bool|null>|null $query
     */
    public function withQuery(Query|array|null $query): static
    {
        if (is_array($query))
            $query = new Query($query);
        if ($query === $this->query)
            return $this;
        else
            return new static(
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
    public function withPath(Path|Stringable|string $path): static
    {
        if (is_string($path))
            $path = Path::fromString($path);
        if (!($path instanceof Path))
            $path = Path::fromString((string) $path);
        if ($path === $this->path)
            return $this;
        else
            return new static(
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
            $url = (string) $this->path;
            if (!$url)
                $url = './';
        }
        else {
            // absolute paths render with scheme/authority, or a leading slash if no authority is present
            $authority = $this->authorityString();
            if ($authority) {
                if ($this->scheme)
                    $url = $this->scheme->value . '://' . $authority . '/' . $this->path;
                else
                    $url = '//' . $authority . '/' . $this->path;
            }
            else {
                $url = '/' . $this->path;
            }
        }
        // append query
        if ($q = (string) $this->query)
            $url .= '?' . $q;
        // append fragment
        if ($f = (string) $this->fragment)
            $url .= '#' . $f;
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
        if (!$port)
            return null;
        if ($port === 80 && ($this->schemeString() == 'http' || is_null($this->scheme)))
            return null;
        if ($port === 443 && ($this->schemeString() == 'https' || is_null($this->scheme)))
            return null;
        return $port;
    }

    protected function schemeString(): ?string
    {
        if ($this->scheme === null)
            return null;
        return (string) $this->scheme->value;
    }

}

# smolURL

A simple and lightweight extensible URL library designed for working with URLs in human-scale applications.

## What is smolURL?

smolURL is a modern PHP library for working with URLs using an immutable, component-based architecture. Unlike built-in PHP URL functions, smolURL represents URLs as composed objects where each component (path, query, fragment, host, etc.) is its own readonly class.

Key features:

- **Immutable design**: All URL components are readonly and use `with*()` methods to create modified copies
- **Type-safe**: Built with PHP 8.3+ features including readonly properties and typed parameters
- **Component-based**: Each URL part (Path, Query, Fragment, Scheme, Host, Port, User) is a separate class
- **Security-focused**: Intentionally limited to HTTP/HTTPS schemes to prevent arbitrary scheme parsing
- **Link resolution**: Built-in support for resolving relative links (like HTML `<a>` tags)
- **Parsing & factories**: Parse strings or globals and compose URLs against a base URL
- **Clean API**: All components implement `Stringable` for easy conversion to strings

## Installation

```bash
composer require joby/smol-url
```

## Basic usage

### Quick start

```php
use Joby\Smol\URL\{URL, Path, Query, Scheme, Host};

$url = new URL(
    Path::fromString('/docs'),
    new Query(['q' => 'smol']),
    scheme: Scheme::HTTPS,
    host: new Host('example.com')
);

echo $url; // "https://example.com/docs?q=smol"
```

### Creating URLs

```php
use Joby\Smol\URL\{URL, Path, Query, Fragment, Scheme, Host, Port, User};

// Simple absolute path
$url = new URL(new Path(filename: 'page.html'));
echo $url; // "/page.html"

// Full URL with all components
$url = new URL(
    path: new Path(['dir1', 'dir2'], 'file.php'),
    query: new Query(['key' => 'value']),
    fragment: new Fragment('section'),
    scheme: Scheme::HTTPS,
    host: new Host('example.com'),
    port: new Port(8080),
    user: new User('username', 'password')
);
echo $url; // "https://username:password@example.com:8080/dir1/dir2/file.php?key=value#section"

// Relative paths
$url = new URL(new Path(filename: 'page.html', absolute: false));
echo $url; // "page.html"
```

### Parsing URLs

```php
use Joby\Smol\URL\{UrlFactory, URL};

$factory = new UrlFactory();

// From string
$url = $factory->fromString('https://example.com/path?x=1#frag');

// From globals (REQUEST_URI, HOST, etc.)
$current = $factory->fromGlobals();

// Merge a URL with the factory base URL
$base = $factory->baseUrl();
$composed = $factory->fromUrl(new URL(path: $base->path));
```

### Working with paths

```php
// Create from string
$path = Path::fromString('/dir1/dir2/file.php');

// Access components
$path->directory; // ['dir1', 'dir2']
$path->filename;  // 'file.php'
$path->absolute;  // true

// Get directory path
$path->dirname(); // "/dir1/dir2/"
```

### Manipulating query parameters

```php
$query = new Query(['page' => '1', 'sort' => 'name']);

// Access values with type safety
$page = $query->getInt('page');        // 1
$sort = $query->get('sort');           // 'name'
$missing = $query->get('foo', 'bar');  // 'bar' (default)

// Check for parameters
$query->has('page'); // true

// Require parameters (throws exception if missing)
$page = $query->requireInt('page');

// Create modified copies
$newQuery = $query->withArg('limit', 10);
$newQuery = $query->withArgs(['page' => 2, 'limit' => 10]);
$newQuery = $query->withoutArg('sort');
```

### Modifying URLs immutably

```php
$url = new URL(
    Path::fromString('/page'),
    new Query(['id' => '123']),
    scheme: Scheme::HTTP,
    host: new Host('example.com')
);

// Create modified versions
$https = $url->withScheme(Scheme::HTTPS);
$newPath = $url->withPath(Path::fromString('/other'));
$newQuery = $url->withQuery(new Query(['id' => '456']));

// Original URL is unchanged
echo $url;   // "http://example.com/page?id=123"
echo $https; // "https://example.com/page?id=123"
```

#### URL query helpers

To make query edits less verbose, URL exposes helper methods that delegate to the `Query` object and return new URLs:

```php
$url = new URL(
    Path::fromString('/page'),
    new Query(['a' => '1']),
    scheme: Scheme::HTTP,
    host: new Host('example.com')
);

$url = $url->withArg('b', 2);            // adds/updates a single arg
$url = $url->withArgs(['c' => true]);    // adds/updates multiple args
$url = $url->withoutArg('a');            // removes one arg
$url = $url->withoutArgs(['b', 'c']);    // removes multiple args
```

#### Permissive `with*()` inputs

Several `with*()` methods accept additional input types for convenience:

```php
$url = new URL(new Path(absolute: true));

$url = $url->withScheme('https');        // string or Stringable
$url = $url->withHost('example.com');    // string or Stringable
$url = $url->withPort(8080);             // int
$url = $url->withFragment('section');    // string or Stringable
$url = $url->withQuery(['a' => '1']);    // array
$url = $url->withPath('/docs');          // string or Stringable
```

### Resolving relative links

URLs include a `withLinkStringApplied()` method that allows updating URLs using a variety of relative URL strings, including relative paths, fragments, and both partial and full query string updates.

```php
$base = new URL(
    Path::fromString('/dir1/dir2/page.html'),
    new Query(['a' => '1'])
);

// Apply relative links (like HTML <a href="...">)
$url = $base->withLinkStringApplied('other.html');
echo $url; // "/dir1/dir2/other.html"

$url = $base->withLinkStringApplied('../file.html');
echo $url; // "/dir1/file.html"

$url = $base->withLinkStringApplied('?b=2');
echo $url; // "/dir1/dir2/page.html?b=2"

$url = $base->withLinkStringApplied('&b=2');
echo $url; // "/dir1/dir2/page.html?a=1&b=2"

$url = $base->withLinkStringApplied('#section');
echo $url; // "/dir1/dir2/page.html#section"
```

## Validation and encoding

- **Host validation**: `Host` validates IP addresses and domain names.
- **Path normalization**: `Path` resolves `.` and `..` segments and rejects `.` or `..` filenames.
- **Encoding**: `Path` and `Fragment` encode their values for safe URL output; `Query` uses `http_build_query()` for encoding.

## Error handling

Invalid inputs throw `URLException` or `QueryException` depending on the component. For example, invalid host names, query value types, or missing required query keys will raise exceptions.

## Limitations

- **HTTP/HTTPS only**: The library intentionally only supports HTTP and HTTPS schemes. This is a security feature to prevent parsing of arbitrary schemes like `javascript:`, `data:`, etc. (It does strictly allow using any backed enum as the scheme, so you could extend it to support more schemes if you like.)
- **No query parameter arrays**: Query parameters are limited to scalar types (strings, integers, floats, booleans). Arrays and objects are not supported to keep the implementation simple and focused.
- **Immutable only**: All components are readonly and immutable. You cannot modify a URL or its components in place; you must use the `with*()` methods to create new instances with your changes.

## Requirements

Fully tested on PHP 8.3+, static analysis for PHP 8.1+.

## License

MIT License - See [LICENSE](LICENSE) file for details.